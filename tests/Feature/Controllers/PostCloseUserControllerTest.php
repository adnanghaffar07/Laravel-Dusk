<?php

namespace Tests\Feature\Controllers;

use App\Notifications\PostClose\WelcomeClientNotification;
use App\PostCloseClient;
use App\PostCloseDocument;
use App\PostCloseFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
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

class PostCloseUserControllerTest extends TestCase
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

    protected $postCloseClient;
    protected $postCloseFile;
    protected $postCloseDocument;

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
            'subdomain' => $this->faker->word,
            'post_close_subdomain' => $this->faker->word
        ]);

        // Create fake branch
        $this->branch = factory(Branch::class)
            ->create([
                'title_id' => $this->title->id,
                'post_close_support_phone' => '+15005550006',
                'post_close_support_email' => 'postcloseemail@test.test',
                'post_close_support_website' => 'not.a.real.website.com',
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
        $this->postCloseFile = factory(PostCloseFile::class)->create([
            'post_close_client_id' => $this->postCloseClient->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'ref_number' => 'refnum',
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
        $this->be($this->user);
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
     * @param int $numDocuments
     * @return Collection
     */
    private function makePostCloseFileWithRelations(int $numDocuments = 1)
    {
        // Create fake PostCloseClient
        $email = $this->faker->unique()->email;
        $postCloseClient = factory(PostCloseClient::class)->create([
            'title_id' => $this->title->id,
            'email' => $email,
            'phone' => $this->faker->unique()->numerify('+1500555####'),
            'username' => $email . $this->user->id,
            'password' => Hash::make('1234567'),
        ]);

        // Create fake PostCloseFile
        $fakePostCloseDocumentName = snake_case($this->faker->unique()->name) . '.doc';
        $postCloseFiles = factory(PostCloseFile::class, $numDocuments)->create([
            'post_close_client_id' => $postCloseClient->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'address' => $this->faker->unique()->streetAddress,
            'ref_number' => $this->faker->unique()->lexify('?????????????'),
        ])->each(function ($file) use ($fakePostCloseDocumentName) {
            factory(PostCloseDocument::class)->create([
                'post_close_file_id' => $file->id,
                'uploader_role' => 'user',
                'uploader_id' => $this->user->id,
                'path' => $fakePostCloseDocumentName,
                'name' => $fakePostCloseDocumentName,
            ]);
        });

        return $postCloseFiles->fresh(['client', 'documents', 'branch', 'branch.title']);
    }

    /**
     * @param string $phrase
     * @param int $iterator
     * @param int $recordsFiltered
     * @param int $recordsTotal
     */
    private function requestSearchPostCloseTable(string $phrase, int $iterator, int $recordsFiltered, int $recordsTotal)
    {
        $response = $this->post(route('postCloseFilesTable'), [
            'draw' => $iterator,
            'start' => 0,
            'length' => 100,
            'search' => [
                'value' => $phrase
            ],
        ]);
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertJson([
            'recordsFiltered' => $recordsFiltered,
            'recordsTotal' => $recordsTotal,
        ]);
    }

    /**
     * Test userPostCloseHome route
     * Behaviour: success
     */
    public function test_index_method_success()
    {
        // Load test route
        $response = $this->get(route('userPostCloseHome'));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('Add Post-Close Clients');
    }

    /**
     * Test storePostCloseFile route
     * Behaviour: success
     */
    public function test_store_post_close_file_method_success()
    {
        $fakePostCloseDocumentName = snake_case($this->faker->name) . '.doc';

        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($fakePostCloseDocumentName);
        // Load test route
        $response = $this->post(route('addPostCloseFile'), [
            'email' => $this->faker->email,
            'address' => $this->faker->address,
            'refnum' => 'refnum',
            'company' => $this->title->id,
            'branch' => $this->branch->id,
            'notes' => 'notes',
            'documents' => [
                'file' => $fakeDocumentFile
            ]
        ]);

        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('added post-close file');

        // Clean up after test
        Storage::disk('s3')->delete($fakePostCloseDocumentName);
    }

    /**
     * Test storePostCloseFile route
     * Behaviour: fail
     */
    public function test_store_post_close_file_method_fail_check_access()
    {
        $oldPhone = $this->branch->post_close_support_phone;
        $this->branch->update(['post_close_support_phone' => null]);

        $fakePostCloseDocumentName = snake_case($this->faker->name) . '.doc';

        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($fakePostCloseDocumentName);
        // Load test route
        $response = $this->post(route('addPostCloseFile'), [
            'email' => $this->faker->email,
            'address' => $this->faker->address,
            'refnum' => 'refnum',
            'company' => $this->title->id,
            'branch' => $this->branch->id,
            'notes' => 'notes',
            'documents' => [
                'file' => $fakeDocumentFile
            ]
        ]);
        $response->assertStatus(501);
        $response->assertSee('custom_error');
        $response->assertSee('This branch is missing information required for post-close. Please contact ' . env('MAIL_SUPPORT_ADDRESS'));

        $this->branch->update(['post_close_support_phone' => $oldPhone]);

        $oldSubdomain = $this->title->post_close_subdomain;
        $this->title->update(['post_close_subdomain' => null]);

        $response = $this->post(route('addPostCloseFile'), [
            'email' => $this->faker->email,
            'address' => $this->faker->address,
            'refnum' => 'refnum',
            'company' => $this->title->id,
            'branch' => $this->branch->id,
            'notes' => 'notes',
            'documents' => [
                'file' => $fakeDocumentFile
            ]
        ]);

        $response->assertStatus(501);
        $response->assertSee('custom_error');
        $response->assertSee('This company is missing information required for post-close. Please contact ' . env('MAIL_SUPPORT_ADDRESS'));

        $this->title->update(['post_close_subdomain' => $oldSubdomain]);
    }

    /**
     * Test postCloseFilesTable route
     * Behaviour: success
     */
    public function test_table_method_success()
    {
        // Load test route
        $response = $this->post(route('postCloseFilesTable'), [
            'draw' => 1,
            'start' => 0,
            'length' => 3,
            'order' => [
                0 => [
                    'column' => 4,
                    'dir' => 'asc',
                    'data' => 'data data',
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data' => [
                0 => [
                    'address',
                    'branch',
                    'company',
                    'created_at',
                    'documents',
                    'email',
                    'notified_at',
                    'options',
                    'ref_number',
                    'retrieved_at',
                ]
            ]
        ]);
    }

    /**
     * Test postCloseFilesTable route, with search filter
     * Behaviour: success
     */
    public function test_table_method_success_with_search()
    {

        factory(PostCloseFile::class)->create([
            'post_close_client_id' => $this->postCloseClient->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'ref_number' => 'unique word',
        ]);

        // Load test route
        $response = $this->post(route('postCloseFilesTable'), [
            'draw' => 1,
            'start' => 0,
            'length' => 3,
            'search' => [
                'value' => 'unique word'
            ],
            'order' => [
                0 => [
                    'column' => 4,
                    'dir' => 'asc',
                    'data' => 'data data',
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertJson([
            'recordsFiltered' => 1,
        ]);
    }

    /**
     * Test postCloseFilesTable route with search filter by each of column values
     * Behaviour: success
     */
    public function test_table_method_success_with_search_by_columns()
    {
        $numTestPostCloseFiles = 20;
        $numTestDocumentsPerEach = 3;

        $postCloseFiles = collect();
        for ($i = 0; $i < $numTestPostCloseFiles; $i++) {
            $postCloseFiles->push($this->makePostCloseFileWithRelations($numTestDocumentsPerEach));
        }

        foreach ($postCloseFiles as $key => $files) {
            // search by email
            $this->requestSearchPostCloseTable(
                $files->first()->client->email,
                ($key + 1),
                $numTestDocumentsPerEach,
                (($numTestPostCloseFiles * $numTestDocumentsPerEach) + 1) // 1 is for one doc, created by setUp method
            );

//            // search by phone - not in table view right now
//            $this->requestSearchPostCloseTable(
//                $files->first()->client->phone,
//                ($key + 1),
//                $numTestDocumentsPerEach,
//                (($numTestPostCloseFiles * $numTestDocumentsPerEach) + 1) // 1 is for one doc, created by setUp method
//            );

            // search by address
            $this->requestSearchPostCloseTable(
                $files->first()->address,
                ($key + 1),
                $numTestDocumentsPerEach,
                (($numTestPostCloseFiles * $numTestDocumentsPerEach) + 1) // 1 is for one doc, created by setUp method
            );

            // search by ref_number
            $this->requestSearchPostCloseTable(
                $files->first()->ref_number,
                ($key + 1),
                $numTestDocumentsPerEach,
                (($numTestPostCloseFiles * $numTestDocumentsPerEach) + 1) // 1 is for one doc, created by setUp method
            );

            // search by branch name
            $this->requestSearchPostCloseTable(
                $files->first()->branch->title->name,
                ($key + 1),
                (($numTestPostCloseFiles * $numTestDocumentsPerEach) + 1), // branch is the same for all post-clos files, so results must be same as total
                (($numTestPostCloseFiles * $numTestDocumentsPerEach) + 1) // 1 is for one doc, created by setUp method
            );
        }
    }

    /**
     * Test postCloseFilesTable route, without required fields
     * Behaviour: fail
     */
    public function test_table_method_fail_validation_fields_are_required()
    {
        // Load test route
        $response = $this->post(route('postCloseFilesTable'));
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'draw' => 'The draw field is required.',
            'start' => 'The start field is required.',
            'length' => 'The length field is required.',
        ]);
    }

    /**
     * Test userPostCloseDownloadDocument route
     * Behaviour: success
     */
    public function test_download_document_method_success()
    {
        // Load test route
        $response = $this->get(route('userPostCloseDownloadDocument', [
            'document' => $this->postCloseDocument->id
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
        $response = $this->get(route('userPostCloseDownloadDocument', [
            'document' => $newPostCloseDocument->id
        ]));
        $response->assertStatus(302);
        $response->assertSessionMissing('errors');
        $response->assertRedirect(route('userHome'));
    }

    /**
     * Test userPostCloseDownloadDocument route, with non-existing document
     * Behaviour: fail
     */
    public function test_download_document_method_fail_document_id_not_found()
    {
        // Load test route
        $response = $this->get(route('userPostCloseDownloadDocument', [
            'document' => 0
        ]));
        $response->assertStatus(404);
        $response->assertSessionMissing('errors');
    }

    /**
     * Test userPostCloseAttachDocuments route
     * Behaviour: success
     */
    public function test_attach_documents_method_success_without_notification()
    {
        $fakePostCloseDocumentName = snake_case($this->faker->name) . '.doc';

        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($fakePostCloseDocumentName);

        // Load test route
        $response = $this->post(route('userPostCloseAttachDocuments', [
            'file' => $this->postCloseFile->id
        ]), [
            'documents' => [
                'file' => $fakeDocumentFile
            ]
        ]);

        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('Documents attached without notification');

        // Clean up after test
        Storage::disk('s3')->delete($fakePostCloseDocumentName);
    }

    /**
     * Test userPostCloseAttachDocuments route
     * Behaviour: success
     */
    public function test_attach_documents_method_success_with_notification()
    {
        Notification::fake();

        $fakePostCloseDocumentName = snake_case($this->faker->name) . '.doc';

        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($fakePostCloseDocumentName);

        // Load test route
        $response = $this->post(route('userPostCloseAttachDocuments', [
            'file' => $this->postCloseFile->id
        ]), [
            'notify' => 'notify',
            'documents' => [
                'file' => $fakeDocumentFile
            ]
        ]);

        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('Documents attached with notification');

        Notification::assertSentTo([$this->postCloseClient], WelcomeClientNotification::class);

        // Clean up after test
        Storage::disk('s3')->delete($fakePostCloseDocumentName);
    }

    /**
     * Test userPostCloseAttachDocuments route, with demo account
     * Behaviour: fail
     */
    public function test_attach_documents_method_fail_demo_account()
    {
        // Update account name
        $this->account->name = 'Demo Account';
        $this->account->saveOrFail();

        // Load test route
        $response = $this->post(route('userPostCloseAttachDocuments', [
            'file' => $this->postCloseFile->id
        ]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('demo account');
    }

    /**
     * Test userPostCloseAttachDocuments route, with wrong mime type
     * Behaviour: fail
     */
    public function test_attach_documents_method_fail_validation_file_mimes()
    {
        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create('file.mp3');

        // Load test route
        $response = $this->post(route('userPostCloseAttachDocuments', [
            'file' => $this->postCloseFile->id
        ]), [
            'documents' => [
                'file' => $fakeDocumentFile
            ]
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors('documents.file');

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test userPostCloseAttachDocuments route, with not valid file size
     * Behaviour: fail
     */
    public function test_attach_documents_method_fail_validation_file_max_size()
    {
        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->postCloseDocument->name, 5121); // add 1 extra kb

        // Load test route
        $response = $this->post(route('userPostCloseAttachDocuments', [
            'file' => $this->postCloseFile->id
        ]), [
            'documents' => [
                'file' => $fakeDocumentFile
            ]
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('documents.file');

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test userPostCloseAttachDocuments route, check user access
     * Behaviour: fail
     */
    public function test_attach_documents_method_fail_check_access()
    {
        $fakePostCloseDocumentName = snake_case($this->faker->name) . '.doc';

        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($fakePostCloseDocumentName);

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
        $response = $this->post(route('userPostCloseAttachDocuments', [
            'file' => $newPostCloseFile->id
        ]), [
            'documents' => [
                'file' => $fakeDocumentFile
            ]
        ]);

        $response->assertStatus(403);
        $response->assertSee('error');

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test userPostCloseAttachDocuments route, check number of documents
     * Behaviour: fail
     */
    public function test_attach_documents_method_fail_number_of_documents()
    {
        $fakePostCloseDocumentName = snake_case($this->faker->name) . '.doc';

        // Create more PostCloseDocument files for this client
        factory(PostCloseDocument::class, 10)->create([
            'post_close_file_id' => $this->postCloseFile->id,
            'uploader_role' => 'user',
            'uploader_id' => $this->user->id,
            'path' => $fakePostCloseDocumentName,
            'name' => $fakePostCloseDocumentName,
        ]);

        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->postCloseDocument->name);

        // Load test route
        $response = $this->post(route('userPostCloseAttachDocuments', [
            'file' => $this->postCloseFile->id
        ]), [
            'documents' => [
                'file' => $fakeDocumentFile
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => [
            'documents'
        ]]);

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test userPostCloseUploadDocument route
     * Behaviour: success
     */
    public function test_upload_document_method_success()
    {
        $fakePostCloseDocumentName = snake_case($this->faker->name) . '.doc';

        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($fakePostCloseDocumentName);

        // Load test route
        $response = $this->post(route('userPostCloseUploadDocument'), [
            'file' => $fakeDocumentFile
        ]);

        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('File uploaded');

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test userPostCloseUploadDocument route, demo account
     * Behaviour: fail
     */
    public function test_upload_document_method_fail_demo_account()
    {
        // Update account name
        $this->account->name = 'Demo Account';
        $this->account->saveOrFail();

        // Load test route
        $response = $this->post(route('userPostCloseUploadDocument'));

        $response->assertStatus(403);
        $response->assertSee('Demo Account');
    }

    /**
     * Test userPostCloseUploadDocument route, with wrong mime type
     * Behaviour: fail
     */
    public function test_upload_document_method_fail_validation_file_mimes()
    {
        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create('file.mp3');

        // Load test route
        $response = $this->post(route('userPostCloseUploadDocument'), [
            'file' => $fakeDocumentFile
        ]);

        $response->assertStatus(422);
        $response->assertSee('The file must be a file of type: doc, docx, pdf, png, bmp, jpg, jpeg, zip.');

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test userPostCloseUploadDocument route, with not valid file size
     * Behaviour: fail
     */
    public function test_upload_document_method_fail_validation_file_max_size()
    {
        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->postCloseDocument->name, 5121); // add 1 extra kb

        // Load test route
        $response = $this->post(route('userPostCloseUploadDocument'), [
            'file' => $fakeDocumentFile
        ]);

        $response->assertStatus(422);
        $response->assertSee('The file may not be greater than');

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test userPostCloseRemoveDocument route
     * Behaviour: success
     */
    public function test_remove_document_method_success()
    {
        // Load test route
        $response = $this->delete(route('userPostCloseRemoveDocument', [
            'document' => $this->postCloseDocument->id
        ]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('deleted document');
    }

    /**
     * Test userPostCloseRemoveDocument route, use demo account
     * Behaviour: fail
     */
    public function test_remove_document_method_fail_demo_account()
    {
        // Update account name
        $this->account->name = 'Demo Account';
        $this->account->saveOrFail();

        // Load test route
        $response = $this->delete(route('userPostCloseRemoveDocument', [
            'document' => $this->postCloseDocument->id
        ]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('demo account');
    }

    /**
     * Test userPostCloseRemoveDocument route, fail access
     * Behaviour: fail
     */
    public function test_remove_document_method_fail_check_access()
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
        $response = $this->delete(route('userPostCloseRemoveDocument', [
            'document' => $newPostCloseDocument->id
        ]));

        $response->assertStatus(403);
        $response->assertSee('error');
    }

    /**
     * Test notifyPostCloseFile route
     * Behaviour: success
     */
    public function test_notify_method_success()
    {
        Notification::fake();

        // Load test route
        $response = $this->post(route('notifyPostCloseFile', [
            'file' => $this->postCloseFile->id
        ]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('notified post-close client');

        Notification::assertSentTo([$this->postCloseClient], WelcomeClientNotification::class);
    }

    /**
     * Test notifyPostCloseFile route, use demo account
     * Behaviour: fail
     */
    public function test_notify_method_fail_demo_account()
    {
        // Update account name
        $this->account->name = 'Demo Account';
        $this->account->saveOrFail();

        // Load test route
        $response = $this->post(route('notifyPostCloseFile', [
            'file' => $this->postCloseFile->id
        ]));

        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('demo account');
    }

    /**
     * Test notifyPostCloseFile route, fail access
     * Behaviour: fail
     */
    public function test_notify_method_fail_check_access()
    {
        // Recreate some data
        $newBranch = factory(Branch::class)
            ->create([
                'title_id' => $this->title->id
            ]);

        $newTitle = factory(Title::class)->create([
            'account_id' => $this->account->id,
            'subdomain' => $this->faker->word
        ]);

        $newPostCloseClient = factory(PostCloseClient::class)->create([
            'title_id' => $newTitle->id,
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

        // Load test route
        $response = $this->post(route('notifyPostCloseFile', [
            'file' => $newPostCloseFile->id
        ]));

        $response->assertStatus(403);
        $response->assertSee('error');
    }

    /**
     * Test deletePostCloseFile route
     * Behaviour: success
     */
    public function test_destroy_method_success()
    {
        // Re-create some fake data
        $newFile = factory(PostCloseFile::class)->create([
            'post_close_client_id' => $this->postCloseClient->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'ref_number' => 'refnum',
        ]);

        $fakePostCloseDocumentName = snake_case($this->faker->name) . '.doc';
        $newDocument = factory(PostCloseDocument::class)->create([
            'post_close_file_id' => $this->postCloseFile->id,
            'uploader_role' => 'user',
            'uploader_id' => $this->user->id,
            'path' => $fakePostCloseDocumentName,
            'name' => $fakePostCloseDocumentName,
        ]);

        // Create and upload fake post close file
        $fakePostCloseDocumentFile = UploadedFile::fake()->create($newDocument->name);
        Storage::disk('s3')->put($newDocument->name, file_get_contents($fakePostCloseDocumentFile));

        // Load test route
        $response = $this->delete(route('deletePostCloseFile', [
            'file' => $newFile->id
        ]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('deleted post-close client');

        // Clean up after test
        Storage::disk('s3')->delete($fakePostCloseDocumentFile);
    }

    /**
     * Test deletePostCloseFile route, use demo account
     * Behaviour: fail
     */
    public function test_destroy_method_fail_demo_account()
    {
        // Update account name
        $this->account->name = 'Demo Account';
        $this->account->saveOrFail();

        // Load test route
        $response = $this->delete(route('deletePostCloseFile', [
            'file' => $this->postCloseFile->id
        ]));

        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('demo account');
    }

    /**
     * Test deletePostCloseFile route, check user access
     * Behaviour: fail
     */
    public function test_destroy_method_fail_check_access()
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

        // Create and upload fake post close file
        $fakePostCloseDocumentFile = UploadedFile::fake()->create($newPostCloseDocument->name);
        Storage::disk('s3')->put($newPostCloseDocument->name, file_get_contents($fakePostCloseDocumentFile));

        // Load test route
        $response = $this->delete(route('deletePostCloseFile', [
            'file' => $newPostCloseFile->id
        ]));

        $response->assertStatus(403);
        $response->assertSessionMissing('errors');
        $response->assertSee('error');

        // Clean up after test
        Storage::disk('s3')->delete($fakePostCloseDocumentFile);
    }
}
