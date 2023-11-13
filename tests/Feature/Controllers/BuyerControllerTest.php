<?php

namespace Tests\Feature\Controllers;

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
use Carbon\Carbon;
use Storage;
use Notification;
use App\Notifications\UserDocumentNotification;
use Illuminate\Http\UploadedFile;

/**
 * Class BuyerControllerTest
 * @package Tests\Feature\Controllers\Auth
 */
class BuyerControllerTest extends TestCase
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

        // Create fake wire doc
        $fakeWireDocName = snake_case($this->faker->name) . '.doc';
        $this->wireDoc = factory(WireDoc::class)
            ->create([
                'title_id' => $this->title->id,
                'path' => $fakeWireDocName,
                'name' => $fakeWireDocName
            ]);

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

        // Create buyer session and login
        $this->createBuyerSessionAndLogin();
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
     * Create buyer session and login
     */
    protected function createBuyerSessionAndLogin()
    {
        // Check credentials and create buyer session
        $this->post(route('buyerCheckCredentials', ['subdomain' => env('GENERIC_SUBDOMAIN')]), [
            'email' => $this->user->email,
            'password' => '1234567',
            'title_id' => $this->title->id,
            'terms' => true,
        ]);

        // Login
        $this->post(route('buyerLoginPOST', ['title' => env('GENERIC_SUBDOMAIN')]), [
            'email' => $this->user->email,
            'password' => '1234567',
            'title_id' => $this->title->id,
            'terms' => true,
        ]);
    }

    /**
     * Test index method, check update buyer files (retrieved_at field)
     * Behaviour: success
     */
    public function test_index_method_success_update_buyer_files()
    {
        // Delay some time before creating and updating file
        sleep(1);

        // Load test route
        $response = $this->get(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');

        // Check buyerFile retrieved_at update
        foreach ($this->buyerFiles as $file) {
            $fileUpdated = $this->buyer->buyerFiles()->find($file->id);
            $retrieveTimestampBeforeUpdate = Carbon::parse($file->retrieved_at);
            $retrieveTimestampAfterUpdate = Carbon::parse($fileUpdated->retrieved_at);
            $this->assertTrue($retrieveTimestampBeforeUpdate->lt($retrieveTimestampAfterUpdate));
        }
    }

    /**
     * Test index method, check property address and google maps street view visibility
     * Behaviour: success
     */
    public function test_index_method_success_with_property_address()
    {
        // Load test route
        $response = $this->get(route('buyerHome', ['subdomain' => env('GENERIC_SUBDOMAIN')]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee($this->buyerFiles->first()->address);
        $response->assertSee(htmlspecialchars($this->buyerFiles->first()->street_view_url));
    }

    /**
     * Test download wire method
     * Behaviour: success
     *
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function test_download_wire_method_success()
    {
        // Get buyer files before update
        $buyerFiles = $this->buyer->buyerFiles()->where(['wire_doc_id' => $this->wireDoc->id])->get();

        // Delay some time before creating and updating file
        sleep(1);

        // Load test route
        $response = $this->get(route('buyerDownloadWire', [
            'subdomain' => env('GENERIC_SUBDOMAIN'),
            'wireDoc' => $this->wireDoc->id
        ]));

        // Check buyerFile retrieved_at update
        foreach ($buyerFiles as $file) {
            $fileUpdated = $this->buyer->buyerFiles()->find($file->id);
            $retrieveTimestampBeforeUpdate = Carbon::parse($file->retrieved_at);
            $retrieveTimestampAfterUpdate = Carbon::parse($fileUpdated->retrieved_at);
            $this->assertTrue($retrieveTimestampBeforeUpdate->lt($retrieveTimestampAfterUpdate));
        }

        // Get current file mime type
        $mimetype = Storage::disk('s3')->getMimetype($this->wireDoc->path);

        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $this->assertTrue($response->headers->get('content-type') === $mimetype);
        $this->assertTrue($response->headers->get('content-description') === 'File Transfer');
        $this->assertTrue($response->headers->get('content-disposition') === 'attachment; filename="' . $this->wireDoc->name . '"');
    }

    /**
     * Test download wire method
     * Behaviour: fail
     */
    public function test_download_wire_method_fail_check_access()
    {
        // Create new wire doc but it's not belongs for buyer files
        $newWireDoc = factory(WireDoc::class)
            ->create([
                'title_id' => $this->title->id
            ]);

        // Load test route
        $response = $this->get(route('buyerDownloadWire', [
            'subdomain' => env('GENERIC_SUBDOMAIN'),
            'wireDoc' => $newWireDoc->id
        ]));
        $response->assertStatus(302);
        $response->assertSessionMissing('errors');
        $response->assertRedirect(route('home'));
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
        $response = $this->get(route('buyerDownloadDocument', [
            'subdomain' => env('GENERIC_SUBDOMAIN'),
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
        $newBuyer = factory(Buyer::class)->create([
            'title_id' => $this->title->id,
            'email' => $this->faker->unique()->safeEmail,
            'username' => $this->faker->unique()->safeEmail . $this->user->id,
        ]);
        $newFile = factory(BuyerFile::class)->create([
            'buyer_id' => $newBuyer->id,
            'branch_id' => $this->branch->id,
            'wire_doc_id' => $this->wireDoc->id,
            'created_by_user_id' => $this->user->id
        ]);
        $newDocument = factory(BuyerDocument::class)->create([
            'buyer_file_id' => $newFile->id
        ]);

        // Load test route
        $response = $this->get(route('buyerDownloadDocument', [
            'subdomain' => env('GENERIC_SUBDOMAIN'),
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
        $this->account->allows_buyer_documents = true;
        $this->account->saveOrFail();

        // Load test route
        $response = $this->post(route('buyerUploadDocument', [
            'subdomain' => env('GENERIC_SUBDOMAIN'),
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
        $response = $this->post(route('buyerUploadDocument', [
            'subdomain' => env('GENERIC_SUBDOMAIN'),
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
        $response = $this->post(route('buyerUploadDocument', [
            'subdomain' => env('GENERIC_SUBDOMAIN'),
        ]), [
            'file' => $fakeDocumentFile
        ]);
        $response->assertStatus(422);
        $response->assertSee('The file must be a file of type: doc, docx, pdf, png, bmp, jpg, jpeg, zip.');

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
        $response = $this->post(route('buyerUploadDocument', [
            'subdomain' => env('GENERIC_SUBDOMAIN'),
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
    public function test_upload_document_method_fail_allows_buyer_documents()
    {
        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name);

        // Load test route
        $response = $this->post(route('buyerUploadDocument', [
            'subdomain' => env('GENERIC_SUBDOMAIN'),
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

        $this->account->allows_buyer_documents = true;
        $this->account->saveOrFail();

        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name);

        // Load test route
        $response = $this->post(route('buyerAttachDocuments', [
            'subdomain' => env('GENERIC_SUBDOMAIN'),
            'buyerFile' => $this->buyerFiles->first()->id
        ]), [
            'documents' => [
                'file' => $fakeDocumentFile
            ]
        ]);
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('buyerFileID');

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
        $response = $this->post(route('buyerAttachDocuments', [
            'subdomain' => env('GENERIC_SUBDOMAIN'),
            'buyerFile' => $this->buyerFiles->first()->id
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
        $response = $this->post(route('buyerAttachDocuments', [
            'subdomain' => env('GENERIC_SUBDOMAIN'),
            'buyerFile' => $this->buyerFiles->first()->id
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
        $response = $this->post(route('buyerAttachDocuments', [
            'subdomain' => env('GENERIC_SUBDOMAIN'),
            'buyerFile' => $this->buyerFiles->first()->id
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

        // Recreate some data for new file
        $newBuyer = factory(Buyer::class)->create([
            'title_id' => $this->title->id,
            'email' => $this->faker->unique()->safeEmail,
            'username' => $this->faker->unique()->safeEmail . $this->user->id,
        ]);
        $newFile = factory(BuyerFile::class)->create([
            'buyer_id' => $newBuyer->id,
            'branch_id' => $this->branch->id,
            'wire_doc_id' => $this->wireDoc->id,
            'created_by_user_id' => $this->user->id
        ]);

        // Load test route
        $response = $this->post(route('buyerAttachDocuments', [
            'subdomain' => env('GENERIC_SUBDOMAIN'),
            'buyerFile' => $newFile->id
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
        $this->account->allows_buyer_documents = true;
        $this->account->saveOrFail();

        // Create more documents for this buyer file
        factory(BuyerDocument::class, 9)->create([
            'buyer_file_id' => $this->buyerFiles->first()->id,
            'path' => $fakeDocumentName,
            'name' => $fakeDocumentName
        ]);

        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name);

        // Load test route
        $response = $this->post(route('buyerAttachDocuments', [
            'subdomain' => env('GENERIC_SUBDOMAIN'),
            'buyerFile' => $this->buyerFiles->first()->id
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
        $response = $this->delete(route('buyerRemoveDocument', [
            'subdomain' => env('GENERIC_SUBDOMAIN'),
            'document' => $this->documents->first()->id
        ]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('buyerFileID');
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
        $response = $this->delete(route('buyerRemoveDocument', [
            'subdomain' => env('GENERIC_SUBDOMAIN'),
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
        // Recreate some data for new file
        $newBuyer = factory(Buyer::class)->create([
            'title_id' => $this->title->id,
            'email' => $this->faker->unique()->safeEmail,
            'username' => $this->faker->unique()->safeEmail . $this->user->id,
        ]);
        $newFile = factory(BuyerFile::class)->create([
            'buyer_id' => $newBuyer->id,
            'branch_id' => $this->branch->id,
            'wire_doc_id' => $this->wireDoc->id,
            'created_by_user_id' => $this->user->id
        ]);
        $fakeDocumentName = snake_case($this->faker->name) . '.doc';
        $newDocument = factory(BuyerDocument::class)->create([
            'buyer_file_id' => $newFile->id,
            'path' => $fakeDocumentName,
            'name' => $fakeDocumentName
        ]);

        // Load test route
        $response = $this->delete(route('buyerRemoveDocument', [
            'subdomain' => env('GENERIC_SUBDOMAIN'),
            'document' => $newDocument->id
        ]));
        $response->assertStatus(403);
        $response->assertSee('error');
    }
}
