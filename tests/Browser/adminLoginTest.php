<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class adminLoginTest extends DuskTestCase
{
    /**
     * @group admin
     * @throws
     * @return void
     */
    public function testLogin()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(route('login'))
                    ->waitForText('ADMIN LOGIN')
                    ->assertSee('ADMIN LOGIN');

            // Ensure wrong credentials fail
            $browser->type('email', 'test@test.test')
                    ->type('password', 'password')
                    ->press('Login')
                    ->waitForText('These credentials do not match our records.');

            // Ensure correct credentials work
            $browser->type('password', 'currentPassword1')
                    ->press('Login')
                    ->waitForText('ADD CLIENTS')
                    ->assertSee('ADD CLIENTS');

            // Logout
            $browser->clickLink('Logout')
                    ->waitForText('ADMIN LOGIN')
                    ->assertSee('ADMIN LOGIN');
        });
    }
}
