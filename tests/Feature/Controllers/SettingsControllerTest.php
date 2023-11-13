<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Account;
use App\Title;
use App\User;
use App\Branch;
use App\WireDoc;
use Hash;
use Carbon\Carbon;

/**
 * Class SettingsControllerTest
 * @package Tests\Feature\Controllers
 */
class SettingsControllerTest extends TestCase
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
     * @var
     */
    protected $wireDoc;

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
            'password' => Hash::make('1234567'),
            'role' => 'User',
            'title_id' => $this->title->id,
            'password_updated_at' => Carbon::now(),
            'account_id' => $this->account->id,
            'active' => 1
        ]);

        // Create fake wire doc
        $fakeWireDocName = snake_case($this->faker->name) . '.doc';
        $this->wireDoc = factory(WireDoc::class)
            ->create([
                'title_id' => $this->title->id,
                'path' => $fakeWireDocName,
                'name' => $fakeWireDocName,
                'branch_id' => $this->branch->id
            ]);

        // Associate fake user with branch
        $this->branch->users()->save($this->user);
    }

    /**
     *  Clean up the testing environment before the next test.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Test index method
     * Behaviour: success
     */
    public function test_index_method_success()
    {
        // Acting as logged user
        $this->be($this->user);

        // Load test route
        $response = $this->get(route('settings'));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('Account Options');
        $response->assertSee('Available Wire Instructions');
    }

    /**
     * Test if account is not assigned to any branches.
     * Behaviour: success
     */
    public function test_middleware_account_is_not_assigned_to_any_branches_success()
    {
        // Acting as logged user
        $this->be($this->user);

        // Remove user branch
        $this->branch->delete();

        // Load test route
        $response = $this->get(route('settings'));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('Your account is not assigned to any branches.');
    }

    /**
     * Test if account has been deactivated
     * Behaviour: success
     */
    public function test_middleware_account_has_been_deactivated_success()
    {
        // Acting as logged user
        $this->be($this->user);

        // Update user
        $this->user->active = 0;
        $this->user->saveOrFail();

        // Load test route
        $response = $this->get(route('settings'));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('Your account has been deactivated.');
    }

    /**
     * Test updateAccountOptions method as admin user
     * Behaviour: success
     */
    public function test_update_account_options_method_as_admin_success()
    {
        // Acting as logged user
        $this->be($this->user);

        // Set user admin role
        $this->user->role = 'Admin';
        $this->user->saveOrFail();

        // Load test route
        $response = $this->patch(route('updateAccountOptions'), [
            'notes' => 'string',
            'documents' => 'documents',
            'phone' => 'phone',
        ]);
        $response->assertStatus(302);
        $response->assertSessionMissing('errors');
        $response->assertSessionHas('toastrSuccessTitle', 'Success!');
        $response->assertSessionHas('toastrSuccessMessage', 'Updated account options');

        // Get current user account again
        $account = Account::find($this->account->id);
        $this->assertEquals($account->allows_buyer_notes, true);
        $this->assertEquals($account->allows_buyer_documents, true);
        $this->assertEquals($account->allows_buyer_phone, true);
    }

    /**
     * Test updateAccountOptions method without privileges
     * Behaviour: fail
     */
    public function test_update_account_options_method_fail_without_privileges()
    {
        // Acting as logged user
        $this->be($this->user);

        // Load test route
        $response = $this->patch(route('updateAccountOptions'), [
            'notes' => 'string',
            'documents' => 'documents',
            'phone' => 'phone',
        ]);
        $response->assertStatus(302);
        $response->assertSessionMissing('errors');
    }

    /**
     * Test updateAccountOptions with demo account
     * Behaviour: fail
     */
    public function test_update_account_options_method_fail_demo_account()
    {
        // Acting as logged user
        $this->be($this->user);

        // Update account name
        $this->account->name = 'Demo Account';
        $this->account->saveOrFail();

        // Load test route
        $response = $this->patch(route('updateAccountOptions'), [
            'notes' => 'string',
            'documents' => 'documents',
            'phone' => 'phone',
        ]);
        $response->assertStatus(302);
        $response->assertSessionMissing('errors');
        $response->assertSessionHas('toastrSuccessTitle', 'Success!');
        $response->assertSessionHas('toastrSuccessMessage', 'Demo Account');
    }

    /**
     * Test updateAccountOptions method, with wrong fields
     * Behaviour: fail validation
     */
    public function test_update_account_options_method_fail_validation_wrong_fields()
    {
        // Acting as logged user
        $this->be($this->user);

        // Load test route
        $response = $this->patch(route('updateAccountOptions'), [
            'notes' => 1,
            'documents' => 1,
            'phone' => 3,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'notes' => 'The notes must be a string.',
            'documents' => 'The documents must be a string.',
            'phone' => 'The phone must be a string.',
        ]);
    }

    /**
     * Test updateDefaultWire method
     * Behaviour: success
     */
    public function test_update_default_wire_method_success()
    {
        // Acting as logged user
        $this->be($this->user);

        // Load test route
        $response = $this->patch(route('updateUserDefaultWire'), [
            'wireDoc' => $this->wireDoc->id,
        ]);
        $response->assertStatus(302);
        $response->assertSessionMissing('errors');
        $response->assertSessionHas('toastrSuccessTitle', 'Success!');
        $response->assertSessionHas('toastrSuccessMessage', 'Updated default wire instructions');
        $this->assertNotNull($this->user->defaultWireDoc);
    }

    /**
     * Test updateDefaultWire method, with wrong wireDoc field
     * Behaviour: fail
     */
    public function test_update_default_wire_method_fail_validation_wire_doc_is_wrong()
    {
        // Acting as logged user
        $this->be($this->user);

        // Load test route
        $response = $this->patch(route('updateUserDefaultWire'), [
            'wireDoc' => 'stringisnotvalidtype',
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['wireDoc' => 'The wire doc must be an integer.']);
    }

    /**
     * Test updateDefaultWire with demo account
     * Behaviour: fail
     */
    public function test_update_default_wire_method_fail_demo_account()
    {
        // Acting as logged user
        $this->be($this->user);

        // Update account name
        $this->account->name = 'Demo Account';
        $this->account->saveOrFail();

        // Load test route
        $response = $this->patch(route('updateUserDefaultWire'), [
            'wireDoc' => $this->wireDoc->id,
        ]);
        $response->assertStatus(302);
        $response->assertSessionMissing('errors');
        $response->assertSessionHas('toastrSuccessTitle', 'Success!');
        $response->assertSessionHas('toastrSuccessMessage', 'Demo Account');
    }

    /**
     * Test updateDefaultWire, wire instructions do not belongs to this user
     * Behaviour: fail
     */
    public function test_update_default_wire_method_fail_wire_instructions_do_not_belongs_to_this_user()
    {
        // Acting as logged user
        $this->be($this->user);

        // Create fake branch
        $newBranch = factory(Branch::class)
            ->create([
                'title_id' => $this->title->id
            ]);

        // Create another fake wire doc
        $fakeWireDocName = snake_case($this->faker->name) . '.doc';
        $newWireDoc = factory(WireDoc::class)
            ->create([
                'title_id' => $this->title->id,
                'path' => $fakeWireDocName,
                'name' => $fakeWireDocName,
                'branch_id' => $newBranch->id
            ]);

        // Load test route
        $response = $this->patch(route('updateUserDefaultWire'), [
            'wireDoc' => $newWireDoc->id,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'errors' => 'No message'
        ]);
    }
}
