<?php

namespace Tests\Feature\Controllers\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Account;
use App\Title;
use App\User;
use Hash;

use Carbon\Carbon;

/**
 * Class LoginControllerTest
 * @package Tests\Feature\Controllers\Auth
 */
class LoginControllerTest extends TestCase
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
     * Test show login form method
     * Behaviour: success
     */
    public function test_show_login_form_method_success()
    {
        // Load test route
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('Admin Login');
    }

    /**
     * Test login method
     * Behaviour: success
     */
    public function test_login_method_success()
    {
        // Load test route
        $response = $this->post(route('loginPOST'), [
            'email' => $this->user->email,
            'password' => '1234567'
        ]);
        $response->assertStatus(302);
        $response->assertSessionMissing('errors');
        $response->assertRedirect(route('userHome'));
        $response->assertCookieMissing(auth('web')->getRecallerName());
    }

    /**
     * Test login method, with 'remember me' selected
     * Behaviour: success
     */
    public function test_login_method_success_and_has_cookie()
    {
        // Load test route
        $response = $this->post(route('loginPOST'), [
            'email' => $this->user->email,
            'password' => '1234567',
            'remember' => 'remember'
        ]);
        $response->assertStatus(302);
        $response->assertSessionMissing('errors');
        $response->assertRedirect(route('userHome'));

        $cookieName = auth('web')->getRecallerName();

        $response->assertCookie($cookieName, vsprintf('%s|%s|%s', [
            $this->user->id,
            $this->user->getRememberToken(),
            $this->user->password,
        ]));

        $expiresAt = $response->headers->getCookies()[0]->getExpiresTime();
        $expiresAt = Carbon::createFromTimestamp($expiresAt);
        $this->assertEquals(
            30,
            Carbon::now()->diffInDays($expiresAt),
            "Cookie [{$cookieName}] expiration is not 30 days."
        );
    }

    /**
     * Test login method, without email
     * Behaviour: fail
     */
    public function test_login_method_fail_validation_email_is_required()
    {
        // Load test route
        $response = $this->post(route('loginPOST'), [
            'password' => '1234567'
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
    }

    /**
     * Test login method, without password
     * Behaviour: fail
     */
    public function test_login_method_fail_validation_password_is_required()
    {
        // Load test route
        $response = $this->post(route('loginPOST'), [
            'email' => $this->user->email,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');
    }

    /**
     * Test login method, with wrong credentials
     * Behaviour: fail
     */
    public function test_login_method_fail_credentials_do_not_match()
    {
        // Load test route
        $response = $this->post(route('loginPOST'), [
            'email' => $this->user->email,
            'password' => 'wrong_password'
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Test logout method
     * Behaviour: success
     */
    public function test_logout_method_success()
    {
        // Acting as logged user
        $this->be($this->user);

        // Load test route
        $response = $this->post(route('logout'));
        $response->assertStatus(302);
        $response->assertRedirect(route('userHome'));

        // Follow redirect
        $response2 = $this->get($response->headers->get('Location'));
        $response2->assertStatus(302);
        $response2->assertRedirect(route('login'));
    }

    /**
     * Test demo method
     * Behaviour: success
     */
    public function test_demo_method_success()
    {
        // Load test route
        $response = $this->get(route('adminDemo'));
        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('demo email', 'admin@demo.com');
        $response->assertSessionHas('demo pass', 'buyerdocsisgreat');
    }
}
