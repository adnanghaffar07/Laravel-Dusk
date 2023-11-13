<?php
/**
 * Copyright (c) 2018, BuyerDocs, LLC.
 * All rights reserved.
 */

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;

use App\Buyer;
use App\User;

class buyerPasscodeTest extends DuskTestCase
{
    /**
     * Create new buyer and assure that passcode exists if account requires it and phone number is included
     * @group buyer
     * @throws
     * @return void
     */
    public function testAddClientWithPasscode()
    {
        $this->browse(function (Browser $browser) {

            $admin = User::where([
                ['name', 'Test User'],
                ['email', 'test@test.test'],
            ])->firstOrFail();
            $oldAllowsPhone = $admin->account->allows_buyer_phone;
            $oldRequiresPasscode = $admin->account->requires_buyer_passcode;

            $admin->account->forceFill([
                'allows_buyer_phone' => true,
                'requires_buyer_passcode' => true
            ])->save();

            $browser->loginAs($admin)
                ->visit(route('userHome'))
                ->waitForText('ADD CLIENTS')
                ->assertSee('ADD CLIENTS');

            $browser->element('#openAddModal')->getLocationOnScreenOnceScrolledIntoView();

            $browser->pause(500);

            // Add client
            $browser->type('email[0]', 'duskwithpasscode@autotest.com')
                ->type('phone[0]', '2564572639')
                ->type('refnum', 'Testing passcode')
                ->click('#notify-label')  // uncheck notify
                ->select('company', '1')
                ->select('branch', '1')
                ->select('wireDoc', '1')
                ->click('#openAddModal')
                ->waitForText('Confirm')// check modal
                ->assertSee('Wiring Instructions Sample.pdf')
                ->assertSee('*Will not receive any notification');

            // Click in modal to add client
            $browser->waitFor('#addModal.modal.fade.show')
                ->pause(250)// allow modal to open
                ->waitForText('Confirm')
                ->press('Confirm');

            // Dismiss toastr notification
            $browser->waitForText('Success!', 10)
                ->click('#toast-container')
                ->waitUntilMissing('#toast-container')
                ->assertDontSee('Success!');

            $buyer = Buyer::where(['email' => 'duskwithpasscode@autotest.com'])->firstOrFail();

            $this->assertNotNull($buyer->passcode);

            $admin->account->allows_buyer_phone = $oldAllowsPhone;
            $admin->account->requires_buyer_passcode = $oldRequiresPasscode;
            $admin->account->save();
        });
    }


    /**
     * @group buyer
     * @throws
     * @return void
     */
    public function testLoginWithPasscode()
    {
        $this->browse(function (Browser $browser) {
            $admin = User::where([
                ['name', 'Test User'],
                ['email', 'test@test.test'],
            ])->firstOrFail();
            $oldAllowsPhone = $admin->account->allows_buyer_phone;
            $oldRequiresPasscode = $admin->account->requires_buyer_passcode;

            $admin->account->forceFill([
                'allows_buyer_phone' => true,
                'requires_buyer_passcode' => true
            ])->save();

            $browser->visit(route('buyerLogin', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                ->waitForText('BUYERS / LENDERS');

            // Ensure we are still here (because terms is unchecked)
            $browser->type('email', 'duskwithpasscode@autotest.com')
                ->select('title_id', '1')
                ->click('.custom-checkbox')
                ->press('Login')
                ->waitForText('Select notification type');

            // Click in modal to accept warning
            $browser->waitFor('#selectPhoneTypeModal.modal.fade.show')
                ->pause(500)                // allow modal to open
                ->waitForText('Already have a passcode?')
                ->clickLink('Already have a passcode?')
                ->waitForText('Enter passcode')
                ->assertSee('Resend passcode')
                ->clickLink('Resend passcode')
                ->waitForText('Already have a passcode?')
                ->clickLink('Already have a passcode?');

            // Try wrong passcode
            $browser->type('passcode', '123')
                ->press('Submit')
                ->waitForText('Invalid passcode. Please try again.')
                ->assertSee('Invalid passcode. Please try again.');

            // Use correct passcode
            $buyer = Buyer::where(['email' => 'duskwithpasscode@autotest.com'])->firstOrFail();
            $browser->type('passcode', $buyer->passcode)
                ->press('Submit')
                ->waitForText('WARNING')
                ->assertSee('WARNING');

            // Accept wire fraud warning
            $browser->waitFor('#warnModal.modal.fade.show')
                ->pause(500)                // allow modal to open
                ->waitForText('Accept')
                ->press('Accept')
                ->waitforText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                ->assertSee('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS');

            $admin->account->allows_buyer_phone = $oldAllowsPhone;
            $admin->account->requires_buyer_passcode = $oldRequiresPasscode;
            $admin->account->save();
        });
    }
}
