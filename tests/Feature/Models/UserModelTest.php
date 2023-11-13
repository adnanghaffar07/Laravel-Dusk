<?php

namespace Tests\Feature\Models;

use App\Services\GoogleMapsService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Account;
use App\User;
use App\Title;
use App\Buyer;
use App\Branch;
use App\BuyerDocument;
use App\BuyerFile;
use App\WireDoc;
use Storage;
use Illuminate\Http\UploadedFile;

/**
 * Class UserModelTest
 * @package Tests\Feature\Controllers\Auth
 */
class UserModelTest extends TestCase
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
    protected $branch;

    /**
     * @var
     */
    protected $wireDoc;

    /**
     * @var
     */
    protected $buyerFiles;

    /**
     * @var
     */
    protected $documents;
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

        // Create fake title
        $this->title = factory(Title::class)->create([
            'account_id' => $this->account->id,
            'subdomain' => $this->faker->word
        ]);
        $this->title->account()->associate($this->account);
        $this->title->saveOrFail();

        $this->user = factory(User::class)->create([
            'password' => '1234567',
            'role' => 'User',
            'title_id' => $this->title->id
        ]);

        // Associate account with user
        $this->user->account()->associate($this->account);
        $this->user->saveOrFail();

        // Create fake buyer
        $this->buyer = factory(Buyer::class)->create([
            'title_id' => $this->title->id,
            'email' => $this->user->email,
            'phone' => '+15005550006',
            'passcode' => $this->faker->numerify('1########'),
            'username' => $this->user->email . $this->user->id,
        ]);

        // Create fake branch
        $this->branch = factory(Branch::class)
            ->create([
                'title_id' => $this->title->id
            ]);
        $this->branch->title()->associate($this->title);
        $this->branch->saveOrFail();

        $this->user->branches()->attach($this->branch->id);

        // Create fake wire doc
        $fakeWireDocName = snake_case($this->faker->name) . '.doc';
        $this->wireDoc = factory(WireDoc::class)
            ->create([
                'title_id' => $this->title->id,
                'path' => $fakeWireDocName,
                'name' => $fakeWireDocName
            ]);
        $this->wireDoc->branch()->associate($this->branch);
        $this->wireDoc->saveOrFail();

        // Create fake files and documents
        $fakeDocumentName = snake_case($this->faker->name) . '.doc';
        $address = '555 Acme Street, Austin, TX 78759';
        $this->documents = collect([]);
        $this->buyerFiles = factory(BuyerFile::class, 3)
            ->create([
                'buyer_id' => $this->buyer->id,
                'branch_id' => $this->branch->id,
                'wire_doc_id' => $this->wireDoc->id,
                'created_by_user_id' => $this->user->id,
                'address' => $address,
                'street_view_url' => GoogleMapsService::getStreetViewImgURL($address),
            ])->each(function ($file) use ($fakeDocumentName) {
                $this->documents->push(factory(BuyerDocument::class)->create([
                    'buyer_file_id' => $file->id,
                    'path' => $fakeDocumentName,
                    'name' => $fakeDocumentName
                ]));
            });

        // Create and upload fake wire doc file
        $fakeWireDocFile = UploadedFile::fake()->create($this->wireDoc->name);
        Storage::disk('s3')->put($this->wireDoc->name, file_get_contents($fakeWireDocFile));

        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name);
        Storage::disk('s3')->put($this->documents->first()->name, file_get_contents($fakeDocumentFile));

    }

    /**
     *  Clean up the testing environment before the next test.
     */
    public function tearDown()
    {
        Storage::disk('s3')->delete($this->wireDoc->name);
        Storage::disk('s3')->delete($this->documents->first()->name);

        parent::tearDown();
    }

    /**
     * Test that Users with role=Admin see inactive WireDocs
     * Behaviour: success
     */
    public function test_user_role_admin_sees_inactive_wire_docs()
    {
        // Save state before modifying
        $prevUserRole = $this->user->role;
        $prevWireDocActive = $this->wireDoc->active;

        // Admins should see all WireDocs regardless of 'active'
        $this->user->update(['role' => 'Admin']);
        $this->wireDoc->forceFill(['active' => 1])->saveOrFail();
        $this->assertCount(1, $this->user->wireDocs()->get());

        $this->wireDoc->forceFill(['active' => 0])->saveOrFail();
        $this->assertCount(1, $this->user->wireDocs()->get());

        // Restore state
        $this->user->update(['role' => $prevUserRole]);
        $this->wireDoc->forceFill(['active' => $prevWireDocActive]);
    }

    /**
     * Test that Users with role=User do not see inactive WireDocs
     * Behaviour: success
     */
    public function test_user_role_user_does_not_see_inactive_wire_docs()
    {
        // Save state before modifying
        $prevUserRole = $this->user->role;
        $prevWireDocActive = $this->wireDoc->active;

        // Users should only see 'active' WireDocs
        $this->user->update(['role' => 'User']);
        $this->wireDoc->forceFill(['active' => 1])->saveOrFail();
        $this->assertCount(1, $this->user->wireDocs()->get());

        $this->wireDoc->forceFill(['active' => 0])->saveOrFail();
        $this->assertCount(0, $this->user->wireDocs()->get());

        // Restore state
        $this->user->update(['role' => $prevUserRole]);
        $this->wireDoc->forceFill(['active' => $prevWireDocActive]);
    }
}
