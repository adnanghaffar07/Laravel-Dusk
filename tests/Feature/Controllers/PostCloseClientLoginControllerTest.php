<?php

namespace Tests\Feature\Controllers;

use App\Notifications\PostClose\AccessCodeNotification;
use App\PostCloseClient;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Account;
use App\Title;
use App\User;
use App\Branch;
use Carbon\Carbon;

class PostCloseClientLoginControllerTest extends TestCase
{
    use WithFaker, RefreshDatabase;
    private $title;
    private $account;
    private $postCloseClient;
    private $user;
    private $branch;

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
        $this->account->allows_post_close = true;
        $this->account->saveOrFail();

        // Create title
        $subdomainWord = $this->faker->word;
        $this->title = factory(Title::class)->create([
            'account_id' => $this->account->id,
            'subdomain' => $subdomainWord,
            'post_close_subdomain' => $subdomainWord,
        ]);

        // Create fake branch
        $this->branch = factory(Branch::class)
            ->create([
                'title_id' => $this->title->id
            ]);

        $this->user = factory(User::class)->create([
            'password' => Hash::make('1234567'),
            'role' => 'User',
            'title_id' => $this->title->id,
            'password_updated_at' => Carbon::now(),
            'account_id' => $this->account->id,
            'active' => 1
        ]);

        // Create fake PostCloseClient
        $this->postCloseClient = factory(PostCloseClient::class)->create([
            'title_id' => $this->title->id,
            'email' => $this->user->email,
            'username' => $this->user->email . $this->user->id,
            'password' => Hash::make('1234567'),
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
     * Test postCloseClientLogin route
     * Behaviour: success
     */
    public function test_show_login_form_method_success()
    {
        // Load test route
        $response = $this->get(route('postCloseClientLogin', [
            'subdomain' => $this->title->subdomain,
        ]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('Client Login');
    }

    /**
     * Test postCloseClientLoginPOST route
     * Behaviour: success
     */
    public function test_login_method_success()
    {
        // Load test route
        $response = $this->post(route('postCloseClientLoginPOST', [
            'subdomain' => $this->title->subdomain,
        ]), [
            'email' => $this->postCloseClient->email,
            'accessCode' => $this->postCloseClient->token,
        ]);

        $response->assertStatus(302);
        $response->assertSessionMissing('errors');
        $response->assertRedirect(route('postCloseClientHome', [
            'subdomain' => $this->title->subdomain,
        ]));
    }

    /**
     * Test postCloseClientLoginPOST route, without email
     * Behaviour: fail
     */
    public function test_login_method_fail_validation_email_is_required()
    {
        // Load test route
        $response = $this->post(route('postCloseClientLoginPOST', [
            'subdomain' => $this->title->subdomain,
        ]), [
            'accessCode' => $this->postCloseClient->token,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
    }

    /**
     * Test postCloseClientLoginPOST route, without accessCode
     * Behaviour: fail
     */
    public function test_login_method_fail_validation_access_code_is_required()
    {
        // Load test route
        $response = $this->post(route('postCloseClientLoginPOST', [
            'subdomain' => $this->title->subdomain,
        ]), [
            'email' => $this->postCloseClient->email,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('accessCode');
    }

    /**
     * Test postCloseClientLoginPOST route, with wrong credentials
     * Behaviour: fail
     */
    public function test_login_method_fail_credentials_do_not_match()
    {
        // Load test route
        $response = $this->post(route('postCloseClientLoginPOST', [
            'subdomain' => $this->title->subdomain,
        ]), [
            'email' => $this->postCloseClient->email,
            'accessCode' => 'wrong_access_code',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('login');
    }

    /**
     * Test postCloseClientLogout route
     * Behaviour: success
     */
    public function test_logout_method_success()
    {
        // Acting as logged user
        $this->be($this->postCloseClient, 'postCloseClient');

        // Load test route
        $response = $this->post(route('postCloseClientLogout', [
            'subdomain' => $this->title->subdomain,
        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('postCloseClientLogin', [
            'subdomain' => $this->title->subdomain,
        ]));

        // Follow redirect
        $response2 = $this->get($response->headers->get('Location'));
        $response2->assertStatus(200);
        $response2->assertSessionMissing('errors');
        $response2->assertSee('Client Login');
    }

    /**
     * Test postCloseClientSendAccessCode route
     * Behaviour: success
     */
    public function test_send_access_code_method_success()
    {
        Notification::fake();

        // Load test route
        $response = $this->post(route('postCloseClientSendAccessCode', [
            'subdomain' => $this->title->subdomain,
        ]), [
            'sendAccessCodeEmail' => $this->postCloseClient->email,
        ]);

        $response->assertStatus(302);
        $response->assertSessionMissing('errors');

        Notification::assertSentTo([$this->postCloseClient], AccessCodeNotification::class);
    }

    /**
     * Test postCloseClientSendAccessCode route, with wrong email
     * Behaviour: success
     */
    public function test_send_access_code_method_fail_wrong_email()
    {
        Notification::fake();

        // Load test route
        $response = $this->post(route('postCloseClientSendAccessCode', [
            'subdomain' => $this->title->subdomain,
        ]), [
            'sendAccessCodeEmail' => 'actually-wrong-email@test.test',
        ]);

        $response->assertStatus(302);
        $response->assertSessionMissing('errors');

        Notification::assertNothingSent();
    }
}
