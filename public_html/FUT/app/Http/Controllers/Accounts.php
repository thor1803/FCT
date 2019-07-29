<?php
/**
 * Created by PhpStorm.
 * User: curtiscrewe
 * Date: 19/10/2018
 * Time: 00:03
 */

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccounts as StoreRequest;
use App\Http\Requests\UpdateAccounts as UpdateRequest;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Carbon\Carbon;

class Accounts extends CrudController
{

    public function setup() {
        $this->crud->setModel("App\Models\Accounts");
        $this->crud->setRoute("admin/accounts");
        $this->crud->setEntityNameStrings('Accounts', 'Account');
        $this->crud->setColumns([
            'email',
            'platform',
            [
                'label' => 'Coins',
                'type' => 'closure',
                'function' => function($entry) {
                    return number_format($entry->coins);
                }
            ],
            [
                'name' => 'tradepile_cards',
                'label' => 'Tradepile Cards',
            ],
            [
                'label' => 'Tradepile Value',
                'type' => 'closure',
                'function' => function($entry) {
                    return number_format($entry->tradepile_value);
                }
            ],
            [
                'name' => 'updated_at',
                'label' => 'Last Update',
            ],
            [
                'label' => 'Status',
                'type' => 'closure',
                'function' => function($entry) {
                    if($entry->in_use === '1') {
                        return "<label class='label label-warning'>Running</label>";
                    }
                    if($entry->status === '1') {
                        if(is_null($entry->phishingToken)) {
                            return "<label class='label label-danger'>Offline</label>";
                        } else {
                            if($entry->cooldown === '0') {
                                return "<label class='label label-success'>Online</label>";
                            } else {
                                return "<label class='label label-info'>Cooldown</label>";
                            }
                        }
                    } elseif($entry->status == "-1") {
                        return "<label class='label label-danger'>".ucfirst($entry->status_reason)."</label>";
                    } else {
                        return "<label class='label label-danger'>Offline</label>";
                    }
                }
            ]
        ]);
        $this->crud->addField([
            'name' => 'platform',
            'label' => 'Platform',
            'type' => 'radio',
            'options' => [
                'XBOX' => 'Xbox One',
                'PS4' => 'Playstation 4',
                'PC' => 'PC'
            ],
            'default' => 'XBOX',
            'inline' => true
        ]);
        $this->crud->addField([
            'name' => 'email',
            'label' => 'Email',
            'attributes' => [
                'placeholder' => 'Your EA account email'
            ]
        ]);
        $this->crud->addField([
            'name' => 'password',
            'label' => 'Password',
            'attributes' => [
                'placeholder' => 'Your EA account password'
            ]
        ]);
        $this->crud->addField([
            'name' => 'code_method',
            'label' => 'Origin Code Method',
            'type' => 'radio',
            'options' => [
                '1' => 'Backup Codes'
            ],
            'default' => '1',
            'inline' => true
        ]);
        $this->crud->addField([
            'name' => 'backup_codes',
            'label' => 'Backup Codes',
            'attributes' => [
                'placeholder' => '12345,12345,12345,12345'
            ]
        ]);
        $this->crud->addField([
            'name' => 'endpoint',
            'label' => 'Endpoint',
            'type' => 'radio',
            'options' => [
                '0' => 'WebApp',
                '1' => 'Mobile'
            ],
            'default' => '0',
            'inline' => true
        ]);
        $this->crud->addField([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'radio',
            'options' => [
                '1' => 'Enabled',
                '0' => 'Disabled',
                '-1' => 'Offline'
            ],
            'default' => '1',
            'inline' => true
        ]);
        $this->crud->addField([
            'name' => 'in_use',
            'label' => 'In Use (Advanced Usage Only)',
            'type' => 'radio',
            'options' => [
                '0' => 'No',
                '1' => 'Yes'
            ],
            'default' => '0',
            'inline' => true
        ]);
    }

    public function store(StoreRequest $request) {
        return parent::storeCrud();
    }

    public function update(UpdateRequest $request) {
        return parent::updateCrud();
    }

}