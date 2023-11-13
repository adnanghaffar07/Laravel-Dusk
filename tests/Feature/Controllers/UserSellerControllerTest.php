<?php

namespace Tests\Feature\Controllers;

use App\Notifications\RequestSellerInformationNotification;
use App\Notifications\SellerDocumentNotification;
use App\Seller;
use App\SellerDocument;
use App\SellerFile;
use App\Services\GoogleMapsService;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Account;
use App\Title;
use App\User;
use App\Branch;
use Hash;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Storage;

/**
 * Class UserSellerControllerTest
 * @package Tests\Feature\Controllers
 */
class UserSellerControllerTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    protected $account;
    protected $branch;
    protected $title;
    protected $user;
    protected $seller;
    protected $sellerFiles;
    protected $documents;

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
        $subdomain = $this->faker->word;
        $this->title = factory(Title::class)->create([
            'account_id' => $this->account->id,
            'subdomain' => $subdomain,
            'seller_subdomain' => $subdomain
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

        // Create fake seller
        $this->seller = factory(Seller::class)->create([
            'title_id' => $this->title->id,
            'email' => $this->user->email,
            'phone' => '+15005550006',
            'phone_type' => 'mobile',
            'token' => '58XgQx54',
            'username' => $this->user->email . $this->user->id,
        ]);

        // Create fake files and documents
        $fakeDocumentName = snake_case($this->faker->name) . '.doc';
        $address = '555 Acme Street, Austin, TX 78759';
        $this->documents = collect([]);
        $this->sellerFiles = factory(SellerFile::class, 3)
            ->create([
                'seller_id' => $this->seller->id,
                'branch_id' => $this->branch->id,
                'ref_number' => 'test',
                'created_by_user_id' => $this->user->id,
                'address' => $address,
                'street_view_url' => GoogleMapsService::getStreetViewImgURL($address),
            ])->each(function ($file) use ($fakeDocumentName) {
                $this->documents->push(factory(SellerDocument::class)->create([
                    'seller_file_id' => $file->id,
                    'path' => $fakeDocumentName,
                    'name' => $fakeDocumentName
                ]));
            });

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
        parent::tearDown();
    }

    /**
     * @param int $numDocuments
     * @return \Illuminate\Support\Collection
     */
    private function makeSellerFileWithRelations(int $numDocuments = 1)
    {
        $sellerEmail = $this->faker->unique()->email;
        $newSeller = factory(Seller::class)->create([
            'title_id' => $this->title->id,
            'email' => $sellerEmail,
            'phone' => $this->faker->unique()->numerify('+1500555####'),
            'phone_type' => 'mobile',
            'token' => '67X0Q0x1',
            'username' => $sellerEmail . $this->user->id,
        ]);

        // Create fake files and documents
        $fakeDocumentName = snake_case($this->faker->unique()->name) . '.doc';
        $sellerFiles = factory(SellerFile::class, $numDocuments)
            ->create([
                'seller_id' => $newSeller->id,
                'branch_id' => $this->branch->id,
                'ref_number' => $this->faker->unique()->lexify('?????????????'),
                'address' => $this->faker->unique()->streetAddress,
                'created_by_user_id' => $this->user->id,
            ])->each(function ($file) use ($fakeDocumentName) {
                factory(SellerDocument::class)->create([
                    'seller_file_id' => $file->id,
                    'path' => $fakeDocumentName,
                    'name' => $fakeDocumentName
                ]);
            });

        return $sellerFiles->fresh(['seller', 'documents', 'branch', 'branch.title']);
    }

    /**
     * @param string $phrase
     * @param int $iterator
     * @param int $recordsFiltered
     * @param int $recordsTotal
     */
    private function requestSearchSellersTable(string $phrase, int $iterator, int $recordsFiltered, int $recordsTotal)
    {
        $response = $this->post(route('sellersTable'), [
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
     * Test index method
     * Behaviour: success
     */
    public function test_index_method_success()
    {
        // Load test route
        $response = $this->get(route('userSellerIndex'));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('Add Clients');
    }

    /**
     * Test storeSellerFile method
     * Behaviour: success
     */
    public function test_store_seller_file_method_success()
    {
        // Load test route
        $response = $this->post(route('userStoreSellerFile'), [
            'email' => $this->faker->email,
            'phone' => null,
            'notes' => 'notes',
            'refnum' => $this->sellerFiles->first()->ref_number,
        ]);

        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('added seller file');
    }

    /**
     * Test storeSellerFile method, with request_wire notification
     * Behaviour: success
     */
    public function test_store_seller_file_method_success_with_request_wire_notification()
    {
        $this->account->allows_welcome_email = 1;
        $this->account->saveOrFail();

        Notification::fake();

        $sellerEmail = $this->faker->email;

        // Load test route
        $response = $this->post(route('userStoreSellerFile'), [
            'email' => $sellerEmail,
            'phone' => null,
            'notes' => 'notes',
            'refnum' => $this->sellerFiles->first()->ref_number,
            'notify' => 'notify',
        ]);
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('added seller file');

        $seller = Seller::where('email', $sellerEmail)->firstOrFail();
        Notification::assertSentTo([$seller], RequestSellerInformationNotification::class);
    }

    /**
     * Test storeSellerFile method
     * Behaviour: fail
     */
    public function test_store_seller_file_method_fail_demo_account()
    {
        // Update account name
        $this->account->name = 'Demo Account';
        $this->account->saveOrFail();

        // Load test route
        $response = $this->post(route('userStoreSellerFile'), [
            'email' => $this->faker->email,
            'phone' => null,
            'notes' => 'notes',
            'refnum' => $this->sellerFiles->first()->ref_number,
        ]);
        $response->assertStatus(200);
        $response->assertSee('added demo client');
    }

    /**
     * Test storeSellerFile method, fail email verification by kickbox.io service
     * Docs: https://docs.kickbox.com/docs/sandbox-api
     * Behaviour: fail
     */
    public function test_store_seller_file_method_fail_email_verify_by_kickbox_io()
    {
        // Load test route
        $response = $this->post(route('userStoreSellerFile'), [
            'email' => 'rejected-email@example.com',
            'phone' => null,
            'notes' => 'notes',
            'refnum' => $this->sellerFiles->first()->ref_number,
        ]);
        $response->assertStatus(422);
        $response->assertJsonFragment(
            [
                'errors' => [
                    'email' => ['This email address is undeliverable. Please verify the address.']
            ]
        ]);
    }

    /**
     * Test storeSellerFile method, without required fields
     * Behaviour: fail
     */
    public function test_store_seller_file_method_fail_validation_fields_are_required()
    {
        // Load test route
        $response = $this->post(route('userStoreSellerFile'), []);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'email' => 'The email field is required.',
            'refnum' => 'You must enter a reference number.',
        ]);
    }

    /**
     * Test storeSellerFile method, with invalid phone number
     * Behaviour: fail
     */
    public function test_store_seller_file_method_fail_validation_phone_invalid_number()
    {
        // Load test route
        $response = $this->post(route('userStoreSellerFile'), [
            'email' => $this->faker->email,
            'phone' => '+78123321123321',
            'notes' => 'notes',
            'refnum' => $this->sellerFiles->first()->ref_number,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'phone' => 'The phone number must be in a valid format.'
        ]);
    }

    /**
     * Test storeSellerFile method, validate maximum documents
     * Behaviour: fail
     */
    public function test_store_seller_file_method_fail_maximum_documents()
    {
        // Update current account
        $this->account->allows_seller_documents = true;
        $this->account->saveOrFail();

        $fakeDocumentName = snake_case($this->faker->name) . '.doc';

        // Create more documents for this seller file
        factory(SellerDocument::class, 11)->create([
            'seller_file_id' => $this->sellerFiles->first()->id,
            'path' => $fakeDocumentName,
            'name' => $fakeDocumentName,
            'uploader_role' => 'user'
        ]);

        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name);

        // Load test route
        $response = $this->post(route('userStoreSellerFile'), [
            'email' => $this->seller->email,
            'phone' => '+15005550006',
            'notes' => 'notes',
            'refnum' => $this->sellerFiles->first()->ref_number,
            'documents' => [
                'file' => $fakeDocumentFile
            ],
        ]);
        $response->assertStatus(422);
        $response->assertSessionMissing('errors');
        $response->assertJsonFragment([
            'errors' => [
                'documents' => 'There is a maximum of 10 files per client.'
            ]
        ]);
    }

    /**
     * Test downloadDocument method
     * Behaviour: success
     */
    public function test_download_document_method_success()
    {
        // Create and upload fake wire doc file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name);
        Storage::disk('s3')->put($this->documents->first()->name, file_get_contents($fakeDocumentFile));

        // Load test route
        $response = $this->get(route('userDownloadSellerDocument', [
            'document' => $this->documents->first()->id
        ]));

        // Get current file mime type
        $mimetype = Storage::disk('s3')->getMimetype($this->documents->first()->path);

        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $this->assertTrue($response->headers->get('content-type') === $mimetype);
        $this->assertTrue($response->headers->get('content-description') === 'File Transfer');
        $this->assertTrue($response->headers->get('content-disposition') === 'attachment; filename="' . $this->documents->first()->name . '"');

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test downloadDocument method, check user access
     * Behaviour: fail
     */
    public function test_download_document_method_fail_user_access()
    {
        // Recreate some data for new document
        $sellerEmail = $this->faker->unique()->safeEmail;
        $newSeller = factory(Seller::class)->create([
            'title_id' => $this->title->id,
            'email' => $sellerEmail,
            'phone' => '+15005550006',
            'phone_type' => 'mobile',
            'token' => '67X0Q0x1',
            'username' => $sellerEmail . $this->user->id,
        ]);

        $newBranch = factory(Branch::class)
            ->create([
                'title_id' => $this->title->id
            ]);

        $newFile = factory(SellerFile::class)->create([
            'seller_id' => $newSeller->id,
            'branch_id' => $newBranch->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $newDocument = factory(SellerDocument::class)->create([
            'seller_file_id' => $newFile->id
        ]);

        // Load test route
        $response = $this->get(route('userDownloadSellerDocument', [
            'document' => $newDocument->id
        ]));
        $response->assertStatus(302);
        $response->assertSessionMissing('errors');
    }

    /**
     * Test downloadDocument method, with nonexist document
     * Behaviour: fail
     */
    public function test_download_document_method_fail_wire_doc_id_not_found()
    {
        // Load test route
        $response = $this->get(route('userDownloadSellerDocument', [
            'document' => 0
        ]));
        $response->assertStatus(404);
        $response->assertSessionMissing('errors');
    }

    /**
     * Test attachDocuments method, with notification
     * Behaviour: success
     */
    public function test_attach_documents_method_success_with_notification()
    {
        Notification::fake();

        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name);

        // Load test route
        $response = $this->post(route('userAttachSellerDocuments', [
            'sellerFile' => $this->sellerFiles->first()->id
        ]), [
            'notify' => 'notify',
            'documents' => [
                'file' => $fakeDocumentFile
            ]
        ]);
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('Documents attached with notification');

        Notification::assertSentTo([$this->seller], SellerDocumentNotification::class);

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test attachDocuments method, without notification
     * Behaviour: success
     */
    public function test_attach_documents_method_success_without_notification()
    {
        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name);

        // Load test route
        $response = $this->post(route('userAttachSellerDocuments', [
            'sellerFile' => $this->sellerFiles->first()->id
        ]), [
            'documents' => [
                'file' => $fakeDocumentFile
            ]
        ]);
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('Documents attached without notification');

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test attachDocuments method
     * Behaviour: fail
     */
    public function test_attach_documents_method_fail_demo_account()
    {
        // Update account name
        $this->account->name = 'Demo Account';
        $this->account->saveOrFail();

        // Load test route
        $response = $this->post(route('userAttachSellerDocuments', [
            'sellerFile' => $this->sellerFiles->first()->id
        ]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('demo account');
    }

    /**
     * Test attach documents, with wrong mime type
     * Behaviour: fail
     */
    public function test_attach_documents_method_fail_validation_file_mimes()
    {
        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create('file.mp3');

        // Load test route
        $response = $this->post(route('userAttachSellerDocuments', [
            'sellerFile' => $this->sellerFiles->first()->id
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
     * Test attach documents, with not valid file size
     * Behaviour: fail
     */
    public function test_attach_documents_method_fail_validation_file_max_size()
    {
        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name, 5121); // add 1 extra kb

        // Load test route
        $response = $this->post(route('userAttachSellerDocuments', [
            'sellerFile' => $this->sellerFiles->first()->id
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
     * Test attach documents, check access
     * Behaviour: fail
     */
    public function test_attach_documents_method_fail_check_access()
    {
        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name);

        // Recreate some data for new document
        $sellerEmail = $this->faker->unique()->safeEmail;
        $newSeller = factory(Seller::class)->create([
            'title_id' => $this->title->id,
            'email' => $sellerEmail,
            'phone' => '+15005550006',
            'phone_type' => 'mobile',
            'token' => '67X0Q0x1',
            'username' => $sellerEmail . $this->user->id,
        ]);

        $newBranch = factory(Branch::class)
            ->create([
                'title_id' => $this->title->id
            ]);

        $newFile = factory(SellerFile::class)->create([
            'seller_id' => $newSeller->id,
            'branch_id' => $newBranch->id,
            'created_by_user_id' => $this->user->id,
        ]);

        // Load test route
        $response = $this->post(route('userAttachSellerDocuments', [
            'sellerFile' => $newFile->id
        ]), [
            'documents' => [
                'file' => $fakeDocumentFile
            ]
        ]);
        $response->assertStatus(403);

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test attach documents, check number of documents
     * Behaviour: fail
     */
    public function test_attach_documents_method_fail_number_of_documents()
    {
        $fakeDocumentName = snake_case($this->faker->name) . '.doc';

        // Update account and allow uploading documents
        $this->account->allows_seller_documents = true;
        $this->account->saveOrFail();

        // Create more documents for this seller file
        factory(SellerDocument::class, 10)->create([
            'seller_file_id' => $this->sellerFiles->first()->id,
            'path' => $fakeDocumentName,
            'name' => $fakeDocumentName,
            'uploader_role' => 'user'
        ]);

        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name);

        // Load test route
        $response = $this->post(route('userAttachSellerDocuments', [
            'sellerFile' => $this->sellerFiles->first()->id
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
     * Test upload document method
     * Behaviour: success
     */
    public function test_upload_document_method_success()
    {
        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name);

        // Update account and allow uploading documents
        $this->account->allows_seller_documents = true;
        $this->account->saveOrFail();

        // Load test route
        $response = $this->post(route('userUploadSellerDocument'), [
            'file' => $fakeDocumentFile
        ]);
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('File uploaded');

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test upload document method, demo account
     * Behaviour: fail
     */
    public function test_upload_document_method_fail_demo_account()
    {
        // Update account name
        $this->account->name = 'Demo Account';
        $this->account->saveOrFail();

        // Load test route
        $response = $this->post(route('userUploadSellerDocument'));
        $response->assertStatus(403);
        $response->assertSee('Demo Account');
    }

    /**
     * Test upload document method, with wrong mime type
     * Behaviour: fail
     */
    public function test_upload_document_method_fail_validation_file_mimes()
    {
        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create('file.mp3');

        // Load test route
        $response = $this->post(route('userUploadSellerDocument'), [
            'file' => $fakeDocumentFile
        ]);
        $response->assertStatus(422);
        $response->assertSee('The file must be a file of type: doc, docx, pdf, png, bmp, jpg, jpeg.');

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test upload document method, with not valid file size
     * Behaviour: fail
     */
    public function test_upload_document_method_fail_validation_file_max_size()
    {
        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name, 5121); // add 1 extra byte

        // Load test route
        $response = $this->post(route('userUploadSellerDocument'), [
            'file' => $fakeDocumentFile
        ]);
        $response->assertStatus(422);
        $response->assertSee('The file may not be greater than');

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test upload document method, check allows documents
     * Behaviour: fail
     */
    public function test_upload_document_method_fail_allows_seller_documents()
    {
        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name);

        // Load test route
        $response = $this->post(route('userUploadSellerDocument'), [
            'file' => $fakeDocumentFile
        ]);

        $response->assertStatus(403);
        $response->assertSee('No account permissions');

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test remove document method
     * Behaviour: success
     */
    public function test_remove_document_method_success()
    {
        // Create and upload fake wire doc file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name);
        Storage::disk('s3')->put($this->documents->first()->name, file_get_contents($fakeDocumentFile));

        // Load test route
        $response = $this->delete(route('userRemoveSellerDocument', [
            'document' => $this->documents->first()->id
        ]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('deleted document');

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test remove document method, use demo account
     * Behaviour: fail
     */
    public function test_remove_document_method_fail_demo_account()
    {
        // Update account name
        $this->account->name = 'Demo Account';
        $this->account->saveOrFail();

        // Load test route
        $response = $this->delete(route('userRemoveSellerDocument', [
            'document' => $this->documents->first()->id
        ]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('demo account');
    }

    /**
     * Test remove document method, fail access
     * Behaviour: fail
     */
    public function test_remove_document_method_fail_check_access()
    {
        // Recreate some data for new document
        $sellerEmail = $this->faker->unique()->safeEmail;
        $newSeller = factory(Seller::class)->create([
            'title_id' => $this->title->id,
            'email' => $sellerEmail,
            'phone' => '+15005550006',
            'phone_type' => 'mobile',
            'token' => '67X0Q0x1',
            'username' => $sellerEmail . $this->user->id,
        ]);

        $newBranch = factory(Branch::class)
            ->create([
                'title_id' => $this->title->id
            ]);

        $newFile = factory(SellerFile::class)->create([
            'seller_id' => $newSeller->id,
            'branch_id' => $newBranch->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $newDocument = factory(SellerDocument::class)->create([
            'seller_file_id' => $newFile->id
        ]);

        // Load test route
        $response = $this->delete(route('userRemoveSellerDocument', [
            'document' => $newDocument->id
        ]));
        $response->assertStatus(403);
    }

    /**
     * Test notify method
     * Behaviour: success
     */
    public function test_notify_method_success()
    {
        Notification::fake();

        // Load test route
        $response = $this->post(route('notifySeller', [
            'sellerFile' => $this->sellerFiles->first()->id
        ]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('notified seller');

        Notification::assertSentTo([$this->seller], RequestSellerInformationNotification::class);
    }

    /**
     * Test notify method, use demo account
     * Behaviour: fail
     */
    public function test_notify_method_fail_demo_account()
    {
        // Update account name
        $this->account->name = 'Demo Account';
        $this->account->saveOrFail();

        // Load test route
        $response = $this->post(route('notifySeller', [
            'sellerFile' => $this->sellerFiles->first()->id
        ]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('demo account');
    }

    /**
     * Test notify method, fail access
     * Behaviour: fail
     */
    public function test_notify_method_fail_check_access()
    {
        // Create title
        $newTitle = factory(Title::class)->create([
            'account_id' => $this->account->id,
            'subdomain' => $this->faker->word
        ]);

        // Recreate some data for new document
        $sellerEmail = $this->faker->unique()->safeEmail;
        $newSeller = factory(Seller::class)->create([
            'title_id' => $newTitle->id,
            'email' => $sellerEmail,
            'phone' => '+15005550006',
            'phone_type' => 'mobile',
            'token' => '67X0Q0x1',
            'username' => $sellerEmail . $this->user->id,
        ]);

        $newBranch = factory(Branch::class)
            ->create([
                'title_id' => $this->title->id
            ]);

        $newFile = factory(SellerFile::class)->create([
            'seller_id' => $newSeller->id,
            'branch_id' => $newBranch->id,
            'created_by_user_id' => $this->user->id,
        ]);

        // Load test route
        $response = $this->post(route('notifySeller', [
            'sellerFile' => $newFile->id
        ]));
        $response->assertStatus(403);
    }

    /**
     * Test destroy method
     * Behaviour: success
     */
    public function test_destroy_method_success()
    {
        // Re-create some fake data
        $newFile = factory(SellerFile::class)->create([
            'seller_id' => $this->seller->id,
            'branch_id' => $this->branch->id,
            'created_by_user_id' => $this->user->id,
        ]);
        $fakeDocumentName = snake_case($this->faker->name) . '.doc';
        $newDocument = factory(SellerDocument::class)->create([
            'seller_file_id' => $newFile->id,
            'path' => $fakeDocumentName,
            'name' => $fakeDocumentName
        ]);

        // Create and upload fake wire doc file
        $fakeDocumentFile = UploadedFile::fake()->create($newDocument->name);
        Storage::disk('s3')->put($newDocument->name, file_get_contents($fakeDocumentFile));

        // Load test route
        $response = $this->delete(route('deleteSellerFile', [
            'sellerFile' => $newFile->id
        ]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('deleted seller');

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test destroy method, use demo account
     * Behaviour: fail
     */
    public function test_destroy_method_fail_demo_account()
    {
        // Update account name
        $this->account->name = 'Demo Account';
        $this->account->saveOrFail();

        // Load test route
        $response = $this->delete(route('deleteSellerFile', [
            'sellerFile' => $this->sellerFiles->first()->id
        ]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('demo account');
    }

    /**
     * Test destroy method
     * Behaviour: fail
     */
    public function test_destroy_method_fail_check_access()
    {
        // Re-create some fake data
        $newBranch = factory(Branch::class)
            ->create([
                'title_id' => $this->title->id
            ]);

        $newFile = factory(SellerFile::class)->create([
            'seller_id' => $this->seller->id,
            'branch_id' => $newBranch->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $fakeDocumentName = snake_case($this->faker->name) . '.doc';
        $newDocument = factory(SellerDocument::class)->create([
            'seller_file_id' => $newFile->id,
            'path' => $fakeDocumentName,
            'name' => $fakeDocumentName
        ]);

        // Create and upload fake wire doc file
        $fakeDocumentFile = UploadedFile::fake()->create($newDocument->name);
        Storage::disk('s3')->put($newDocument->name, file_get_contents($fakeDocumentFile));

        // Load test route
        $response = $this->delete(route('deleteSellerFile', [
            'sellerFile' => $newFile->id
        ]));
        $response->assertStatus(403);
        $response->assertSessionMissing('errors');

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test sellersTable method
     * Behaviour: success
     */
    public function test_sellers_table_method_success()
    {
        // Load test route
        $response = $this->post(route('sellersTable'), [
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
                    'documents_received',
                    'documents_sent',
                    'email',
                    'notes',
                    'notified_at',
                    'options',
                    'phone',
                    'ref_number',
                    'retrieved_at',
                    'uploaded_at',
                ]
            ]
        ]);
    }

    /**
     * Test sellersTable method with search filter
     * Behaviour: success
     */
    public function test_sellers_table_method_success_with_search()
    {
        factory(SellerFile::class)->create([
            'seller_id' => $this->seller->id,
            'branch_id' => $this->branch->id,
            'created_by_user_id' => $this->user->id,
            'ref_number' => 'unique word',
        ]);

        // Load test route
        $response = $this->post(route('sellersTable'), [
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
     * Test sellersTable method with search filter by each of column values
     * Behaviour: success
     */
    public function test_sellers_table_method_success_with_search_by_columns()
    {
        $numTestSellerFiles = 20;
        $numTestDocumentsPerEach = 3;

        $sellerFiles = collect();
        for ($i = 0; $i < $numTestSellerFiles; $i++) {
            $sellerFiles->push($this->makeSellerFileWithRelations($numTestDocumentsPerEach));
        }

        foreach ($sellerFiles as $key => $files) {
            // search by email
            $this->requestSearchSellersTable(
                $files->first()->seller->email,
                ($key + 1),
                $numTestDocumentsPerEach,
                (($numTestSellerFiles * $numTestDocumentsPerEach) + 3) // 3 is for three docs, created by setUp method
            );

            // search by phone
            $this->requestSearchSellersTable(
                $files->first()->seller->phone,
                ($key + 1),
                $numTestDocumentsPerEach,
                (($numTestSellerFiles * $numTestDocumentsPerEach) + 3) // 3 is for three docs, created by setUp method
            );

            // search by address
            $this->requestSearchSellersTable(
                $files->first()->address,
                ($key + 1),
                $numTestDocumentsPerEach,
                (($numTestSellerFiles * $numTestDocumentsPerEach) + 3) // 3 is for three docs, created by setUp method
            );

            // search by ref_number
            $this->requestSearchSellersTable(
                $files->first()->ref_number,
                ($key + 1),
                $numTestDocumentsPerEach,
                (($numTestSellerFiles * $numTestDocumentsPerEach) + 3) // 3 is for three docs, created by setUp method
            );

            // search by branch name
            $this->requestSearchSellersTable(
                $files->first()->branch->title->name,
                ($key + 1),
                (($numTestSellerFiles * $numTestDocumentsPerEach) + 3), // branch is the same for all seller files, so results must be same as total
                (($numTestSellerFiles * $numTestDocumentsPerEach) + 3) // 3 is for three docs, created by setUp method
            );
        }
    }

    /**
     * Test sellersTable method with allowed documents
     * Behaviour: success
     */
    public function test_sellers_table_method_success_with_allowed_documents()
    {
        // Update current account
        $this->account->allows_seller_documents = true;
        $this->account->saveOrFail();

        // Load test route
        $response = $this->post(route('sellersTable'), [
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

        // Decode json response
        $decodeResponse = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        foreach ($decodeResponse['data'] as $id => $item) {
            $this->assertTrue($item['documents_sent'] !== '');
            $this->assertInternalType('string', $item['documents_sent']);
            $this->assertTrue($item['documents_received'] !== '');
            $this->assertInternalType('string', $item['documents_received']);
        }
    }

    /**
     * Test sellersTable method with allowed notes
     * Behaviour: success
     */
    public function test_sellers_table_method_success_with_allowed_notes()
    {
        // Update current account
        $this->account->allows_seller_notes = true;
        $this->account->saveOrFail();

        // Load test route
        $response = $this->post(route('sellersTable'), [
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

        // Decode json response
        $decodeResponse = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        foreach ($decodeResponse['data'] as $id => $item) {
            $this->assertTrue($item['notes'] !== '');
            $this->assertInternalType('string', $item['notes']);
        }
    }

    /**
     * Test sellersTable method, without required fields
     * Behaviour: fail
     */
    public function test_sellers_table_method_fail_validation_fields_are_required()
    {
        // Load test route
        $response = $this->post(route('sellersTable'));
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'draw' => 'The draw field is required.',
            'start' => 'The start field is required.',
            'length' => 'The length field is required.',
        ]);
    }
}
