<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

use App\Seller;

class sellerLoginTest extends DuskTestCase
{
    /**
     * @group seller
     * @throws
     * @return void
     */
    public function testLogin()
    {
        $this->browse(function (Browser $browser) {
            $seller = Seller::where(['email' => 'seller@test.test'])->firstOrFail();
            $oldAllowsPhone = $seller->title->account->allows_seller_phone;
            $oldRequiresPasscode = $seller->title->account->requires_seller_passcode;

            $seller->title->account->forceFill([
                'allows_seller_phone' => true,
                'requires_seller_passcode' => false
            ])->save();

            $browser->visit(route('sellerLogin', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                    ->waitForText('CLIENT LOGIN');

            $browser->type('email', $seller->email)
                    ->type('accessCode', $seller->token)
                    ->press('Login')
                    ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                    ->assertSee('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                    ->assertSee($seller->email)
                    ->assertSee($seller->sellerFiles->first()->ref_number)
                    ->assertSee('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS');

            $seller->title->account->allows_seller_phone = $oldAllowsPhone;
            $seller->title->account->requires_seller_passcode = $oldRequiresPasscode;
            $seller->title->account->save();
        });
    }

    /**
     * @group seller
     *
     * @return void
     */
    public function testLogout()
    {
        $this->browse(function (Browser $browser) {
            // Logout
            $browser->clickLink('Logout')
                    ->waitForText('LOGIN')
                    ->assertSee('CLIENT LOGIN');
        });
    }
}
