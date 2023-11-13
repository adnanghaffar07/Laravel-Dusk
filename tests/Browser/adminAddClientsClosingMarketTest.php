<?php
/**
 * Copyright (c) 2018, BuyerDocs, LLC.
 * All rights reserved.
 */

namespace Tests\Browser;

use App\Services\ClosingMarketService;
use App\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class adminAddClientsClosingMarketTest extends DuskTestCase
{
    protected $user;

    protected $notificationMessage;
    private $branch;

    /**
     * @group adminClients
     * @throws
     * @return void
     */
    public function setUp()
    {
        parent::setup();

        $this->user = User::where([
            ['name', 'Test User'],
            ['email', 'test@test.test'],
        ])
            ->firstOrFail();
        $this->branch = $this->user->branches()->first();

        $this->additionalSetUp();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                // ->maximize()
                ->visit(route('userHome'))
                ->waitForText('ADD CLIENTS')
                ->assertSee('ADD CLIENTS');
        });
    }

    /**
     * @group adminClients
     * @throws
     * @return void
     */
    protected function additionalSetUp()
    {
        $this->user->account->update([
            'allows_buyer_notes' => 1,
            'allows_buyer_phone' => 1,
            'allows_buyer_documents' => 1,
            'allows_balance_due' => 1
        ]);

        $this->notificationMessage = '*Will receive an email and text/call notification';
    }

    /**
     * @group adminClients
     * @throws
     * @return void
     */
    public function testClosingMarketSuccess()
    {
        if (!(env('CM_CONFIG_LOGIN') && env('CM_CONFIG_PASSWORD') && env('CM_CONFIG_DOMAIN'))) {
            throw new \Exception('No Closing Market credentials provided. Check .env.dusk');
        }

        $this->browse(function (Browser $browser) {
            $cmService = new ClosingMarketService();
            $cmTradingPartners = $cmService->GetTradingPartnerList();
            $cmEnterpriseServiceID = ($cmTradingPartners->first())['EnterpriseServiceID'] ?? 0;

            $this->branch->allows_closingmarket = true;
            $this->branch->enterprise_service_id = $cmEnterpriseServiceID;
            $this->branch->active = 1;
            $this->branch->save();

            $orders = $cmService->GetOrderList($this->branch);
            $refNumber = $orders->last();

            $browser->visit(route('userHome'));

            $browser->waitForText('Closing Market integration is available');
            $browser->assertSee('Closing Market integration is available. Select a branch to enable autocomplete');

            $browser->select('company', $this->branch->title->id);
            $browser->select('branch', $this->branch->id);

            $browser->waitForText('Loading autocomplete data');
            $browser->waitForText('Begin typing the reference file number to autocomplete', 30);

            $browser->type('refnum', $refNumber);
            $browser->waitFor('.ui-autocomplete');
            $browser->click('.ui-menu-item:first-child');
            $browser->waitUntilMissing('.loadingoverlay', 30);
            $browser->pause(500);

            $scriptResult = $browser->script('return $("div:contains(\'Choose an address\')").length');
            $selectAddressExists = $scriptResult[0] ?? 0;
            if ($selectAddressExists) {
                $browser->click('button.ui-button:first-child');
                $browser->pause(500);
            }

            //
        });
    }

    /**
     * @group adminClients
     * @throws
     * @return void
     */
    public function testClosingMarketFailNoOrdersFound()
    {
        $this->browse(function (Browser $browser) {
            $this->branch->allows_closingmarket = true;
            $this->branch->enterprise_service_id = 99999990; // set wrong id
            $this->branch->active = 1;
            $this->branch->save();

            $browser->visit(route('userHome'));

            $browser->waitForText('Closing Market integration is available');
            $browser->assertSee('Closing Market integration is available. Select a branch to enable autocomplete');

            $browser->select('company', $this->branch->title->id);
            $browser->select('branch', $this->branch->id);

            $browser->waitForText('Connection to Closing Market was successful, but no open orders were found', 30);
            $browser->assertDontSee('Begin typing the reference file number to autocomplete');
        });
    }

    /**
     * @group adminClients
     * @throws
     * @return void
     */
    public function testClosingMarketNotAvailable()
    {
        $this->browse(function (Browser $browser) {
            $this->branch->allows_closingmarket = false;
            $this->branch->enterprise_service_id = null;
            $this->branch->active = 1;
            $this->branch->save();

            $browser->visit(route('userHome'));
            $browser->waitForText('ADD CLIENTS');
            $browser->assertDontSee('Closing Market integration is available');
        });
    }
}
