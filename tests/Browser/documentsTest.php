<?php
/**
 * Copyright (c) 2018, BuyerDocs, LLC.
 * All rights reserved.
 */

namespace Tests\Browser;

use App\PostCloseClient;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;

use App\Buyer;
use App\User;

class documentsTest extends DuskTestCase
{
    /**
     * @group documents
     * @throws \Throwable
     * @return void
     */
    public function testClientWithoutDocuments()
    {
        $this->browse(function (Browser $browser) {

            // Add client as admin
            $admin = User::where([
                ['name', 'Test User'],
                ['email', 'test@test.test'],
            ])->firstOrFail();

            $oldAllowsDocuments = $admin->account->allows_buyer_documents;
            $admin->account->allows_buyer_documents = true;
            $admin->account->save();

            $browser->loginAs($admin)
                ->visit(route('userHome'))
                ->waitForText('ADD CLIENTS')
                ->assertSee('ADD CLIENTS');

            // Add client2
            $browser->type('email[0]', 'duskTestWithoutDocuments@autotest.com')
                ->type('refnum', 'File 1234 from dusk without documents')
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
            $browser->waitForText('Success!', 15)
                ->click('#toast-container')
                ->waitUntilMissing('#toast-container')
                ->assertDontSee('Success!');

            // Ensure buyer login works
            $buyer = Buyer::with('buyerFiles')->where('email', 'duskTestWithoutDocuments@autotest.com')->firstOrFail();
            $buyerFile = $buyer->buyerFiles->first();
            self::assertEquals($buyerFile->documents->count(), 0);

            $this->loginBuyer($buyer, $browser);
            $browser->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                ->assertDontSee('Documents received:');
            $this->logoutBuyer($browser);

            $admin->account->allows_buyer_documents = $oldAllowsDocuments;
            $admin->account->save();

            $buyer = $buyer->fresh();
            foreach ($buyer->buyerFiles as $buyerFile) {
                $buyerFile->delete();
            }
        });
    }
    
    /**
     * @group documents
     * @throws \Throwable
     * @return void
     */
    public function testClientWithDocuments()
    {
        $this->browse(function (Browser $browser) {
            $localDocumentName = 'encrypted_email_white_paper.pdf';
            $localDocumentPath = public_path('assets/docs/' . $localDocumentName);

            $admin = User::where([
                ['name', 'Test User'],
                ['email', 'test@test.test'],
            ])->firstOrFail();

            $oldAllowsDocuments = $admin->account->allows_buyer_documents;
            $admin->account->allows_buyer_documents = true;
            $admin->account->save();

            $browser->loginAs($admin)
                ->visit(route('userHome'))
                ->waitForText('ADD CLIENTS')
                ->assertSee('ADD CLIENTS');

            // Add client2
            $browser->type('email[0]', 'duskTestWithDocuments@autotest.com')
                ->type('refnum', 'File 1234 from dusk with notes')
                ->select('company', '1')
                ->select('branch', '1')
                ->select('wireDoc', '9')
                ->attach('.dz-hidden-input', $localDocumentPath);

            $browser->element('#openAddModal')->getLocationOnScreenOnceScrolledIntoView();

            $browser->pause(1000)
                ->click('#openAddModal')
                ->waitForText('Confirm')  // check modal
                ->assertSee('Wiring Instructions Sample.pdf')
                ->assertSee($localDocumentName);

            // Click in modal to add client
            $browser->waitFor('#addModal.modal.fade.show')
                ->pause(250)                // allow modal to open
                ->waitForText('Confirm')
                ->press('Confirm');

            // Dismiss toastr notification
            $browser->waitForText('Success!', 15)
                ->click('#toast-container')
                ->waitUntilMissing('#toast-container')
                ->assertDontSee('Success!');

            // Ensure buyer login works
            $buyer = Buyer::with('buyerFiles')->where('email', 'duskTestWithDocuments@autotest.com')->firstOrFail();
            $buyerFile = $buyer->buyerFiles->first();
            self::assertEquals($buyerFile->documents->count(), 1);

            $this->loginBuyer($buyer, $browser);
            $browser->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                ->assertSee('Documents received:')
                ->assertSee($localDocumentName);
            $this->logoutBuyer($browser);

            $admin->account->allows_buyer_documents = $oldAllowsDocuments;
            $admin->account->save();

            $buyer = $buyer->fresh();
            foreach ($buyer->buyerFiles as $buyerFile) {
                $buyerFile->delete();
            }
        });
    }

    /**
     * @group documents
     * @throws \Throwable
     * @return void
     */
    public function testPostCloseWithoutDocuments()
    {
        $this->browse(function (Browser $browser) {

            // Add client as admin
            $admin = User::where([
                ['name', 'Test User'],
                ['email', 'test@test.test'],
            ])->firstOrFail();

            $oldAllowsDocuments = $admin->account->allows_buyer_documents;
            $oldPostClose = $admin->account->allows_post_close;
            $admin->account->allows_buyer_documents = true;
            $admin->account->allows_post_close = true;
            $admin->account->save();

            $title = $admin->titles()->first();
            $branch = $title->branches()->first();
            $title->post_close_subdomain = env('GENERIC_SUBDOMAIN');
            $branch->post_close_support_phone = '+15005550006';
            $branch->post_close_support_email = 'postcloseemail@test.test';
            $branch->post_close_support_website = 'not.a.real.website.com';
            $branch->save();
            $title->save();

            $browser->loginAs($admin)
                ->visit(route('userPostCloseHome'))
                ->waitForText('ADD POST-CLOSE CLIENTS')
                ->assertSee('ADD POST-CLOSE CLIENTS');

            // Add client2
            $browser->type('email', 'duskTestPostCloseWithoutDocuments@autotest.com')
                ->type('refnum', 'File 1234 from dusk post close')
                ->select('company', '1')
                ->select('branch', '1');

            $browser->element('#openAddModal')->getLocationOnScreenOnceScrolledIntoView();

            $browser->pause(1000)
                ->click('#openAddModal')
                ->waitForText('Confirm');

            // Click in modal to add client
            $browser->waitFor('#addModal.modal.fade.show')
                ->pause(250)                // allow modal to open
                ->waitForText('Confirm')
                ->press('Confirm');

            // Dismiss toastr notification
            $browser->waitForText('Success!', 15)
                ->click('#toast-container')
                ->waitUntilMissing('#toast-container')
                ->assertDontSee('Success!');

            // Ensure post close login works
            $client = PostCloseClient::with('postCloseFiles')->where('email', 'duskTestPostCloseWithoutDocuments@autotest.com')->firstOrFail();
            $file = $client->postCloseFiles->first();
            self::assertEquals($file->documents->count(), 0);

            $this->loginPostClose($client, $browser);
            $this->logoutPostClose($browser);

            $client = $client->fresh();
            foreach ($client->postCloseFiles as $postCloseFile) {
                $postCloseFile->delete();
            }
            $client->delete();

            $admin->account->allows_post_close = $oldPostClose;
            $admin->account->allows_buyer_documents = $oldAllowsDocuments;
            $admin->account->save();
        });
    }

    /**
     * @group documents
     * @throws \Throwable
     * @return void
     */
    public function testPostCloseWithDocuments()
    {
        $this->browse(function (Browser $browser) {
            $localDocumentName = 'encrypted_email_white_paper.pdf';
            $localDocumentPath = public_path('assets/docs/' . $localDocumentName);

            $admin = User::where([
                ['name', 'Test User'],
                ['email', 'test@test.test'],
            ])->firstOrFail();

            $oldAllowsDocuments = $admin->account->allows_buyer_documents;
            $oldPostClose = $admin->account->allows_post_close;
            $admin->account->allows_buyer_documents = true;
            $admin->account->allows_post_close = true;
            $admin->account->save();

            $title = $admin->titles()->first();
            $branch = $title->branches()->first();
            $title->post_close_subdomain = env('GENERIC_SUBDOMAIN');
            $branch->post_close_support_phone = '+15005550006';
            $branch->post_close_support_email = 'postcloseemail@test.test';
            $branch->post_close_support_website = 'not.a.real.website.com';
            $branch->save();
            $title->save();


            $browser->loginAs($admin)
                ->visit(route('userPostCloseHome'))
                ->waitForText('ADD POST-CLOSE CLIENTS')
                ->assertSee('ADD POST-CLOSE CLIENTS');

            // Add client2
            $browser->type('email', 'duskTestPostCloseWithDocuments@autotest.com')
                ->type('refnum', 'File 1234 from dusk post close')
                ->select('company', $title->id)
                ->select('branch', $branch->id)
                ->attach('.dz-hidden-input', $localDocumentPath);

            $browser->element('#openAddModal')->getLocationOnScreenOnceScrolledIntoView();

            $browser->pause(1000)
                ->click('#openAddModal')
                ->waitForText('Confirm')
                ->assertSee($localDocumentName);

            // Click in modal to add client
            $browser->waitFor('#addModal.modal.fade.show')
                ->pause(250)                // allow modal to open
                ->waitForText('Confirm')
                ->press('Confirm');

            // Dismiss toastr notification
            $browser->waitForText('Success!', 15)
                ->click('#toast-container')
                ->waitUntilMissing('#toast-container')
                ->assertDontSee('Success!');

            // Ensure post close login works
            $client = PostCloseClient::with('postCloseFiles')->where('email', 'duskTestPostCloseWithDocuments@autotest.com')->firstOrFail();
            $file = $client->postCloseFiles->first();
            self::assertEquals($file->documents->count(), 1);

            $this->loginPostClose($client, $browser);
            $browser->visit(route('postCloseClientHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                ->assertSee('Thank you for trusting ' . $client->title->name . ' with your closing!')
                ->assertSee('Documents:')
                ->assertSee($localDocumentName);
            $this->logoutPostClose($browser);

            $client = $client->fresh();
            foreach ($client->postCloseFiles as $postCloseFile) {
                $postCloseFile->delete();
            }
            $client->delete();

            $admin->account->allows_post_close = $oldPostClose;
            $admin->account->allows_buyer_documents = $oldAllowsDocuments;
            $admin->account->save();
        });
    }
}
