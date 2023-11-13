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

class propertyAddressTest extends DuskTestCase
{
    /**
     * @group propertyAddress
     * @throws \Throwable
     * @return void
     */
    public function testClientWithPropertyAddress()
    {
        $this->browse(function (Browser $browser) {
            $address = '555 Acme Street, Austin, TX 78759';

            // Add client as admin
            $admin = User::where([
                ['name', 'Test User'],
                ['email', 'test@test.test'],
            ])->firstOrFail();

            $browser->loginAs($admin)
                ->visit(route('userHome'))
                ->waitForText('ADD CLIENTS')
                ->assertSee('ADD CLIENTS');

            // Add client2
            $browser->type('email[0]', 'duskBuyerTestWithPropertyAddress@autotest.com')
                ->type('refnum', 'File 1234 from dusk without notes')
                ->select('company', '1')
                ->select('branch', '1')
                ->select('wireDoc', '9')
                ->type('address', $address);

            $browser->element('#openAddModal')->getLocationOnScreenOnceScrolledIntoView();

            $browser->pause(1000)
                ->click('#openAddModal')
                ->waitForText('Confirm')  // check modal
                ->assertSee('Wiring Instructions Sample.pdf')
                ->assertSee($address);

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

            $browser->element('#buyerTable')->getLocationOnScreenOnceScrolledIntoView();

            $buyer = Buyer::with('buyerFiles')->where('email', 'duskBuyerTestWithPropertyAddress@autotest.com')->firstOrFail();
            $buyerFile = $buyer->buyerFiles->first();
            self::assertNotNull($buyerFile->address);
            self::assertNotNull($buyerFile->street_view_url);

            $browser->pause(1000)
                ->assertSee($buyer->email)
                ->click('#streetview-' . $buyer->id)
                ->waitFor('#streetViewModal.modal.fade.show')
                ->pause(250) // allow modal to open
                ->assertSee($buyerFile->address);
            $imageSrc = $browser->attribute('#street_view_image', 'src');
            self::assertEquals($buyerFile->street_view_url, $imageSrc);

            $this->loginBuyer($buyer, $browser);
            $browser->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                ->assertSee($buyerFile->address);
            $imageSrc = $browser->attribute('#street_view_image', 'src');
            self::assertEquals($buyerFile->street_view_url, $imageSrc);
            $this->logoutBuyer($browser);

            $buyer = $buyer->fresh();
            foreach ($buyer->buyerFiles as $buyerFile) {
                $buyerFile->delete();
            }
        });
    }
    
    /**
     * @group propertyAddress
     * @throws \Throwable
     * @return void
     */
    public function testPostCloseWithPropertyAddress()
    {
        $this->browse(function (Browser $browser) {
            $address = '555 Acme Street, Austin, TX 78759';

            $admin = User::where([
                ['name', 'Test User'],
                ['email', 'test@test.test'],
            ])->firstOrFail();

            $oldPostClose = $admin->account->allows_post_close;
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
            $browser->type('email', 'duskPostCloseTestWithPropertyAddress@autotest.com')
                ->type('refnum', 'File 1234 from dusk post close')
                ->select('company', '1')
                ->select('branch', '1')
                ->type('address', $address);

            $browser->element('#openAddModal')->getLocationOnScreenOnceScrolledIntoView();

            $browser->pause(1000)
                ->click('#openAddModal')
                ->waitForText('Confirm')
                ->assertSee($address);

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

            $browser->element('#clientTable')->getLocationOnScreenOnceScrolledIntoView();

            // Ensure post close login works
            $client = PostCloseClient::with('postCloseFiles')->where('email', 'duskPostCloseTestWithPropertyAddress@autotest.com')->firstOrFail();
            $file = $client->postCloseFiles->first();
            self::assertNotNull($file->address);
            self::assertNotNull($file->street_view_url);

            $browser->pause(1000)
                ->assertSee($client->email)
                ->click('#streetview-' . $client->id)
                ->waitFor('#streetViewModal.modal.fade.show')
                ->pause(250) // allow modal to open
                ->assertSee($file->address);
            $imageSrc = $browser->attribute('#street_view_image', 'src');
            self::assertEquals($file->street_view_url, $imageSrc);

            $this->loginPostClose($client, $browser);
            $browser->visit(route('postCloseClientHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                ->assertSee('Thank you for trusting ' . $client->title->name . ' with your closing!')
                ->assertSee($file->address);
            $imageSrc = $browser->attribute('#street_view_image', 'src');
            self::assertEquals($file->street_view_url, $imageSrc);
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
