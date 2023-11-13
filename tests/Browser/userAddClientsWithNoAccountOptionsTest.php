<?php
/**
 * Copyright (c) 2018, BuyerDocs, LLC.
 * All rights reserved.
 */

namespace Tests\Browser;

use Tests\baseAdminClientsTest;
use Tests\DuskTestCase;

use App\User;

/**
 * This test group is for a User with 'User' privileges, a single company/branch, and no account options
 * Class adminAddClientsWithNoAccountOptionsTest
 * @package Tests\Browser
 */
class userAddClientsWithNoAccountOptionsTest extends DuskTestCase
{
    use baseAdminClientsTest;

    /**
     * @group userAddClients
     * @throws
     * @return void
     */
    protected function additionalSetUp()
    {
        $this->user = User::where([
            ['name', 'Single Branch User'],
            ['email', 'singlebranchuser@test.test'],
        ])
            ->firstOrFail();

        $this->user->account->update([
            'allows_buyer_notes' => 0,
            'allows_buyer_phone' => 0,
            'allows_buyer_documents' => 0,
            'allows_balance_due' => 0,
            'allows_welcome_email' => 0,
        ]);

        $this->notificationMessage = '*Will receive an email notification';
    }

    /**
     * @group userAddClients
     * @throws
     * @return void
     */
    public function testAddError()
    {
        $this->addError();
    }

    /**
     * @group userAddClients
     * @throws
     * @return void
     */
    public function testAddSuccess1()
    {
        $this->addSuccess1();
    }

    /**
     * @group userAddClients
     * @throws
     * @return void
     */
    public function testAddSuccess2()
    {
        $this->addSuccess2();
    }

    /**
     * @group userAddClients
     * @throws
     * @return void
     */
    public function testAddWithPhone()
    {
        $this->addWithPhone();
    }

    /**
     * @group userAddClients
     * @throws
     * @return void
     */
    public function testAddNotificationText()
    {
        $this->addNotificationText();
    }

    /**
     * @group userAddClients
     * @throws
     * @return void
     */
    public function testNotifyClient()
    {
        $this->notifyClient();
    }

    /**
     * @group userAddClients
     * @throws
     * @return void
     */
    public function testDeleteClient()
    {
        $this->deleteClient();
    }
}
