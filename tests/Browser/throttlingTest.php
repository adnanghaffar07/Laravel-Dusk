<?php
/**
 * Copyright (c) 2018, BuyerDocs, LLC.
 * All rights reserved.
 */

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class throttlingTest extends DuskTestCase
{

    /**
     * @group throttle
     * @throws \Throwable
     * @return void
     */
    public function testAdminLogin()
    {
        $this->clearCache();

        $this->browse(function (Browser $browser) {
            $browser->visit(route('login'))
                    ->waitForText('ADMIN LOGIN')
                    ->assertSee('ADMIN LOGIN');


            for ($i = 0; $i < 5; $i++) {
                // Ensure wrong credentials fail
                $browser->type('email', 'test@test.test')
                        ->type('password', 'password')
                        ->press('Login')
                        ->waitForText('These credentials do not match our records.');
            }

            // Throttled
            $browser->type('email', 'test@test.test')
                    ->type('password', 'password')
                    ->press('Login')
                    ->waitForText('Too many login attempts.')
                    ->assertSee('Too many login attempts.');

            // Ensure correct credentials don't work (throttled)
            $browser->type('password', 'currentPassword1')
                    ->press('Login')
                    ->waitForText('Too many login attempts.')
                    ->assertSee('Too many login attempts.');
        });

        $this->clearCache();
    }

    /**
     * @group throttle
     * @throws \Throwable
     * @return void
     */
    public function testBuyerLogin()
    {
        $this->clearCache();

        $this->browse(function (Browser $browser) {
            $browser->visit(route('buyerLogin', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
                    ->waitForText('BUYERS / LENDERS')
                    ->assertSee('BUYERS / LENDERS');

            $browser->type('email', 'notarealemail@buyerdocs.com')
                    ->select('title_id', '1')
                    ->click('.custom-checkbox');

            for ($i = 0; $i < 15; $i++) {
                // Ensure wrong credentials fail
                $browser->press('Login')
                        ->waitForText('If you verified your credentials and no information was found, please contact your closing company directly.');
                $browser->pause(500);
            }

            // Throttled
            $browser->press('Login')
                    ->waitForText('Too many login attempts.')
                    ->assertSee('Too many login attempts.');
        });

        $this->clearCache();
    }
}
