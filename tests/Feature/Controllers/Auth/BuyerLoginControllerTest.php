<?php

namespace Tests\Feature\Controllers\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Account;
use App\User;
use App\Title;
use App\Buyer;
use Hash;

/**
 * Class BuyerLoginControllerTest
 * @package Tests\Feature\Controllers\Auth
 */
class BuyerLoginControllerTest extends TestCase
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
     * @var
     */
    protected $buyer;

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

        // Associate account with user
        $this->user->account()->associate($this->account);
        $this->user->saveOrFail();

        // Create buyer
        $this->buyer = factory(Buyer::class)->create([
            'title_id' => $this->title->id,
            'email' => $this->user->email,
            'phone' => '+15005550006',
            'phone_type' => 'mobile',
            'passcode' => $this->faker->numerify('1########'),
            'username' => $this->user->email . $this->user->id,
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
     * Test check credentials method with valid and existed data
     * Behaviour: success
     *
     * @throws \Throwable
     */
    public function test_check_credentials_method_success_check()
    {
        // Load test route
        $response = $this->post(route('buyerCheckCredentials', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'email' => $this->user->email,
            'password' => '1234567',
            'title_id' => $this->title->id,
            'terms' => true,
        ]);
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertJson(['require_passcode' => false]);
    }

    /**
     * Test check credentials method, require pass code
     * Behaviour: success
     *
     * @throws \Throwable
     */
    public function test_check_credentials_method_success_require_pass_code()
    {
        // Update some fileds for account and buyer
        $this->account->requires_buyer_passcode = 1;
        $this->account->saveOrFail();

        // Load test route
        $response = $this->post(route('buyerCheckCredentials', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'email' => $this->user->email,
            'password' => '1234567',
            'title_id' => $this->title->id,
            'terms' => true,
        ]);
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertJson(['require_passcode' => true]);
    }

    /**
     * Test check credentials method with null email
     * Behaviour: fail validation
     *
     * @throws \Throwable
     */
    public function test_check_credentials_method_fail_validation_email_is_required()
    {
        // Load test route
        $response = $this->post(route('buyerCheckCredentials', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'email' => null,
            'password' => '1234567',
            'title_id' => $this->title->id,
            'terms' => true,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'email' => 'The email field is required.'
        ]);
    }

    /**
     * Test check credentials method with null title id
     * Behaviour: fail validation
     *
     * @throws \Throwable
     */
    public function test_check_credentials_method_fail_validation_title_id_is_required()
    {
        // Load test route
        $response = $this->post(route('buyerCheckCredentials', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'email' => $this->user->email,
            'password' => '1234567',
            'title_id' => null,
            'terms' => true,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'title_id' => 'Please select your closing company.'
        ]);
    }

    /**
     * Test check credentials method with not accepted terms
     * Behaviour: fail validation
     *
     * @throws \Throwable
     */
    public function test_check_credentials_method_fail_validation_terms_accept()
    {
        // Load test route
        $response = $this->post(route('buyerCheckCredentials', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'email' => $this->user->email,
            'password' => '1234567',
            'title_id' => $this->title->id,
            'terms' => 'no',
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'terms' => 'The terms must be accepted.'
        ]);
    }

    /**
     * Test check credentials method with not valid email
     * Behaviour: fail validation
     *
     * @throws \Throwable
     */
    public function test_check_credentials_method_fail_validation_email_field()
    {
        // Load test route
        $response = $this->post(route('buyerCheckCredentials', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'email' => 'itsnotemail',  // set not valid email
            'password' => '1234567',
            'title_id' => $this->title->id,
            'terms' => true,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'email' => 'The email must be a valid email address.'
        ]);
    }

    /**
     * Test check credentials method with not valid title id
     * Behaviour: fail validation
     *
     * @throws \Throwable
     */
    public function test_check_credentials_method_fail_validation_title_id_field()
    {
        // Load test route
        $response = $this->post(route('buyerCheckCredentials', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'email' => $this->user->email,
            'password' => '1234567',
            'title_id' => 'string', // set wrong type (string instead int)
            'terms' => true,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'title_id' => 'The title id must be an integer.'
        ]);
    }

    /**
     * Test check credentials method with non existed email
     * Behaviour: fail, unauthorized
     *
     * @throws \Throwable
     */
    public function test_check_credentials_method_fail_unauthorized_with_non_existed_email()
    {
        // Load test route
        $response = $this->post(route('buyerCheckCredentials', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'email' => 'notexistmail' . $this->user->email, // set non existed email
            'password' => '1234567',
            'title_id' => $this->title->id,
            'terms' => true,
        ]);
        $response->assertStatus(401);
        $response->assertJson(['errors' => ['email' => 'No match', 'title_id' => 'No title match']]);
    }

    /**
     * Test check credentials method with non existed title id
     * Behaviour: fail, unauthorized
     *
     * @throws \Throwable
     */
    public function test_check_credentials_method_fail_unauthorized_with_non_existed_title_id()
    {
        // Load test route
        $response = $this->post(route('buyerCheckCredentials', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'email' => $this->user->email,
            'password' => '1234567',
            'title_id' => 0, // set non existed id
            'terms' => true,
        ]);
        $response->assertStatus(401);
        $response->assertJson(['errors' => ['email' => 'No match', 'title_id' => 'No title match']]);
    }

    /**
     * Test validate passcode method with non existed passcode
     * Behaviour: fail validation
     *
     * @throws \Throwable
     */
    public function test_validate_passcode_method_fail_validation_passcode_is_required()
    {
        // Load test route
        $response = $this->post(route('buyerValidatePasscode', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'passcode' => null,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'passcode' => 'The passcode field is required.'
        ]);
    }

    /**
     * Test validate passcode method with non existed passcode
     * Behaviour: fail validation
     *
     * @throws \Throwable
     */
    public function test_validate_passcode_method_fail_validation_passcode_field()
    {
        // Load test route
        $response = $this->post(route('buyerValidatePasscode', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'passcode' => 'stringinsteadofint',
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'passcode' => 'The passcode must be an integer.'
        ]);
    }

    /**
     * Test validate passcode method with non existed passcode
     * Behaviour: fail validation
     *
     * @throws \Throwable
     */
    public function test_validate_passcode_method_fail_invalid_passcode()
    {
        // Check credentials and create buyer session
        $this->post(route('buyerCheckCredentials', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'email' => $this->user->email,
            'password' => '1234567',
            'title_id' => $this->title->id,
            'terms' => true,
        ]);

        // Load test route
        $response = $this->post(route('buyerValidatePasscode', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'passcode' => 0,
        ]);
        $response->assertStatus(401);
        $response->assertSessionMissing('errors');
        $response->assertExactJson(['errors' => ['passcode' => 'Invalid passcode. Please try again.']]);
    }

    /**
     * Test validate passcode method without buyer session
     * Behaviour: fail
     *
     * @throws \Throwable
     */
    public function test_validate_passcode_method_fail_no_buyer_session()
    {
        // Load test route
        $response = $this->post(route('buyerValidatePasscode', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'passcode' => 0,
        ]);
        $response->assertStatus(500);
        $response->assertSessionMissing('errors');
        $response->assertSee('Missing values in session');
    }

    /**
     * Test validate passcode method with valid passcode
     * Behaviour: success
     *
     * @throws \Throwable
     */
    public function test_validate_passcode_method_success()
    {
        // Check credentials and create buyer session
        $this->post(route('buyerCheckCredentials', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'email' => $this->user->email,
            'password' => '1234567',
            'title_id' => $this->title->id,
            'terms' => true,
        ]);

        // Load test route
        $response = $this->post(route('buyerValidatePasscode', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'passcode' => $this->buyer->passcode,
        ]);
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('passcode validated');
    }

    /**
     * Test send passcode method with sms
     * Behaviour: success
     *
     * @throws \Throwable
     */
    public function test_send_passcode_method_success_with_sms()
    {
        // Check credentials and create buyer session
        $this->post(route('buyerCheckCredentials', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'email' => $this->user->email,
            'password' => '1234567',
            'title_id' => $this->title->id,
            'terms' => true,
        ]);

        // Load test route
        $response = $this->post(route('buyerSendPasscode', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'phoneType' => 'sms',
        ]);
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('texted passcode');
    }

    /**
     * Test send passcode method with call
     * Behaviour: success
     *
     * @throws \Throwable
     */
    public function test_send_passcode_method_success_with_call()
    {
        // Check credentials and create buyer session
        $this->post(route('buyerCheckCredentials', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'email' => $this->user->email,
            'password' => '1234567',
            'title_id' => $this->title->id,
            'terms' => true,
        ]);

        // Load test route
        $response = $this->post(route('buyerSendPasscode', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'phoneType' => 'call',
        ]);
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('called passcode');
    }

    /**
     * Test send passcode method, fail untextable phone number
     * Behaviour: fail
     *
     * @throws \Throwable
     */
    public function test_send_passcode_method_fail_untextable_phone_number()
    {
        // Update some fileds for buyer
        $this->buyer->phone_type = 'landline';
        $this->buyer->saveOrFail();

        // Check credentials and create buyer session
        $this->post(route('buyerCheckCredentials', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'email' => $this->user->email,
            'password' => '1234567',
            'title_id' => $this->title->id,
            'terms' => true,
        ]);

        // Load test route
        $response = $this->post(route('buyerSendPasscode', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'phoneType' => 'sms',
        ]);

        $response->assertStatus(422);
        $response->assertSee('Phone number is not textable');
    }

    /**
     * Test send passcode method with null phoneType
     * Behaviour: fail validation
     *
     * @throws \Throwable
     */
    public function test_send_passcode_method_fail_validation_phone_type_is_required()
    {
        // Load test route
        $response = $this->post(route('buyerSendPasscode', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'phoneType' => null,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'phoneType' => 'The phone type field is required.'
        ]);
    }

    /**
     * Test send passcode method with non existed phoneType
     * Behaviour: fail validation
     *
     * @throws \Throwable
     */
    public function test_send_passcode_method_fail_validation_with_non_existed_phone_type()
    {
        // Load test route
        $response = $this->post(route('buyerSendPasscode', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'phoneType' => 'nonexistedtype',
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'phoneType' => 'The selected phone type is invalid.'
        ]);
    }

    /**
     * Test show login form method with generic subdomain
     * Behaviour: success
     *
     * @throws \Throwable
     */
    public function test_show_login_form_method_success_with_generic_subdomain()
    {
        // Load test route
        $response = $this->get(route('buyerLogin', ['title' => env('GENERIC_SUBDOMAIN')]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('<h2>Login</h2>');
        $response->assertSee('Select closing company');
    }

    /**
     * Test show login form method with variable subdomain
     * Behaviour: fail, redirect
     *
     * @throws \Throwable
     */
    public function test_show_login_form_method_success_with_non_existed_variable_subdomain()
    {
        // Load test route
        $response = $this->get(route('buyerLogin', ['title' => $this->faker->word]));
        $response->assertStatus(302);
        $response->assertSessionMissing('errors');
        $response->assertRedirect(route('buyerLogin', ['subdomain' => env('GENERIC_SUBDOMAIN')]));
    }

    /**
     * Test show login form method with variable subdomain
     * Behaviour: success
     *
     * @throws \Throwable
     */
    public function test_show_login_form_method_success_with_existed_variable_subdomain()
    {
        // Load test route
        $response = $this->get(route('buyerLogin', ['title' => $this->title->subdomain]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
    }

    /**
     * Test login method
     * Behaviour: success
     *
     * @throws \Throwable
     */
    public function test_login_method_success()
    {
        // Check credentials and create buyer session
        $this->post(route('buyerCheckCredentials', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'email' => $this->user->email,
            'password' => '1234567',
            'title_id' => $this->title->id,
            'terms' => true,
        ]);

        // Load test route
        $response = $this->post(route('buyerLoginPOST', ['title' => env('GENERIC_SUBDOMAIN')]), [
            'email' => $this->user->email,
            'password' => '1234567',
            'title_id' => $this->title->id,
            'terms' => true,
        ]);
        $response->assertStatus(302);
        $response->assertSessionMissing('errors');
        $response->assertCookie(auth('buyer')->getRecallerName());
    }

    /**
     * Test login method, do not create buyer session before login
     * Behaviour: fail
     *
     * @throws \Throwable
     */
    public function test_login_method_fail_with_no_buyer_session()
    {
        // Load test route
        $response = $this->post(route('buyerLoginPOST', ['title' => env('GENERIC_SUBDOMAIN')]), [
            'email' => $this->user->email,
            'password' => '1234567',
            'title_id' => $this->title->id,
            'terms' => true,
        ]);
        $response->assertStatus(500);
        $response->assertSessionMissing('errors');
        $response->assertSee('missing values in session');
    }

    /**
     * Test logout method
     * Behaviour: success
     *
     * @throws \Throwable
     */
    public function test_logout_method_success()
    {
        \Event::fake();

        // Check credentials and create buyer session
        $this->post(route('buyerCheckCredentials', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'email' => $this->user->email,
            'password' => '1234567',
            'title_id' => $this->title->id,
            'terms' => true,
        ]);

        // Load test route
        $response = $this->post(route('buyerLogout', ['title' => env('GENERIC_SUBDOMAIN')]));
        $response->assertStatus(302);
        $response->assertSessionMissing('errors');
    }
}
