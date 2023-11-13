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

class notesTest extends DuskTestCase
{
    /**
     * @group notes
     * @throws \Throwable
     * @return void
     */
    public function testClientWithoutNotes()
    {
        $this->browse(function (Browser $browser) {

            // Add client as admin
            $admin = User::where([
                ['name', 'Test User'],
                ['email', 'test@test.test'],
            ])->firstOrFail();
            $oldAllowsNotes = $admin->account->allows_buyer_notes;
            $admin->account->allows_buyer_notes = true;
            $admin->account->save();

            $browser->loginAs($admin)
                ->visit(route('userHome'))
                ->waitForText('ADD CLIENTS')
                ->assertSee('ADD CLIENTS');

            // Add client2
            $browser->type('email[0]', 'duskTestWithoutNotes@autotest.com')
                ->type('refnum', 'File 1234 from dusk without notes')
                ->select('company', '1')
                ->select('branch', '1')
                ->select('wireDoc', '9');

            $browser->element('#openAddModal')->getLocationOnScreenOnceScrolledIntoView();

            $browser->pause(1000)
                ->click('#openAddModal')
                ->waitForText('Confirm')  // check modal
                ->assertSee('Wiring Instructions Sample.pdf');

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
            $buyer = Buyer::with('buyerFiles')->where('email', 'duskTestWithoutNotes@autotest.com')->firstOrFail();
            $buyerFile = $buyer->buyerFiles->first();
            self::assertNull($buyerFile->notes);

            $this->loginBuyer($buyer, $browser);
            $browser->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                ->assertDontSee('Notes:');
            $this->logoutBuyer($browser);

            $admin->account->allows_buyer_notes = $oldAllowsNotes;
            $admin->account->save();

            $buyer = $buyer->fresh();
            foreach ($buyer->buyerFiles as $buyerFile) {
                $buyerFile->delete();
            }
        });
    }
    
    /**
     * @group notes
     * @throws \Throwable
     * @return void
     */
    public function testClientWithNotes()
    {
        $this->browse(function (Browser $browser) {

            $notes = "This is a test note from Dusk";

            $admin = User::where([
                ['name', 'Test User'],
                ['email', 'test@test.test'],
            ])->firstOrFail();
            $oldAllowsNotes = $admin->account->allows_buyer_notes;
            $admin->account->allows_buyer_notes = true;
            $admin->account->save();

            $browser->loginAs($admin)
                ->visit(route('userHome'))
                ->waitForText('ADD CLIENTS')
                ->assertSee('ADD CLIENTS');

            // Add client2
            $browser->type('email[0]', 'duskTestWithNotes@autotest.com')
                ->type('refnum', 'File 1234 from dusk with notes')
                ->select('company', '1')
                ->select('branch', '1')
                ->select('wireDoc', '9')
                ->type('notes', $notes);

            $browser->element('#openAddModal')->getLocationOnScreenOnceScrolledIntoView();

            $browser->pause(1000)
                ->click('#openAddModal')
                ->waitForText('Confirm')  // check modal
                ->assertSee('Wiring Instructions Sample.pdf');

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
            $buyer = Buyer::with('buyerFiles')->where('email', 'duskTestWithNotes@autotest.com')->firstOrFail();
            $buyerFile = $buyer->buyerFiles->first();
            self::assertEquals($buyerFile->notes, $notes);

            $this->loginBuyer($buyer, $browser);
            $browser->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                ->assertSee('Notes:')
                ->assertSee($notes);
            $this->logoutBuyer($browser);

            $admin->account->allows_buyer_notes = $oldAllowsNotes;
            $admin->account->save();

            $buyer = $buyer->fresh();
            foreach ($buyer->buyerFiles as $buyerFile) {
                $buyerFile->delete();
            }
        });
    }
}
