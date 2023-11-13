<?php
/**
 * Copyright (c) 2018, BuyerDocs, LLC.
 * All rights reserved.
 */

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;

use App\PostCloseClient;
use App\User;

class postCloseTest extends DuskTestCase
{
    /**
     * @group postClose
     * @throws \Throwable
     * @return void
     */
    public function testPostCloseNotAllowed()
    {
        $this->browse(function (Browser $browser) {
            $admin = User::where([
                ['name', 'Test User'],
                ['email', 'test@test.test'],
            ])->firstOrFail();

            $oldPostClose = $admin->account->allows_post_close;
            $admin->account->allows_post_close = false;
            $admin->account->save();

            $browser->loginAs($admin)
                ->visit(route('userPostCloseHome'))
                ->waitForText('Account not activated')
                ->assertSee('Account not activated')
                ->assertSee('Sorry, your account is not setup to use the post-close solution.')
                ->assertSee('To activate your account, please contact an Admin for your account.');

            $admin->account->allows_post_close = $oldPostClose;
            $admin->account->save();
        });
    }
    
    /**
     * @group postClose
     * @throws \Throwable
     * @return void
     */
    public function testPostCloseAllowed()
    {
        $this->browse(function (Browser $browser) {
            $admin = User::where([
                ['name', 'Test User'],
                ['email', 'test@test.test'],
            ])->firstOrFail();

            $oldPostClose = $admin->account->allows_post_close;
            $admin->account->allows_post_close = true;
            $admin->account->save();

            $title = $admin->titles()->first();
            $branch = $title->branches()->first();
            $title->post_close_subdomain = null;
            $branch->post_close_support_phone = null;
            $branch->post_close_support_email = null;
            $branch->post_close_support_website = null;
            $branch->save();
            $title->save();

            $browser->loginAs($admin)
                ->visit(route('userPostCloseHome'))
                ->waitForText('ADD POST-CLOSE CLIENTS')
                ->assertSee('ADD POST-CLOSE CLIENTS');

            // Add client2
            $browser->type('email', 'duskTestPostClose@autotest.com')
                ->type('refnum', 'File 1234 from dusk post close')
                ->select('company', '1')
                ->select('branch', '1');

            $browser->element('#openAddModal')->getLocationOnScreenOnceScrolledIntoView();

            // check for company information consistency
            $browser->pause(1000)
                ->click('#openAddModal')
                ->waitFor('#addModal.modal.fade.show')
                ->waitForText('Confirm')
                ->press('Confirm')
                ->waitForText('This company is missing information required for post-close. Please contact ' . env('MAIL_SUPPORT_ADDRESS'))
                ->assertSee('This company is missing information required for post-close. Please contact ' . env('MAIL_SUPPORT_ADDRESS'));

            $title->post_close_subdomain = env('GENERIC_SUBDOMAIN');
            $title->save();

            // check for branch information consistency
            $browser->pause(1000)
                ->click('#openAddModal')
                ->waitFor('#addModal.modal.fade.show')
                ->waitForText('Confirm')
                ->press('Confirm')
                ->waitForText('This branch is missing information required for post-close. Please contact ' . env('MAIL_SUPPORT_ADDRESS'))
                ->assertSee('This branch is missing information required for post-close. Please contact ' . env('MAIL_SUPPORT_ADDRESS'));

            $branch->post_close_support_phone = '+15005550006';
            $branch->post_close_support_email = 'postcloseemail@test.test';
            $branch->post_close_support_website = 'not.a.real.website.com';
            $branch->save();

            $browser->visit(route('userPostCloseHome'))
                ->waitForText('ADD POST-CLOSE CLIENTS')
                ->assertSee('ADD POST-CLOSE CLIENTS');

            // Add client2
            $browser->type('email', 'duskTestPostClose@autotest.com')
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
            $client = PostCloseClient::with('postCloseFiles')->where('email', 'duskTestPostClose@autotest.com')->firstOrFail();
            $this->loginPostClose($client, $browser);
            $this->logoutPostClose($browser);

            $client = $client->fresh();
            foreach ($client->postCloseFiles as $postCloseFile) {
                $postCloseFile->delete();
            }
            $client->delete();

            $admin->account->allows_post_close = $oldPostClose;
            $admin->account->save();
        });
    }
}
