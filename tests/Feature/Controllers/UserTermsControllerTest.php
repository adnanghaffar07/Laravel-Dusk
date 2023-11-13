<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Account;
use App\Title;
use App\User;
use Hash;
use Carbon\Carbon;

/**
 * Class UserTermsControllerTest
 * @package Tests\Feature\Controllers
 */
class UserTermsControllerTest extends TestCase
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

        // Create user
        $this->user = factory(User::class)->create([
            'password' => Hash::make('1234567'),
            'role' => 'User',
            'title_id' => $this->title->id,
            'password_updated_at' => Carbon::now(),
            'account_id' => $this->account->id,
            'forceTerms' => 1,
            'active' => 1
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
     * Test force method
     * Behaviour: success
     */
    public function test_force_method_success()
    {
        // Acting as logged user
        $this->be($this->user);

        // Load test route
        $response = $this->get(route('forceUserTerms'));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('Terms of Service');
        $response->assertSee('Accept');
    }

    /**
     * Test accept method
     * Behaviour: success
     */
    public function test_accept_method_success()
    {
        // Acting as logged user
        $this->be($this->user);

        // Load test route
        $response = $this->post(route('forceUserTerms'));
        $response->assertStatus(302);
        $response->assertRedirect(route('userHome'));
        $response->assertSessionMissing('errors');
        $this->assertEquals($this->user->forceTerms, 0);
    }
}
