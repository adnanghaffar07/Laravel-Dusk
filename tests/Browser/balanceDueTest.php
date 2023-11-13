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

class balanceDueTest extends DuskTestCase
{
    /**
     * @group balanceDue
     * @throws \Throwable
     * @return void
     */
    public function testClientWithoutBalanceDue()
    {
        $this->browse(function (Browser $browser) {

            // Add client as admin
            $admin = User::where([
                ['name', 'Test User'],
                ['email', 'test@test.test'],
            ])->firstOrFail();
            $oldBalanceDue = $admin->account->allows_balance_due;
            $admin->account->allows_balance_due = true;
            $admin->account->save();

            $browser->loginAs($admin)
                ->visit(route('userHome'))
                ->waitForText('ADD CLIENTS')
                ->assertSee('ADD CLIENTS');

            // Add client2
            $browser->type('email[0]', 'duskTestWithoutBalanceDue@autotest.com')
                ->type('refnum', 'File 1234 from dusk without balance due')
                ->select('company', '1')
                ->select('branch', '1')
                ->select('wireDoc', '9');

            $browser->element('#openAddModal')->getLocationOnScreenOnceScrolledIntoView();

            $browser->pause(1000)
                ->click('#openAddModal')
                ->waitForText('Confirm')  // check modal
                ->assertSee('Wiring Instructions Sample.pdf')
                ->assertSee('Balance due:');

            // Click in modal to add client
            $browser->waitFor('#addModal.modal.fade.show')
                ->pause(250)                // allow modal to open
                ->waitForText('Confirm')
                ->press('Confirm');

            // Dismiss toastr notification
            $browser->waitForText('Success!')
                ->click('#toast-container')
                ->waitUntilMissing('#toast-container')
                ->assertDontSee('Success!');

            // Ensure buyer login works
            $buyer = Buyer::with('buyerFiles')->where('email', 'duskTestWithoutBalanceDue@autotest.com')->firstOrFail();
            $buyerFile = $buyer->buyerFiles->first();
            self::assertNull($buyerFile->balance_due);

            $this->loginBuyer($buyer, $browser);
            $browser->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                ->assertDontSee('Balance due:');
            $this->logoutBuyer($browser);

            $admin->account->allows_balance_due = $oldBalanceDue;
            $admin->account->save();

            $buyer = $buyer->fresh();
            foreach ($buyer->buyerFiles as $buyerFile) {
                $buyerFile->delete();
            }
        });
    }
    
    /**
     * @group balanceDue
     * @throws \Throwable
     * @return void
     */
    public function testClientWithBalanceDue()
    {
        $this->browse(function (Browser $browser) {

            $balanceDue = 123.45;

            $admin = User::where([
                ['name', 'Test User'],
                ['email', 'test@test.test'],
            ])->firstOrFail();

            $oldBalanceDue = $admin->account->allows_balance_due;
            $admin->account->allows_balance_due = true;
            $admin->account->save();

            $browser->loginAs($admin)
                ->visit(route('userHome'))
                ->waitForText('ADD CLIENTS')
                ->assertSee('ADD CLIENTS');

            // Add client2
            $browser->type('email[0]', 'duskTestWithBalanceDue@autotest.com')
                ->type('refnum', 'File 1234 from dusk without balance due')
                ->type('balance_due', $balanceDue)
                ->select('company', '1')
                ->select('branch', '1')
                ->select('wireDoc', '9');

            $browser->element('#openAddModal')->getLocationOnScreenOnceScrolledIntoView();

            $browser->pause(1000)
                ->click('#openAddModal')
                ->waitForText('Confirm')  // check modal
                ->assertSee('Wiring Instructions Sample.pdf')
                ->assertSee('Balance due:')
                ->assertSee('$' . $balanceDue);

            // Click in modal to add client
            $browser->waitFor('#addModal.modal.fade.show')
                ->pause(250)                // allow modal to open
                ->waitForText('Confirm')
                ->press('Confirm');

            // Dismiss toastr notification
            $browser->waitForText('Success!')
                ->click('#toast-container')
                ->waitUntilMissing('#toast-container')
                ->assertDontSee('Success!');

            // Ensure buyer login works
            $buyer = Buyer::with('buyerFiles')->where('email', 'duskTestWithBalanceDue@autotest.com')->firstOrFail();
            $buyerFile = $buyer->buyerFiles->first();
            self::assertEquals($buyerFile->balance_due, $balanceDue);

            $this->loginBuyer($buyer, $browser);
            $browser->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                ->assertSee('Balance due:')
                ->assertSee('$' . $balanceDue);
            $this->logoutBuyer($browser);

            $admin->account->allows_balance_due = $oldBalanceDue;
            $admin->account->save();

            $buyer = $buyer->fresh();
            foreach ($buyer->buyerFiles as $buyerFile) {
                $buyerFile->delete();
            }
        });
    }
}
