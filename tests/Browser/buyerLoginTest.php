<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

use App\Buyer;

class buyerLoginTest extends DuskTestCase
{
    /**
     * @group buyer
     * @throws
     * @return void
     */
    public function testLogin()
    {
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

            // Ensure we are still here (because terms is unchecked)
            $browser->type('email', 'buyer@test.test')
                    ->select('title_id', '1')
                    ->press('Login')
                    ->waitForText('BUYERS / LENDERS');

            // Ensure we can login
            $browser->click('.custom-checkbox')
                    ->press('Login')
                    ->waitForText('WARNING');
        
            // Click in modal to accept warning
            $browser->waitFor('#warnModal.modal.fade.show')
                    ->pause(500)                // allow modal to open
                    ->waitForText('Accept')
                    ->press('Accept')
                    ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                    ->assertHasCookie(\Auth::guard('buyer')->getRecallerName())
                    ->assertSee('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS');

            $buyer->title->account->allows_buyer_phone = $oldAllowsPhone;
            $buyer->title->account->requires_buyer_passcode = $oldRequiresPasscode;
            $buyer->title->account->save();
        });
    }


    /**
     * @group buyer
     *
     * @return void
     */
    public function testPersistantLoginViaSession()
    {
        $this->browse(function (Browser $browser) {
            // Ensure we aren't signing in via recaller token cookie
            $cookie_name = \Auth::guard('buyer')->getRecallerName();
            $browser->assertHasCookie($cookie_name);
            $browser->deleteCookie($cookie_name);
            $browser->assertCookieMissing($cookie_name);

            $browser->visit('/')
                ->waitForText('Securing Wire Transfers for Real Estate');

            // Ensure we are already logged in
            $browser->clickLink('Get Started')
                    ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
                    ->assertSee('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS');
        });
    }

    /**
     * @group buyer
     *
     * @return void
     */
    public function testDownload()
    {
        // Could not get download to work without using actingAs (would always get a redirect to login even when logged in previous/next tests)
        $buyer = Buyer::find(1);
        // File download example
        $response = $this->actingAs($buyer, 'buyer')->get(route('buyerDownloadWire', ['subdomain' => env('GENERIC_SUBDOMAIN'), 'id' => 1]));
        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename="Wiring Instructions Sample.pdf"');
    }

    /**
     * @group buyer
     *
     * @return void
     */
    public function testLogout()
    {
        $this->browse(function (Browser $browser) {
            // Logout
            $browser->clickLink('Logout')
                    ->waitForText('LOGIN')
                    ->assertSee('LOGIN');
        });
    }
}
