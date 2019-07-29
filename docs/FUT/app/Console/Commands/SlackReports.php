<?php
/**
 * Created by PhpStorm.
 * User: curtiscrewe
 * Date: 29/10/2018
 * Time: 01:31
 */

namespace App\Console\Commands;

use App\Models\Transactions;
use Illuminate\Console\Command;
use App\Models\Accounts;
use Carbon\Carbon;

class SlackReports extends Command {

    protected $signature = 'reports:cron';

    protected $description = 'Send reports to Slack';

    public function __construct() {
        parent::__construct();
    }

    public function handle()
    {
        if(is_null(config('laravel-slack.slack_webhook_url'))) {
            return;
        }
        $todays_profit = today_profit();
        $online_accounts = Accounts::query()->where('status', '1')->count();
        $todays_buys = Transactions::query()->whereDate('bought_time', Carbon::now()->format('Y-m-d'))->count();
        $todays_sales = Transactions::query()->whereDate('sold_time', Carbon::now()->format('Y-m-d'))->count();
        $available_coins = Accounts::query()->sum('coins');
        $message = '*Half Hourly Report*
_Todays Profit_ : '.number_format($todays_profit).'
_Todays Buys_ : '.number_format($todays_buys).'
_Todays Sales_ : '.number_format($todays_sales).'
_Online Accounts_ : '.number_format($online_accounts).'
_Available Coins_ : '.number_format($available_coins);
        \Slack::to(config('laravel-slack.default_channel'))->send($message);
    }

}