<?php

namespace Tests\Feature\Controllers\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Account;
use App\Title;
use App\User;
use Hash;
use Notification;

/**
 * Class ResetPasswordController
 * @package Tests\Feature\Controllers\Auth
 */
class ResetPasswordController extends TestCase
{
    use WithFaker, RefreshDatabase;

    /**
     * @var
     */
    protected $account;

    /**
     * @var
     */
    protected $title;

    /**
     * @var
     */
    protected $user;

    /**
     * Setup the test environment.
     *
     * @throws \Throwable
     */
    public function setUp()
    {
        parent::setUp();

        // Create fake account
        $this->account = new Account();
        $this->account->name = $this->faker->name;
        $this->account->saveOrFail();

        // Create title
        $this->title = factory(Title::class)->create([
            'account_id' => $this->account->id,
            'subdomain' => $this->faker->word
        ]);

        $this->user = factory(User::class)->create([
            'password' => Hash::make('1234567'),
            'role' => 'User',
            'title_id' => $this->title->id
        ]);
    }

    /**
     *  Clean up the testing environment before the next test.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Test showResetForm method
     * Behaviour: success
     */
    public function test_show_reset_form_method_success()
    {
        Notification::fake();

        // Create token and send it to user
        $this->post(route('password.email'), [
            'email' => $this->user->email,
            'g-recaptcha-response' => 'withtestkeysthisalwaysvalid'
        ]);

        // Get token from notification
        $token = '';
        Notification::assertSentTo(
            $this->user,
            \Illuminate\Auth\Notifications\ResetPassword::class,
            function ($notification, $channels) use (&$token) {
                $token = $notification->token;

                return true;
            }
        );

        // Load test route
        $response = $this->get(route('password.reset', ['token' => $token]));
        $response->assertStatus(200);
        $response->assertSee('Reset Password');
        $response->assertSee('CONFIRM NEW PASSWORD');
    }

    /**
     * Test reset method
     * Behaviour: success
     */
    public function test_reset_method_success()
    {
        Notification::fake();

        // Create token and send it to user
        $this->post(route('password.email'), [
            'email' => $this->user->email,
            'g-recaptcha-response' => 'withtestkeysthisalwaysvalid'
        ]);

        // Get token from notification
        $token = '';
        Notification::assertSentTo(
            $this->user,
            \Illuminate\Auth\Notifications\ResetPassword::class,
            function ($notification, $channels) use (&$token) {
                $token = $notification->token;

                return true;
            }
        );

        // Load test route
        $response = $this->post(url('password/reset'), [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'newPassword#1',
            'password_confirmation' => 'newPassword#1',
            'g-recaptcha-response' => 'withtestkeysthisalwaysvalid',
        ]);
        $response->assertStatus(302);
        $response->assertSessionHas('status', 'Your password has been reset!');
        $response->assertSessionHas('toastrSuccessTitle', 'Success!');
        $response->assertSessionHas('toastrSuccessMessage', 'Password updated');
        $response->assertSessionMissing('errors');
    }

    /**
     * Test reset method, use nonexistent email
     * Behaviour: fail
     */
    public function test_reset_method_fail_user_with_nonexistent_email_not_found()
    {
        Notification::fake();

        // Create token and send it to user
        $this->post(route('password.email'), [
            'email' => $this->user->email,
            'g-recaptcha-response' => 'withtestkeysthisalwaysvalid'
        ]);

        // Get token from notification
        $token = '';
        Notification::assertSentTo(
            $this->user,
            \Illuminate\Auth\Notifications\ResetPassword::class,
            function ($notification, $channels) use (&$token) {
                $token = $notification->token;

                return true;
            }
        );

        // Load test route
        $response = $this->post(url('password/reset'), [
            'token' => $token,
            'email' => $this->user->email . 'd',
            'password' => 'newPassword#1',
            'password_confirmation' => 'newPassword#1',
            'g-recaptcha-response' => 'withtestkeysthisalwaysvalid',
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors('email', 'We can\'t find a user with that e-mail address.');
    }

    /**
     * Test reset method, without all required fields
     * Behaviour: fail
     */
    public function test_reset_method_fail_validation_fields_is_required()
    {
        // Load test route
        $response = $this->post(url('password/reset'), []);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'token' => 'The token field is required.',
            'email' => 'The email field is required.',
            'password' => 'The password field is required.',
            'g-recaptcha-response' => 'The g-recaptcha-response field is required.',
        ]);
    }

    /**
     * Test reset method, with wrong email
     * Behaviour: fail
     */
    public function test_reset_method_fail_validation_email_wrong_format()
    {
        // Load test route
        $response = $this->post(url('password/reset'), [
            'token' => 'token',
            'email' => 'notemail',
            'password' => 'newPassword#1',
            'password_confirmation' => 'newPassword#1',
            'g-recaptcha-response' => 'withtestkeysthisalwaysvalid',
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email' => 'The email must be a valid email address.']);
    }
}
