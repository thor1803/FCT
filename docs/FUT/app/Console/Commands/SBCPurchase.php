<?php
/**
 * Created by PhpStorm.
 * User: curtiscrewe
 * Date: 29/10/2018
 * Time: 18:34
 */

namespace App\Console\Commands;

use Backpack\Settings\app\Models\Setting;
use Carbon\Carbon;
use FUTApi\Core;
use FUTApi\FutError;
use Illuminate\Console\Command;
use App\Models\Accounts;
use duzun\hQuery;

class SBCPurchase extends Command {

    protected $signature = 'sbc_purchase:run {solution_url : SBC URL to FUTBIN Challenge} {--percentages : Whether to use our sniping percentages to buy players}';

    protected $description = 'Snipe SBC requirement cards';

    /**
     * The FUT API object
     *
     * @var array
     */
    protected $fut = [];

    /**
     * The Account Object
     *
     * @var array
     */
    protected $account = [];

    /**
     * FUT Cards Purchased
     *
     * @var array
     */
    protected $cards = [];

    /**
     * SBCPurchase constructor.
     */
    public function __construct() {
        parent::__construct();
    }

    public function handle()
    {
        $solution_url = $this->argument('solution_url');
        $this->account = Accounts::where('status', '1')->where('in_use', '0')->whereNotNull('phishingToken')->first();
        if(!$this->account) {
            abort(403);
        }
        Accounts::find($this->account->id)->update([
            'in_use' => '1'
        ]);
        try {

            $this->fut = new Core(
                $this->account->email,
                $this->account->password,
                strtolower($this->account->platform),
                null,
                false,
                false,
                storage_path(
                    'app/fut_cookies/' . md5($this->account->email)
                )
            );
            $this->fut->setSession(
                $this->account->personaId,
                $this->account->nucleusId,
                $this->account->phishingToken,
                $this->account->sessionId,
                date("Y-m", strtotime($this->account->dob))
            );

            $doc = hQuery::fromUrl($solution_url);
            $players = $doc->find('div.card > .cardetails > a');
            if($players) {
                foreach($players as $pos => $a) {
                    $player_card = $a->find('div')[0];
                    if($player_card) {
                        $this->cards['players'][] = [
                            'prices' => [
                                'xbox' => $player_card->attr('data-price-xbl'),
                                'ps' => $player_card->attr('data-price-ps3'),
                                'pc' => $player_card->attr('data-price-pc')
                            ],
                            'futbin_id' => $player_card->attr('data-player-id'),
                            'resource_id' => $player_card->attr('data-resource-id'),
                            'base_id' => $player_card->attr('data-base-id'),
                            'rating' => $player_card->attr('data-rating'),
                            'bought' => false
                        ];
                    }
                }
            }

            $cache_file = storage_path('app/public/'.$this->account->id.'-club_players.json');
            $cache_life = '500';
            $filemtime = @filemtime($cache_file);

            $bought_players_file = storage_path('app/public/'.$this->account->id.'-'.md5($solution_url).'-bought_players.json');
            if(!file_exists($bought_players_file)) {
                file_put_contents($bought_players_file, '[]');
                $this->cards['bought_players'] = [];
            } else {
                $this->cards['bought_players'] = json_decode(file_get_contents($bought_players_file), true);
            }

            if (!$filemtime or (time() - $filemtime >= $cache_life)){
                $this->info("Please hold while we update your club contents cache.");
                $club_players = $this->fut->club();
                $start = 0;
                do {
                    foreach ($club_players['itemData'] as $player) {
                        if (isset($player['loans']))
                            continue;
                        if ($player['resourceId'])
                            $this->cards['club_players'][] = $player['resourceId'];
                    }
                    $club_players = $this->fut->club(
                        'desc',
                        'player',
                        null,
                        $start
                    );
                    $count = count($club_players['itemData']);
                    $start = ($start + 91);
                    $rand = rand(4, 7);
                    sleep($rand);
                } while ($count === 91);
                file_put_contents($cache_file,json_encode($this->cards['club_players']));
            } else {
                $this->cards['club_players'] = json_decode(file_get_contents($cache_file), true);
            }

            $collected_cards = 0;
            $required_cards = count($this->cards['players']);

            if(count($this->cards['players']) === 11) {
                foreach ($this->cards['players'] as $player) {
                    if(in_array($player['resource_id'], $this->cards['bought_players'])) {
                        $collected_cards++;
                        $this->info("It looks like we already bought: ".$player['resource_id']);
                        continue;
                    }
                    if(in_array($player['resource_id'], $this->cards['club_players'])) {
                        $collected_cards++;
                        $this->info("Let's not buy: ".$player['resource_id']." as we already have it in our club");
                        continue;
                    }
                    switch($this->account->platform) {
                        case "XBOX":
                            if($this->option('percentages') == true) {
                                $price_arr = $prices = calculate_prices($player['prices']['xbox'], Setting::get('buy_percentage'), Setting::get('sell_percentage'));
                                $price = $price_arr['max_bin'];
                            } else {
                                $price = $player['prices']['xbox'];
                            }
                            break;
                        case "PS4":
                            if($this->option('percentages') == true) {
                                $price_arr = $prices = calculate_prices($player['prices']['ps'], Setting::get('buy_percentage'), Setting::get('sell_percentage'));
                                $price = $price_arr['max_bin'];
                            } else {
                                $price = $player['prices']['ps'];
                            }
                            break;
                        case "PC":
                            if($this->option('percentages') == true) {
                                $price_arr = $prices = calculate_prices($player['prices']['pc'], Setting::get('buy_percentage'), Setting::get('sell_percentage'));
                                $price = $price_arr['max_bin'];
                            } else {
                                $price = $player['prices']['pc'];
                            }
                            break;
                    }
                    $this->info("We are going to search for ".$player['resource_id']." with a Max BIN of ".$price);
                    $counter = 0;
                    $search_limit = Setting::get('rpm_limit');
                    do {

                        $sleep_time = rand(1,8);
                        $this->info("Sleeping for ".$sleep_time." seconds before we search for ".$player['resource_id']." - ".Carbon::now()->toDayDateTimeString());
                        sleep($sleep_time);
                        $randomBid = rand(14000000, 15000000);
                        $formattedBid = floor($randomBid / 1000) * 1000;
                        $search = $this->fut->searchAuctions(
                            'player',
                            null,
                            null,
                            $player['resource_id'],
                            null,
                            null,
                            $formattedBid,
                            null,
                            $price
                        );
                        if(!empty($search['auctionInfo'])) {
                            usort($search['auctionInfo'], function($previous, $next) {
                                return $previous["buyNowPrice"] > $next["buyNowPrice"] ? 1 : -1;
                            });
                            $cheapest_item = $search['auctionInfo'][0];
                            $bid = $this->fut->bid($cheapest_item['tradeId'], $cheapest_item['buyNowPrice']);
                            if(isset($bid['auctionInfo'])) {
                                $collected_cards++;
                                $this->info("It looks like we bought ".$player['resource_id']." successfully for ".$cheapest_item['buyNowPrice']."!");
                                array_push($this->cards['bought_players'], (int)$player['resource_id']);
                                $counter = $search_limit;
                                file_put_contents($bought_players_file, json_encode($this->cards['bought_players']));
                                sleep(2);
                                $items = $this->fut->unassigned();
                                if(count($items['itemData']) > 0) {
                                    foreach($items['itemData'] as $item) {
                                        $this->fut->sendToClub($item['id']);
                                    }
                                }
                            }
                        }
                        $counter++;

                    } while($counter < $search_limit);
                }
            }

            Accounts::find($this->account->id)->update([
                'in_use' => '0'
            ]);

            if(config('laravel-slack.slack_webhook_url') !== null) {
                \Slack::to(config('laravel-slack.default_channel'))->send('SBCPurchaser - We have completed a run having collected '.$collected_cards.' cards out the required '.$required_cards);
            }

        } catch(FutError $exception) {

            $error = $exception->GetOptions();

            if($error['reason'] == 'permission_denied') {
                $this->info('We was too slow trying to snipe!');
                return;
            }

            Accounts::find($this->account->id)->update([
                'phishingToken' => null,
                'sessionId' => null,
                'nucleusId' => null,
                'status' => '-1',
                'status_reason' => $error['reason'],
                'in_use' => '0'
            ]);

        }
    }

}