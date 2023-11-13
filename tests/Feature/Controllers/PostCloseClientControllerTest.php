<?php

namespace Tests\Feature\Controllers;

use App\PostCloseClient;
use App\PostCloseDocument;
use App\PostCloseFile;
use App\Services\GoogleMapsService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Account;
use App\Title;
use App\User;
use App\Branch;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

class PostCloseClientControllerTest extends TestCase
{
    use WithFaker, RefreshDatabase;
    private $postCloseClient;
    private $account;
    private $title;
    private $branch;
    private $postCloseFile;
    private $user;
    private $postCloseDocument;

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

        // Create fake PostCloseClient
        $this->postCloseClient = factory(PostCloseClient::class)->create([
            'title_id' => $this->title->id,
            'email' => $this->user->email,
            'username' => $this->user->email . $this->user->id,
            'password' => Hash::make('1234567'),
        ]);

        // Create fake PostCloseFile
        $address = '555 Acme Street, Austin, TX 78759';
        $this->postCloseFile = factory(PostCloseFile::class)->create([
            'post_close_client_id' => $this->postCloseClient->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'ref_number' => 'refnum',
            'address' => $address,
            'street_view_url' => GoogleMapsService::getStreetViewImgURL($address),
        ]);

        // Create fake PostCloseDocument
        $fakePostCloseDocumentName = snake_case($this->faker->name) . '.doc';
        $this->postCloseDocument = factory(PostCloseDocument::class)->create([
            'post_close_file_id' => $this->postCloseFile->id,
            'uploader_role' => 'user',
            'uploader_id' => $this->user->id,
            'path' => $fakePostCloseDocumentName,
            'name' => $fakePostCloseDocumentName,
        ]);

        // Create and upload fake post close file
        $fakePostCloseDocumentFile = UploadedFile::fake()->create($this->postCloseDocument->name);
        Storage::disk('s3')->put($this->postCloseDocument->name, file_get_contents($fakePostCloseDocumentFile));

        // Associate fake user with branch
        $this->branch->users()->save($this->user);

        // Acting as logged user
        $this->be($this->postCloseClient, 'postCloseClient');
    }

    /**
     *  Clean up the testing environment before the next test.
     */
    public function tearDown()
    {
        Storage::disk('s3')->delete($this->postCloseDocument->name);

        parent::tearDown();
    }

    /**
     * Test postCloseClientHome route
     * Behaviour: success
     */
    public function test_index_method_success()
    {
        // Load test route
        $response = $this->get(route('postCloseClientHome', [
            'subdomain' => $this->title->subdomain,
            ]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('Please contact ' . $this->title->name . ' with any questions.');
    }

    /**
     * Test postCloseClientHome route, check property address and google maps street view visibility
     * Behaviour: success
     */
    public function test_index_method_success_with_property_address()
    {
        // Load test route
        $response = $this->get(route('postCloseClientHome', [
            'subdomain' => $this->title->subdomain,
            ]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee($this->postCloseFile->address);
        $response->assertSee(htmlspecialchars($this->postCloseFile->street_view_url));
    }

    /**
     * Test postCloseClientDownloadDocument route
     * Behaviour: success
     */
    public function test_download_document_method_success()
    {
        // Load test route
        $response = $this->get(route('postCloseClientDownloadDocument', [
            'subdomain' => $this->title->subdomain,
            'document' => $this->postCloseDocument->id,
        ]));

        // Get current file mime type
        $mimetype = Storage::disk('s3')->getMimetype($this->postCloseDocument->path);

        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $this->assertTrue($response->headers->get('content-type') === $mimetype);
        $this->assertTrue($response->headers->get('content-description') === 'File Transfer');
        $this->assertTrue($response->headers->get('content-disposition') === 'attachment; filename="' . $this->postCloseDocument->name . '"');
    }

    /**
     * Test userPostCloseDownloadDocument route, check user access
     * Behaviour: fail
     */
    public function test_download_document_method_fail_check_access()
    {
        // Recreate some data for new document
        $newBranch = factory(Branch::class)
            ->create([
                'title_id' => $this->title->id
            ]);

        $newPostCloseClient = factory(PostCloseClient::class)->create([
            'title_id' => $this->title->id,
            'email' => $this->faker->unique()->safeEmail,
            'username' => $this->faker->unique()->safeEmail . $this->user->id,
            'password' => Hash::make('1234567'),
        ]);

        $newPostCloseFile = factory(PostCloseFile::class)->create([
            'post_close_client_id' => $newPostCloseClient->id,
            'branch_id' => $newBranch->id,
            'user_id' => $this->user->id,
            'ref_number' => 'refnum',
        ]);

        $fakePostCloseDocumentName = snake_case($this->faker->name) . '.doc';
        $newPostCloseDocument = factory(PostCloseDocument::class)->create([
            'post_close_file_id' => $newPostCloseFile->id,
            'uploader_role' => 'user',
            'uploader_id' => $this->user->id,
            'path' => $fakePostCloseDocumentName,
            'name' => $fakePostCloseDocumentName,
        ]);

        // Load test route
        $response = $this->get(route('postCloseClientDownloadDocument', [
            'subdomain' => $this->title->subdomain,
            'document' => $newPostCloseDocument->id,
        ]));
        $response->assertStatus(302);
        $response->assertSessionMissing('errors');
        $response->assertRedirect(route('postCloseClientLogin', [
            'subdomain' => $this->title->subdomain,
        ]));
    }

    /**
     * Test userPostCloseDownloadDocument route, with non-existing document
     * Behaviour: fail
     */
    public function test_download_document_method_fail_document_id_not_found()
    {
        // Load test route
        $response = $this->get(route('postCloseClientDownloadDocument', [
            'subdomain' => $this->title->subdomain,
            'document' => 0,
        ]));

        $response->assertStatus(404);
        $response->assertSessionMissing('errors');
    }
}
