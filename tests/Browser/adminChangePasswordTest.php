<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class adminChangePasswordTest extends DuskTestCase
{
    /**
     * @group user
     * @throws
     * @return void
     */
    public function testChangePassword()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(route('login'))
                    ->waitForText('ADMIN LOGIN')
                    ->assertSee('ADMIN LOGIN');

            // Ensure correct credentials work
            $browser->type('email', 'test@test.test')
                    ->type('password', 'currentPassword1')
                    ->press('Login')
                    ->waitForText('ADD CLIENTS')
                    ->assertSee('ADD CLIENTS');

            $browser->visit(route('settings'))
                    ->waitForText('UPDATE PASSWORD')
                    ->assertSee('UPDATE PASSWORD');

            $browser->element('#form-change-password > div.col-md-12 > div > button')->getLocationOnScreenOnceScrolledIntoView();

            $browser->pause(1000)
                    ->type('currentPassword', 'currentPassword1')
                    ->type('newPassword', 'newPassword1')
                    ->type('newPassword_confirmation', 'newPassword1')
                    ->press('#form-change-password > div.col-md-12 > div > button');

            // Wait for toastr notification
            $browser->waitForText('Password updated');
            // Dismiss toastr notification
            $browser->click('#toast-container')
                    ->waitUntilMissing('#toast-container')
                    ->assertDontSee('Password updated');

            // Ensure still logged in
            $browser->visit(route('userHome'))
                    ->waitForText('ADD CLIENTS')
                    ->assertSee('ADD CLIENTS');

            // Logout
            $browser->clickLink('Logout')
                    ->waitForText('ADMIN LOGIN');


            // Test new password
            $browser->visit(route('login'))
                ->waitForText('ADMIN LOGIN')
                ->assertSee('ADMIN LOGIN');

            // Ensure wrong credentials fail
            $browser->type('email', 'test@test.test')
                ->type('password', 'currentPassword1')
                ->press('Login')
                ->waitForText('These credentials do not match our records.');

            $browser->type('password', 'newPassword1')
                ->press('Login')
                ->waitForText('ADD CLIENTS')
                ->assertSee('ADD CLIENTS');

            $browser->visit(route('settings'))
                ->waitForText('UPDATE PASSWORD')
                ->assertSee('UPDATE PASSWORD');

            // Reset password to original
            $browser->element('#form-change-password > div.col-md-12 > div > button')->getLocationOnScreenOnceScrolledIntoView();

            $browser->pause(1000)
                ->type('currentPassword', 'newPassword1')
                ->type('newPassword', 'currentPassword1')
                ->type('newPassword_confirmation', 'currentPassword1')
                ->press('#form-change-password > div.col-md-12 > div > button');

            // Wait for toastr notification
            $browser->waitForText('Password updated');
            // Dismiss toastr notification
            $browser->click('#toast-container')
                ->waitUntilMissing('#toast-container')
                ->assertDontSee('Password updated');

            // Ensure still logged in
            $browser->visit(route('userHome'))
                ->waitForText('ADD CLIENTS')
                ->assertSee('ADD CLIENTS');

            // Logout
            $browser->clickLink('Logout')
                ->waitForText('ADMIN LOGIN');
        });
    }
}
