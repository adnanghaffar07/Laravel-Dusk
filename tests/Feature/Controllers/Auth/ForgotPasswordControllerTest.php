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
 * Class ForgotPasswordControllerTest
 * @package Tests\Feature\Controllers\Auth
 */
class ForgotPasswordControllerTest extends TestCase
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
     * Test showLinkRequestForm method
     * Behaviour: success
     */
    public function test_show_link_request_form_method_success()
    {
        // Load test route
        $response = $this->get(route('password.request'));
        $response->assertStatus(200);
        $response->assertSee('Reset Password');
    }

    /**
     * Test sendResetLinkEmail method
     * Behaviour: success
     */
    public function test_send_reset_link_email_method_success()
    {
        Notification::fake();

        // Load test route
        $response = $this->post(route('password.email'), [
            'email' => $this->user->email,
            'g-recaptcha-response' => 'withtestkeysthisalwaysvalid'
        ]);
        $response->assertStatus(302);
        $response->assertSessionHas('status', 'We have e-mailed your password reset link!');
        $response->assertSessionMissing('errors');

        Notification::assertSentTo([$this->user], \Illuminate\Auth\Notifications\ResetPassword::class);
    }

    /**
     * Test sendResetLinkEmail method, without email
     * Behaviour: fail
     */
    public function test_send_reset_link_email_method_fail_validation_email_required()
    {
        // Load test route
        $response = $this->post(route('password.email'), [
            'g-recaptcha-response' => 'withtestkeysthisalwaysvalid'
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email' => 'The email field is required.']);
    }

    /**
     * Test sendResetLinkEmail method, without g-recaptcha-response
     * Behaviour: fail
     */
    public function test_send_reset_link_email_method_fail_validation_g_recaptcha_response_required()
    {
        // Load test route
        $response = $this->post(route('password.email'), [
            'email' => $this->user->email,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['g-recaptcha-response' => 'The g-recaptcha-response field is required.']);
    }

    /**
     * Test sendResetLinkEmail method, with invalid email format
     * Behaviour: fail
     */
    public function test_send_reset_link_email_method_fail_validation_email_wrong_format()
    {
        // Load test route
        $response = $this->post(route('password.email'), [
            'email' => 'notemail',
            'g-recaptcha-response' => 'withtestkeysthisalwaysvalid'
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email' => 'The email must be a valid email address.']);
    }

    /**
     * Test sendResetLinkEmail method, with demo email
     * Behaviour: fail
     */
    public function test_send_reset_link_email_method_fail_demo_email()
    {
        // Load test route
        $response = $this->post(route('password.email'), [
            'email' => 'admin@demo.com',
            'g-recaptcha-response' => 'withtestkeysthisalwaysvalid'
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email' => 'There is no need to reset the demo password.']);
    }
}
