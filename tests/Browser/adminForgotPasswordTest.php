<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

use App\User;

class adminForgotPasswordTest extends DuskTestCase
{
    /**
     * @group user
     *
     * @return void
     */
    public function testResetPassword()
    {
        $user = User::where('email', 'test@test.test')->first();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit(route('login'))
                ->clickLink('Reset Password')
                ->waitForText('RESET PASSWORD')
                ->type('email', $user->email)
                ->press('Submit Reset')
                ->waitForText('We have e-mailed your password reset link!')
                ->assertSee('We have e-mailed your password reset link!');
        });

        // TODO: Check email was sent

        \DB::table('password_resets')->where('email', $user->email)->first();

        $user = User::where('email', 'test@test.test')->first();

        $token = '3ea19db508df456fa48fe3a670abd790a34749c05956d308be792d4b1ca16cf6';
        \DB::table('password_resets')->where('email', $user->email)->update(['token' => bcrypt($token)]);
        $this->browse(function (Browser $browser) use ($user, $token) {
            $browser->visit(route('password.reset', ['token' => $token]))
                 ->waitForText('RESET PASSWORD')
                 ->type('email', $user->email)
                 ->type('password', 'currentPassword1')
                 ->type('password_confirmation', 'currentPassword1')
                 ->press('Reset Password')
                 ->waitForText('ADD CLIENTS');

            // Wait for toastr notification
            $browser->waitfortext('Password updated');
            // Dismiss toastr notification
            $browser->click('#toast-container')
                    ->pause(1500)
                    ->assertDontSee('Password updated');
        });
    }
}
