<?php
/**
 * Copyright (c) 2018, BuyerDocs, LLC.
 * All rights reserved.
 */

namespace Tests\Browser;

use Carbon\Carbon;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;

use App\Seller;
use App\User;

class sellerPasscodeTest extends DuskTestCase
{
    /**
     * Create new seller and assure that passcode exists if account requires it and phone number is included
     * @group seller
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
            $oldAllowsPhone = $admin->account->allows_seller_phone;
            $oldRequiresPasscode = $admin->account->requires_seller_passcode;

            $admin->account->forceFill([
                'allows_seller_phone' => true,
                'requires_seller_passcode' => true
            ])->save();

            $browser->loginAs($admin)
                ->visit(route('userSellerIndex'))
                ->waitForText('ADD CLIENTS')
                ->assertSee('ADD CLIENTS');

            $browser->element('#openAddModal')->getLocationOnScreenOnceScrolledIntoView();

            $browser->pause(500);

            // Add client
            $browser->type('email', 'duskwithpasscode@autotest.com')
                ->type('phone', '+15005550006')
                ->type('refnum', 'Testing passcode')
                ->click('#notify-label')  // uncheck notify
                ->select('company', '1')
                ->select('branch', '1')
                ->click('#openAddModal')
                ->waitForText('Confirm')// check modal
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

            $seller = Seller::where(['email' => 'duskwithpasscode@autotest.com'])->firstOrFail();

            $admin->account->allows_seller_phone = $oldAllowsPhone;
            $admin->account->requires_seller_passcode = $oldRequiresPasscode;
            $admin->account->save();
        });
    }


    /**
     * @group seller
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
            $oldAllowsPhone = $admin->account->allows_seller_phone;
            $oldRequiresPasscode = $admin->account->requires_seller_passcode;

            $seller = Seller::where(['email' => 'duskwithpasscode@autotest.com'])->firstOrFail();

            $admin->account->forceFill([
                'allows_seller_phone' => true,
                'requires_seller_passcode' => true
            ])->save();

            $browser->visit(route('sellerLogin', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                ->waitForText('CLIENT LOGIN');

            $browser->type('email', $seller->email)
                ->type('accessCode', $seller->token)
                ->press('Login')
                ->waitForText('Select notification type');

            $browser->waitFor('#selectPhoneTypeModal.modal.fade.show')
                ->pause(500)                // allow modal to open
                ->waitForText('Already have a passcode?')
                ->clickLink('Already have a passcode?')
                ->waitForText('Enter passcode')
                ->assertSee('Resend passcode')
                ->clickLink('Resend passcode')
                ->waitForText('Already have a passcode?')
                ->press('Continue')
                ->waitForText('Enter passcode');

            // set expired passcode date
            $seller = $seller->fresh();
            $seller->passcode_created_at = Carbon::now()->subMinutes(11);
            $seller->save();

            // Try wrong passcode
            $browser->type('passcode', '123')
                ->press('Submit')
                ->waitForText('Invalid passcode. Please try again.')
                ->assertSee('Invalid passcode. Please try again.');

            // check passcode timeout
            $browser->type('passcode', $seller->passcode)
                ->press('Submit')
                ->waitForText('Passcode timed out. Please request a new passcode.')
                ->assertSee('Passcode timed out. Please request a new passcode.');

            // set actual passcode date
            $seller = $seller->fresh();
            $seller->passcode_created_at = Carbon::now();
            $seller->save();

            // Use correct passcode
            $browser->type('passcode', $seller->passcode)
                ->press('Submit')
                ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                ->assertSee('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS');

            $admin->account->allows_seller_phone = $oldAllowsPhone;
            $admin->account->requires_seller_passcode = $oldRequiresPasscode;
            $admin->account->save();
        });
    }
}
