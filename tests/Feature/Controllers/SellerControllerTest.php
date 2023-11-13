<?php

namespace Tests\Feature\Controllers;

use App\Seller;
use App\SellerDocument;
use App\SellerFile;
use App\Services\GoogleMapsService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Account;
use App\User;
use App\Title;
use App\Branch;
use Storage;
use Notification;
use App\Notifications\UserDocumentNotification;
use Illuminate\Http\UploadedFile;

/**
 * Class SellerControllerTest
 * @package Tests\Feature\Controllers\Auth
 */
class SellerControllerTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    protected $account;
    protected $title;
    protected $user;
    protected $branch;
    protected $wireDoc;
    protected $sellerFiles;
    protected $documents;
    protected $seller;

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

        $this->user = factory(User::class)->create([
            'password' => '1234567',
            'role' => 'User',
            'title_id' => $this->title->id
        ]);

        // Associate account with user
        $this->user->account()->associate($this->account);
        $this->user->saveOrFail();

        // Create fake seller
        $this->seller = factory(Seller::class)->create([
            'title_id' => $this->title->id,
            'email' => $this->user->email,
            'phone' => '+15005550006',
            'phone_type' => 'mobile',
            'token' => '58XgQx54',
            'username' => $this->user->email . $this->user->id,
        ]);

        // Create fake branch
        $this->branch = factory(Branch::class)
            ->create([
                'title_id' => $this->title->id
            ]);

        // Create fake files and documents
        $fakeDocumentName = snake_case($this->faker->name) . '.doc';
        $address = '555 Acme Street, Austin, TX 78759';
        $this->documents = collect([]);
        $this->sellerFiles = factory(SellerFile::class, 3)
            ->create([
                'seller_id' => $this->seller->id,
                'branch_id' => $this->branch->id,
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

        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name);
        Storage::disk('s3')->put($this->documents->first()->name, file_get_contents($fakeDocumentFile));

        // Create seller session and login
        $this->createSellerSessionAndLogin();
    }

    /**
     *  Clean up the testing environment before the next test.
     */
    public function tearDown()
    {
        Storage::disk('s3')->delete($this->documents->first()->name);

        parent::tearDown();
    }

    /**
     * Create seller session and login
     */
    protected function createSellerSessionAndLogin()
    {
        // Check credentials and create seller session
        $this->post(route('sellerCheckCredentials', ['subdomain' => $this->title->seller_subdomain]), [
            'email' => $this->seller->email,
            'accessCode' => $this->seller->token,
        ]);

        // Login
        $this->post(route('sellerLoginPOST', ['subdomain' => $this->title->seller_subdomain]), [
            'email' => $this->seller->email,
            'accessCode' => $this->seller->token,
        ]);
    }

    /**
     * Test index method
     * Behaviour: success
     */
    public function test_index_method()
    {
        // Load test route
        $response = $this->get(route('sellerHome', ['subdomain' => $this->title->seller_subdomain]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
    }

    /**
     * Test index method, check property address and google maps street view visibility
     * Behaviour: success
     */
    public function test_index_method_success_with_property_address()
    {
        // Load test route
        $response = $this->get(route('sellerHome', ['subdomain' => $this->title->seller_subdomain]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee($this->sellerFiles->first()->address);
        $response->assertSee(htmlspecialchars($this->sellerFiles->first()->street_view_url));
    }

    /**
     * Test download document method
     * Behaviour: success
     *
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function test_download_document_method_success()
    {
        // Load test route
        $response = $this->get(route('sellerDownloadDocument', [
            'seller_subdomain' => $this->title->seller_subdomain,
            'document' => $this->documents->first()->id
        ]));

        // Get current file mime type
        $mimetype = Storage::disk('s3')->getMimetype($this->documents->first()->path);

        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $this->assertTrue($response->headers->get('content-type') === $mimetype);
        $this->assertTrue($response->headers->get('content-description') === 'File Transfer');
        $this->assertTrue($response->headers->get('content-disposition') === 'attachment; filename="' . $this->documents->first()->name . '"');
    }

    /**
     * Test download document method
     * Behaviour: fail
     */
    public function test_download_document_method_fail_check_access()
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
        $newFile = factory(SellerFile::class)->create([
            'seller_id' => $newSeller->id,
            'branch_id' => $this->branch->id,
            'created_by_user_id' => $this->user->id,
        ]);
        $newDocument = factory(SellerDocument::class)->create([
            'seller_file_id' => $newFile->id
        ]);

        // Load test route
        $response = $this->get(route('sellerDownloadDocument', [
            'subdomain' => $this->title->seller_subdomain,
            'document' => $newDocument->id
        ]));
        $response->assertStatus(302);
        $response->assertSessionMissing('errors');
        $response->assertRedirect(route('home'));
    }

    /**
     * Test upload document
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
        $response = $this->post(route('sellerUploadDocument', [
            'subdomain' => $this->title->seller_subdomain,
        ]), [
            'file' => $fakeDocumentFile
        ]);
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('File uploaded');

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test upload document, demo account
     * Behaviour: fail
     */
    public function test_upload_document_method_fail_demo_account()
    {
        // Update account name
        $this->account->name = 'Demo Account';
        $this->account->saveOrFail();

        // Load test route
        $response = $this->post(route('sellerUploadDocument', [
            'subdomain' => $this->title->seller_subdomain,
        ]));

        $response->assertStatus(403);
        $response->assertSee('Demo Account');
    }

    /**
     * Test upload document, with wrong mime type
     * Behaviour: fail
     */
    public function test_upload_document_method_fail_validation_file_mimes()
    {
        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create('file.mp3');

        // Load test route
        $response = $this->post(route('sellerUploadDocument', [
            'subdomain' => $this->title->seller_subdomain,
        ]), [
            'file' => $fakeDocumentFile
        ]);
        $response->assertStatus(422);
        $response->assertSee('The file must be a file of type: doc, docx, pdf, png, bmp, jpg, jpeg.');

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test upload document, with not valid file size
     * Behaviour: fail
     */
    public function test_upload_document_method_fail_validation_file_max_size()
    {
        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name, 5121); // add 1 extra byte

        // Load test route
        $response = $this->post(route('sellerUploadDocument', [
            'subdomain' => $this->title->seller_subdomain,
        ]), [
            'file' => $fakeDocumentFile
        ]);
        $response->assertStatus(422);
        $response->assertSee('The file may not be greater than');

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test upload document, check allows documents
     * Behaviour: fail
     */
    public function test_upload_document_method_fail_allows_seller_documents()
    {
        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name);

        // Load test route
        $response = $this->post(route('sellerUploadDocument', [
            'subdomain' => $this->title->seller_subdomain,
        ]), [
            'file' => $fakeDocumentFile
        ]);

        $response->assertStatus(403);
        $response->assertSee('No account permissions');

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test attach documents
     * Behaviour: success
     */
    public function test_attach_documents_method_success()
    {
        Notification::fake();

        $this->account->allows_seller_documents = true;
        $this->account->saveOrFail();

        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name);

        // Load test route
        $response = $this->post(route('sellerAttachDocuments', [
            'subdomain' => $this->title->seller_subdomain,
            'sellerFile' => $this->sellerFiles->first()->id
        ]), [
            'documents' => [
                'file' => $fakeDocumentFile
            ]
        ]);
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('sellerFileID');

        Notification::assertSentTo([$this->user], UserDocumentNotification::class);

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test attach documents, demo account
     * Behaviour: fail
     */
    public function test_attach_documents_method_fail_demo_account()
    {
        // Update account name
        $this->account->name = 'Demo Account';
        $this->account->saveOrFail();

        // Load test route
        $response = $this->post(route('sellerAttachDocuments', [
            'subdomain' => $this->title->seller_subdomain,
            'sellerFile' => $this->sellerFiles->first()->id
        ]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('demo account');
    }

    /**
     * Test attach document, with wrong mime type
     * Behaviour: fail
     */
    public function test_attach_documents_method_fail_validation_file_mimes()
    {
        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create('file.mp3');

        // Load test route
        $response = $this->post(route('sellerAttachDocuments', [
            'subdomain' => $this->title->seller_subdomain,
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
     * Test attach document, with not valid file size
     * Behaviour: fail
     */
    public function test_attach_documents_method_fail_validation_file_max_size()
    {
        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name, 5121); // add 1 extra kb

        // Load test route
        $response = $this->post(route('sellerAttachDocuments', [
            'subdomain' => $this->title->seller_subdomain,
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
     * Test attach document, check access
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
        $newFile = factory(SellerFile::class)->create([
            'seller_id' => $newSeller->id,
            'branch_id' => $this->branch->id,
            'created_by_user_id' => $this->user->id,
        ]);

        // Load test route
        $response = $this->post(route('sellerAttachDocuments', [
            'subdomain' => $this->title->seller_subdomain,
            'sellerFile' => $newFile->id
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
     * Test attach document, check number of documents
     * Behaviour: fail
     */
    public function test_attach_documents_method_fail_number_of_documents()
    {
        $fakeDocumentName = snake_case($this->faker->name) . '.doc';

        // Update account and allow uploading documents
        $this->account->allows_seller_documents = true;
        $this->account->saveOrFail();

        // Create more documents for this seller file
        factory(SellerDocument::class, 9)->create([
            'seller_file_id' => $this->sellerFiles->first()->id,
            'path' => $fakeDocumentName,
            'name' => $fakeDocumentName
        ]);

        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name);

        // Load test route
        $response = $this->post(route('sellerAttachDocuments', [
            'subdomain' => $this->title->seller_subdomain,
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
     * Test remove document
     * Behaviour: success
     */
    public function test_remove_document_method_success()
    {
        // Load test route
        $response = $this->delete(route('sellerRemoveDocument', [
            'subdomain' => $this->title->seller_subdomain,
            'document' => $this->documents->first()->id
        ]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('sellerFileID');
    }

    /**
     * Test remove document, use demo account
     * Behaviour: fail
     */
    public function test_remove_document_method_fail_demo_account()
    {
        // Update account name
        $this->account->name = 'Demo Account';
        $this->account->saveOrFail();

        // Load test route
        $response = $this->delete(route('sellerRemoveDocument', [
            'subdomain' => $this->title->seller_subdomain,
            'document' => $this->documents->first()->id
        ]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('demo account');
    }

    /**
     * Test remove document, fail access
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
        $newFile = factory(SellerFile::class)->create([
            'seller_id' => $newSeller->id,
            'branch_id' => $this->branch->id,
            'created_by_user_id' => $this->user->id,
        ]);
        $newDocument = factory(SellerDocument::class)->create([
            'seller_file_id' => $newFile->id
        ]);

        // Load test route
        $response = $this->delete(route('sellerRemoveDocument', [
            'subdomain' => $this->title->seller_subdomain,
            'document' => $newDocument->id
        ]));
        $response->assertStatus(403);
        $response->assertSee('error');
    }
}
