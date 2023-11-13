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
 * This test group is for a User with 'Admin' privileges, multiple companies/branches, and all account options
 * Class adminAddClientsWithAllAccountOptionsTest
 * @package Tests\Browser
 */
class adminAddClientsWithAllAccountOptionsTest extends DuskTestCase
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
            'allows_buyer_notes' => 1,
            'allows_buyer_phone' => 1,
            'allows_buyer_documents' => 1,
            'allows_balance_due' => 1,
            'allows_close_date' => 1,
            'allows_welcome_email' => 1,
        ]);

        $this->notificationMessage = '*Will receive an email and text/call notification';
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
    public function testCheckInactiveWireDocText()
    {
        $this->checkInactiveWireDocText();
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
