<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

use App\User;
use App\Buyer;

class multipleGuardsTest extends DuskTestCase
{
    /**
     * @throws \Throwable
     */
    public function testBuyerLogin()
    {
        $this->clearCache();

        $this->browse(function (Browser $browser) {

            $buyer = Buyer::where(['email' => 'buyer@test.test'])->firstOrFail();
            $oldAllowsPhone = $buyer->title->account->allows_buyer_phone;
            $oldRequiresPasscode = $buyer->title->account->requires_buyer_passcode;

            $buyer->title->account->forceFill([
                'allows_buyer_phone' => true,
                'requires_buyer_passcode' => false
            ])->save();

            $browser->visit(route('buyerLogin', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                ->waitForText('BUYERS / LENDERS');

            $browser->type('email', 'buyer@test.test')
                ->select('title_id', '1')
                ->click('.custom-checkbox')
                ->press('Login')
                ->waitForText('BUYERS / LENDERS');

            // Click in modal to accept warning
            $browser->waitFor('#warnModal.modal.fade.show')
                ->pause(500)                // allow modal to open
                ->waitForText('Accept')
                ->press('Accept')
                ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                ->assertSee('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS');

            $buyer->title->account->allows_buyer_phone = $oldAllowsPhone;
            $buyer->title->account->requires_buyer_passcode = $oldRequiresPasscode;
            $buyer->title->account->save();
        });
    }

    /**
     * @throws \Throwable
     */
    public function testBuyerLogout()
    {
        $this->clearCache();

        $this->browse(function (Browser $browser) {
            $browser->visit(route('buyerLogin', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                ->assertSee('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS');

            // Logout
            $browser->clickLink('Logout')
                ->waitForText('LOGIN')
                ->assertSee('LOGIN');
        });
    }

    /**
     * @group multipleGuards
     * @throws
     * @return void
     */
    public function testLoginsLogouts()
    {
        $this->clearCache();

        $this->browse(function (Browser $browser) {
            // Ensure nobody is logged in
            $browser->visit(route('userHome'))
                    ->waitForText('ADMIN LOGIN')
                    ->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                    ->waitForText('BUYERS / LENDERS');

            $admin = User::find(1);

            // Only admin logged in
            $browser->loginAs($admin, 'web')
                    ->visit(route('userHome'))
                    ->waitForText('ADD CLIENTS')
                    ->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                    ->waitForText('BUYERS / LENDERS')
                    ->assertSee('BUYERS / LENDERS');
            $browser->logout('web')
                    ->visit(route('userHome'))
                    ->waitForText('ADMIN LOGIN');

            $this->clearCache();

            // Only buyer logged in
            $this->testBuyerLogin();

            $browser->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                    ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                    ->visit(route('userHome'))
                    ->waitForText('ADMIN LOGIN')
                    ->assertSee('ADMIN LOGIN');

            $this->testBuyerLogout();
            $browser->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                    ->waitForText('BUYERS / LENDERS');

            $this->clearCache();

            // admin logs in, buyer logs in, admin logs out, buyer logs out
            // login admin, buyer
            $browser->loginAs($admin, 'web')
                    ->visit(route('userHome'))
                    ->waitForText('ADD CLIENTS');

            $this->testBuyerLogin();

            $browser->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                    ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS');
            // ensure both are still logged in
            $browser->visit(route('userHome'))
                    ->waitForText('ADD CLIENTS')
                    ->assertSee('ADD CLIENTS');
            $browser->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                    ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                    ->assertSee('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS');
            // logout admin but ensure buyer is logged in
            $browser->logout('web')
                    ->visit(route('userHome'))
                    ->waitForText('ADMIN LOGIN')
                    ->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                    ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                    ->assertSee('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS');
            // logout buyer and ensure admin is still logged out
            $this->testBuyerLogout();
            $browser->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                    ->waitForText('BUYERS / LENDERS')
                    ->visit(route('userHome'))
                    ->waitForText('ADMIN LOGIN')
                    ->assertSee('ADMIN LOGIN');

            $this->clearCache();


            // admin logs in, buyer logs in, buyer logs out, admin logs out
            // login admin, buyer
            $browser->loginAs($admin, 'web')
                    ->visit(route('userHome'))
                    ->waitForText('ADD CLIENTS');

            $this->testBuyerLogin();

            $browser->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                    ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS');
            // ensure both are still logged in
            $browser->visit(route('userHome'))
                    ->waitForText('ADD CLIENTS')
                    ->assertSee('ADD CLIENTS');
            $browser->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                    ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                    ->assertSee('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS');
            // logout buyer and ensure admin is logged in
            $this->testBuyerLogout();
            $browser->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                    ->waitForText('BUYERS / LENDERS')
                    ->visit(route('userHome'))
                    ->waitForText('ADD CLIENTS')
                    ->assertSee('ADD CLIENTS');
            // logout admin and ensure buyer is still logged out
            $browser->logout('web')
                    ->visit(route('userHome'))
                    ->waitForText('ADMIN LOGIN')
                    ->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                    ->waitForText('BUYERS / LENDERS')
                    ->assertSee('BUYERS / LENDERS');

            $this->clearCache();


            // buyer logs in, admin logs in, buyer logs out, admin logs out
            // login buyer, admin
            $this->testBuyerLogin();
            $browser->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                    ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                    ->loginAs($admin, 'web')
                    ->visit(route('userHome'))
                    ->waitForText('ADD CLIENTS');

            $this->clearCache();

            // ensure both are still logged in
            $browser->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                    ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                    ->assertSee('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS');
            $browser->visit(route('userHome'))
                    ->waitForText('ADD CLIENTS')
                    ->assertSee('ADD CLIENTS');

            $this->clearCache();

            // logout buyer and ensure admin is logged in
            $this->testBuyerLogout();
            $browser->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                    ->waitForText('BUYERS / LENDERS')
                    ->visit(route('userHome'))
                    ->waitForText('ADD CLIENTS')
                    ->assertSee('ADD CLIENTS');

            $this->clearCache();

            // logout admin and ensure buyer is still logged out
            $browser->logout('web')
                    ->visit(route('userHome'))
                    ->waitForText('ADMIN LOGIN')
                    ->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                    ->waitForText('BUYERS / LENDERS')
                    ->assertSee('BUYERS / LENDERS');

            $this->clearCache();

            // buyer logs in, admin logs in, admin logs out, buyer logs out
            // login buyer, admin
            $this->testBuyerLogin();
            $browser->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                    ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                    ->loginAs($admin, 'web')
                    ->visit(route('userHome'))
                    ->waitForText('ADD CLIENTS');

            $this->clearCache();

            // ensure both are still logged in
            $browser->visit(route('userHome'))
                    ->waitForText('ADD CLIENTS')
                    ->assertSee('ADD CLIENTS');
            $browser->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                    ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                    ->assertSee('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS');

            $this->clearCache();

            // logout admin but ensure buyer is logged in
            $browser->logout('web')
                    ->visit(route('userHome'))
                    ->waitForText('ADMIN LOGIN')
                    ->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                    ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                    ->assertSee('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS');
            // logout buyer and ensure admin is still logged out
            $this->testBuyerLogout();
            $browser->visit(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                    ->waitForText('BUYERS / LENDERS')
                    ->visit(route('userHome'))
                    ->waitForText('ADMIN LOGIN')
                    ->assertSee('ADMIN LOGIN');
        });
        $this->clearCache();
    }
}
