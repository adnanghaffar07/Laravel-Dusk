<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Account;
use App\Title;
use App\User;
use App\Branch;
use Hash;
use Carbon\Carbon;

/**
 * Class PasswordControllerTest
 * @package Tests\Feature\Controllers
 */
class PasswordControllerTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    /**
     * @var
     */
    protected $account;

    /**
     * @var
     */
    protected $branch;

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

        // Create fake branch
        $this->branch = factory(Branch::class)
            ->create([
                'title_id' => $this->title->id
            ]);

        $this->user = factory(User::class)->create([
            'password' => Hash::make('newPassword#1'),
            'role' => 'User',
            'title_id' => $this->title->id,
            'password_updated_at' => Carbon::now(),
            'account_id' => $this->account->id,
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
        $response = $this->get(route('forcePassword'));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('Update Password');
    }

    /**
     * Test update method
     * Behaviour: success
     */
    public function test_update_method_success()
    {
        // Acting as logged user
        $this->be($this->user);

        // Get snapshot of current hash
        $currentHash = $this->user->password;

        // Load test route
        $response = $this->post(route('updatePassword'), [
            'currentPassword' => 'newPassword#1',
            'newPassword' => 'newPassword#1n2',
            'newPassword_confirmation' => 'newPassword#1n2',
            'terms' => true
        ]);
        $response->assertStatus(302);
        $response->assertRedirect(route('settings'));
        $response->assertSessionMissing('errors');
        $response->assertSessionHas('toastrSuccessTitle', 'Success!');
        $response->assertSessionHas('toastrSuccessMessage', 'Password updated');
        $this->assertNotEquals($this->user->password, $currentHash);
    }

    /**
     * Test update method, without required fields
     * Behaviour: fail
     */
    public function test_update_method_fail_validation_fields_are_required()
    {
        // Acting as logged user
        $this->be($this->user);

        // Load test route
        $response = $this->post(route('updatePassword'), []);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'currentPassword' => 'The current password field is required.',
            'newPassword' => 'The new password field is required.'
        ]);
    }

    /**
     * Test update method, with wrong fields
     * Behaviour: fail
     */
    public function test_update_method_fail_validation_fields_are_wrong()
    {
        // Acting as logged user
        $this->be($this->user);

        // Load test route
        $response = $this->post(route('updatePassword'), [
            'currentPassword' => 123,
            'newPassword' => 123,
            'newPassword_confirmation' => 123,
            'terms' => true
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'currentPassword' => 'The current password must be a string.',
            'newPassword' => 'The new password must be a string.',
            'newPassword' => 'The new password must be at least 8 characters.',
            'newPassword' => 'The new password format is invalid.'
        ]);
    }

    /**
     * Test update method, throttle
     * Behaviour: fail
     */
    public function test_update_method_fail_throttle()
    {
        // Acting as logged user
        $this->be($this->user);

        // Load test route, emulate throttling
        for ($i = 1; $i <= 31; $i++) {
            $response = $this->post(route('updatePassword'), [
                'currentPassword' => 123,
                'newPassword' => 123,
                'newPassword_confirmation' => 123,
                'terms' => true
            ]);
        }
        $response->assertStatus(429);
        $response->assertHeader("retry-after");
        $response->assertSee("You are being rate limited");
    }

    /**
     * Test update method, fail hash check
     * Behaviour: fail
     */
    public function test_update_method_fail_current_password_hash_check()
    {
        // Acting as logged user
        $this->be($this->user);

        // Load test route
        $response = $this->post(route('updatePassword'), [
            'currentPassword' => 'newPassword#1wrong',
            'newPassword' => 'newPassword#1new',
            'newPassword_confirmation' => 'newPassword#1new',
            'terms' => true
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'currentPassword' => 'Please enter correct current password.'
        ]);
    }

    /**
     * Test update method with demo account
     * Behaviour: fail
     */
    public function test_update_method_fail_demo_account()
    {
        // Acting as logged user
        $this->be($this->user);

        // Update account name
        $this->account->name = 'Demo Account';
        $this->account->saveOrFail();

        // Load test route
        $response = $this->post(route('updatePassword'), [
            'currentPassword' => 'newPassword#1',
            'newPassword' => 'newPassword#1n2',
            'newPassword_confirmation' => 'newPassword#1n2',
            'terms' => true
        ]);
        $response->assertStatus(302);
        $response->assertRedirect(route('settings'));
        $response->assertSessionHas('demo', null);
        $response->assertSessionMissing('errors');
    }
}
