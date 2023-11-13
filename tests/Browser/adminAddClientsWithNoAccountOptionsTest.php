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
 * This test group is for a User with 'Admin' privileges, multiple companies/branches, and no account options
 * Class adminAddClientsWithNoAccountOptionsTest
 * @package Tests\Browser
 */
class adminAddClientsWithNoAccountOptionsTest extends DuskTestCase
{
    use baseAdminClientsTest;

    /**
     * @group adminAddClients
     * @throws
     * @return void
     */
    protected function additionalSetUp()
    {
        $this->user = User::where([
            ['name', 'Test User'],
            ['email', 'test@test.test'],
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
     * @group adminAddClients
     * @throws
     * @return void
     */
    public function testAddError()
    {
        $this->addError();
    }

    /**
     * @group adminAddClients
     * @throws
     * @return void
     */
    public function testAddSuccess1()
    {
        $this->addSuccess1();
    }

    /**
     * @group adminAddClients
     * @throws
     * @return void
     */
    public function testAddSuccess2()
    {
        $this->addSuccess2();
    }

    /**
     * @group adminAddClients
     * @throws
     * @return void
     */
    public function testAddWithPhone()
    {
        $this->addWithPhone();
    }

    /**
     * @group adminAddClients
     * @throws
     * @return void
     */
    public function testAddNotificationText()
    {
        $this->addNotificationText();
    }

    /**
     * @group adminAddClients
     * @throws
     * @return void
     */
    public function testNotifyClient()
    {
        $this->notifyClient();
    }

    /**
     * @group adminAddClients
     * @throws
     * @return void
     */
    public function testDeleteClient()
    {
        $this->deleteClient();
    }
}
