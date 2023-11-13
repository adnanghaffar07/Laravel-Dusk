<?php

namespace Tests\Feature\Controllers;

use App\Notifications\WelcomeClientNotification;
use App\Notifications\WireInstructionsNotification;
use App\Services\ClosingMarketService;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Account;
use App\Title;
use App\Buyer;
use App\BuyerFile;
use App\BuyerDocument;
use App\User;
use App\Branch;
use App\WireDoc;
use Hash;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Storage;

/**
 * Class UserBuyerControllerTest
 * @package Tests\Feature\Controllers
 */
class UserBuyerControllerTest extends TestCase
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
    protected $buyer;

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
    private $cmService;
    private $cmTradingPartners;
    private $cmEnterpriseServiceID;

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

        // Create fake buyer
        $email = $this->faker->email;
        $this->buyer = factory(Buyer::class)->create([
            'title_id' => $this->title->id,
            'email' => $email,
            'phone' => '+15005550006',
            'passcode' => $this->faker->numerify('1########'),
            'username' => $email . $this->title->id,
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

        // Create fake files and documents
        $fakeDocumentName = snake_case($this->faker->name) . '.doc';
        $this->documents = collect([]);
        $this->buyerFiles = factory(BuyerFile::class, 3)
            ->create([
                'buyer_id' => $this->buyer->id,
                'branch_id' => $this->branch->id,
                'wire_doc_id' => $this->wireDoc->id,
                'created_by_user_id' => $this->user->id,
                'ref_number' => 'refnum',
            ])->each(function ($file) use ($fakeDocumentName) {
                $this->documents->push(factory(BuyerDocument::class)->create([
                    'buyer_file_id' => $file->id,
                    'path' => $fakeDocumentName,
                    'name' => $fakeDocumentName
                ]));
            });

        // Associate fake user with branch
        $this->branch->users()->save($this->user);

        // Acting as logged user
        $this->be($this->user);

        if (!(env('CM_CONFIG_LOGIN') && env('CM_CONFIG_PASSWORD') && env('CM_CONFIG_DOMAIN'))) {
            throw new \Exception('No ClosingMarket credentials provided. Check .env.testing');
        }

        $this->cmService = new ClosingMarketService();
        $this->cmTradingPartners = $this->cmService->GetTradingPartnerList();
        $this->cmEnterpriseServiceID = ($this->cmTradingPartners->first())['EnterpriseServiceID'] ?? 0;
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
     * @return Collection
     */
    private function makeBuyerFileWithRelations(int $numDocuments = 1)
    {
        // Create fake buyer
        $email = $this->faker->unique()->email;
        $buyer = factory(Buyer::class)->create([
            'title_id' => $this->title->id,
            'email' => $email,
            'phone' => $this->faker->unique()->numerify('+1500555####'),
            'passcode' => $this->faker->numerify('1########'),
            'username' => $email . $this->title->id,
        ]);


        // Create fake wire doc
        $fakeWireDocName = snake_case($this->faker->unique()->name) . '.doc';
        $wireDoc = factory(WireDoc::class)
            ->create([
                'title_id' => $this->title->id,
                'path' => $fakeWireDocName,
                'name' => $fakeWireDocName,
                'branch_id' => $this->branch->id
            ]);

        // Create fake files and documents
        $fakeDocumentName = snake_case($this->faker->unique()->name) . '.doc';
        $buyerFiles = factory(BuyerFile::class, $numDocuments)
            ->create([
                'buyer_id' => $buyer->id,
                'branch_id' => $this->branch->id,
                'wire_doc_id' => $wireDoc->id,
                'created_by_user_id' => $this->user->id,
                'ref_number' => $this->faker->unique()->lexify('?????????????'),
                'address' => $this->faker->unique()->streetAddress,
            ])->each(function ($file) use ($fakeDocumentName) {
                factory(BuyerDocument::class)->create([
                    'buyer_file_id' => $file->id,
                    'path' => $fakeDocumentName,
                    'name' => $fakeDocumentName
                ]);
            });

        return $buyerFiles->fresh(['buyer', 'documents', 'wireDoc', 'branch', 'branch.title']);
    }

    /**
     * @param string $phrase
     * @param int $iterator
     * @param int $recordsFiltered
     * @param int $recordsTotal
     */
    private function requestSearchBuyersTable(string $phrase, int $iterator, int $recordsFiltered, int $recordsTotal)
    {
        $response = $this->post(route('buyersTable'), [
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
        $response = $this->get(route('userHome'));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('Add Clients');
    }

    /**
     * Test storeBuyerFile method
     * Behaviour: success
     */
    public function test_store_buyer_file_method_success()
    {
        // Load test route
        $response = $this->post(route('addBuyerFile'), [
            'email' => [$this->faker->email],
            'phone' => [null],
            'wireDoc' => $this->wireDoc->id,
            'notes' => 'notes',
            'refnum' => 'refnum',
            'source' => BuyerFile::SOURCE_MANUAL,
        ]);

        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('added buyer file');
    }

    /**
     * Test storeBuyerFile method, with multiple emails and welcome notifications
     * Behaviour: success
     */
    public function test_store_buyer_file_method_success_multiple_emails_with_welcome_notification()
    {

       $this->account->allows_welcome_email = 1;
       $this->account->saveOrFail();

        // make many buyer emails
        $emails = [];
        $phones = [];
        for ($i = 0; $i < 10; $i++) {
            $emails[] = $this->faker->email;
            $phones[] = null;
        }

        Notification::fake();

        // Load test route
        $response = $this->post(route('addBuyerFile'), [
            'email' => $emails,
            'phone' => $phones,
            'wireDoc' => $this->wireDoc->id,
            'notes' => 'notes',
            'refnum' => 'refnum',
            'notify' => 'notify',
            'source' => BuyerFile::SOURCE_MANUAL,
        ]);
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('added buyer file');

        foreach ($emails as $email) {
            $buyer = Buyer::where('email', $email)->firstOrFail();
            Notification::assertSentTo([$buyer], WelcomeClientNotification::class);
            Notification::assertSentTo([$buyer], WireInstructionsNotification::class);
        }
    }

    /**
     * Test storeBuyerFile method, with multiple emails and wire notifications, but not welcome notifications
     * Behaviour: success
     */
    public function test_store_buyer_file_method_success_multiple_emails_without_welcome_notification()
    {
        $this->account->allows_welcome_email = 0;
        $this->account->saveOrFail();

        // make many buyer emails
        $emails = [];
        $phones = [];
        for ($i = 0; $i < 10; $i++) {
            $emails[] = $this->faker->email;
            $phones[] = null;
        }

        Notification::fake();

        // Load test route
        $response = $this->post(route('addBuyerFile'), [
            'email' => $emails,
            'phone' => $phones,
            'wireDoc' => $this->wireDoc->id,
            'notes' => 'notes',
            'refnum' => 'refnum',
            'notify' => 'notify',
            'source' => BuyerFile::SOURCE_MANUAL,
        ]);
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('added buyer file');

        foreach ($emails as $email) {
            $buyer = Buyer::where('email', $email)->firstOrFail();
            Notification::assertNotSentTo([$buyer], WelcomeClientNotification::class);
            Notification::assertSentTo([$buyer], WireInstructionsNotification::class);
        }
    }

    /**
     * Test storeBuyerFile method
     * Behaviour: fail
     */
    public function test_store_buyer_file_method_fail_demo_account()
    {
        // Update account name
        $this->account->name = 'Demo Account';
        $this->account->saveOrFail();

        // Load test route
        $response = $this->post(route('addBuyerFile'), [
            'email' => [$this->faker->email],
            'phone' => [null],
            'wireDoc' => $this->wireDoc->id,
            'notes' => 'notes',
            'refnum' => 'refnum',
            'source' => BuyerFile::SOURCE_MANUAL,
        ]);
        $response->assertStatus(200);
        $response->assertSee('added demo client');
    }

    /**
     * Test storeBuyerFile method, fail email verification by kickbox.io service
     * Docs: https://docs.kickbox.com/docs/sandbox-api
     * Behaviour: fail
     */
    public function test_store_buyer_file_method_fail_email_verify_by_kickbox_io()
    {
        // Load test route
        $response = $this->post(route('addBuyerFile'), [
            'email' => ['good@test.com', 'rejected-email@example.com'],
            'phone' => [null, null],
            'wireDoc' => $this->wireDoc->id,
            'notes' => 'notes',
            'refnum' => 'refnum',
            'source' => BuyerFile::SOURCE_MANUAL,
        ]);
        $response->assertStatus(422);
        $response->assertJsonFragment(
            [
                'errors' => [
                    'email.1' => ['This email address is undeliverable. Please verify the address.']
            ]
        ]);
    }

    /**
     * Test storeBuyerFile method
     * Behaviour: fail
     */
    public function test_store_buyer_file_method_fail_email_phone_array_diffferent_size()
    {
        // Load test route
        $response = $this->post(route('addBuyerFile'), [
            'email' => [$this->faker->email, $this->faker->email],
            'phone' => [null],
            'wireDoc' => $this->wireDoc->id,
            'notes' => 'notes',
            'refnum' => 'refnum',
            'source' => BuyerFile::SOURCE_MANUAL,
        ]);
        $response->assertStatus(422);
        $response->assertJsonFragment(['length' => 'Unknown error occurred. Please try again.']);
    }

    /**
     * Test storeBuyerFile method, without required fields
     * Behaviour: fail
     */
    public function test_store_buyer_file_method_fail_validation_fields_are_required()
    {
        // Load test route
        $response = $this->post(route('addBuyerFile'), []);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'email' => 'The email address must be in a valid format.',
            'phone' => 'The phone number must be in a valid format.',
            'wireDoc' => 'Please select wire instructions.',
            'refnum' => 'You must enter a reference number.',
        ]);
    }

    /**
     * Test storeBuyerFile method, without costs field
     * Behaviour: fail
     */
    // uncomment if balance_due is needed to be required
//    public function test_store_buyer_file_method_fail_validation_costs_is_required()
//    {
//        // Update account
//        $this->account->allows_balance_due = 1;
//        $this->account->saveOrFail();
//
//        // Load test route
//        $response = $this->post(route('addBuyerFile'), [
//            'email' => [$this->faker->email],
//            'phone' => [null],
//            'wireDoc' => $this->wireDoc->id,
//            'notes' => 'notes',
//            'refnum' => 'refnum'
//        ]);
//        $response->assertStatus(302);
//        $response->assertSessionHasErrors([
//            'costs' => 'The costs field is required.'
//        ]);
//    }

    /**
     * Test storeBuyerFile method, with invalid phone number
     * Behaviour: fail
     */
    public function test_store_buyer_file_method_fail_validation_phone_invalid_number()
    {
        // Load test route
        $response = $this->post(route('addBuyerFile'), [
            'email' => [$this->faker->email],
            'phone' => ['+78123321123321'],
            'wireDoc' => $this->wireDoc->id,
            'notes' => 'notes',
            'refnum' => 'refnum',
            'source' => BuyerFile::SOURCE_MANUAL,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'phone.0' => 'The phone number must be in a valid format.'
        ]);
    }

    /**
     * Test storeBuyerFile method, with wiredoc which does't belongs to account
     * Behaviour: fail
     */
    public function test_store_buyer_file_method_fail_wireDoc_id_doesnt_belong_to_this_account()
    {
        // Create new fake account and update user
        $newAccount = new Account();
        $newAccount->name = $this->faker->name;
        $newAccount->saveOrFail();

        $this->user->account_id = $newAccount->id;
        $this->user->saveOrFail();

        // Load test route
        $response = $this->post(route('addBuyerFile'), [
            'email' => [$this->faker->email],
            'phone' => ['+15005550006'],
            'wireDoc' => $this->wireDoc->id,
            'notes' => 'notes',
            'refnum' => 'refnum',
            'source' => BuyerFile::SOURCE_MANUAL,
        ]);
        $response->assertStatus(422);
        $response->assertJsonFragment([
            'wireDoc' => 'Please enter correct wire instructions'
        ]);
    }

    /**
     * Test storeBuyerFile method, with wiredoc which does't belongs to user
     * Behaviour: fail
     */
    public function test_store_buyer_file_method_fail_wireDoc_id_doesnt_belong_to_this_user()
    {
        // Create fake branch
        $newBranch = factory(Branch::class)
            ->create([
                'title_id' => $this->title->id
            ]);

        // Create fake wire doc
        $fakeWireDocName = snake_case($this->faker->name) . '.doc';
        $newWireDoc = factory(WireDoc::class)
            ->create([
                'title_id' => $this->title->id,
                'path' => $fakeWireDocName,
                'name' => $fakeWireDocName,
                'branch_id' => $newBranch->id
            ]);

        // Load test route
        $response = $this->post(route('addBuyerFile'), [
            'email' => [$this->faker->email],
            'phone' => ['+15005550006'],
            'wireDoc' => $newWireDoc->id,
            'notes' => 'notes',
            'refnum' => 'refnum',
            'source' => BuyerFile::SOURCE_MANUAL,
        ]);
        $response->assertStatus(422);
        $response->assertJsonFragment([
            'wireDoc' => 'Please enter correct wire instructions'
        ]);
    }

    /**
     * Test storeBuyerFile method, validate maximum documents
     * Behaviour: fail
     */
    public function test_store_buyer_file_method_fail_maximum_documents()
    {
        // Update current account
        $this->account->allows_buyer_documents = true;
        $this->account->saveOrFail();

        $fakeDocumentName = snake_case($this->faker->name) . '.doc';

        // Create more documents for this buyer file
        factory(BuyerDocument::class, 11)->create([
            'buyer_file_id' => $this->buyerFiles->first()->id,
            'path' => $fakeDocumentName,
            'name' => $fakeDocumentName,
            'uploader_role' => 'user'
        ]);

        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name);

        // Load test route
        $response = $this->post(route('addBuyerFile'), [
            'email' => [$this->buyer->email],
            'phone' => ['+15005550006'],
            'wireDoc' => $this->wireDoc->id,
            'notes' => 'notes',
            'refnum' => 'refnum',
            'documents' => [
                'file' => $fakeDocumentFile
            ],
            'source' => BuyerFile::SOURCE_MANUAL,
        ]);
        $response->assertStatus(422);
        $response->assertSessionMissing('errors');
        $response->assertJsonFragment([
            'errors' => [
                'documents' => 'There is a maximum of 10 documents per client.'
            ]
        ]);
    }

    /**
     * Test downloadWire method
     * Behaviour: success
     */
    public function test_download_wire_method_success()
    {
        // Create and upload fake wire doc file
        $fakeWireDocFile = UploadedFile::fake()->create($this->wireDoc->name);
        Storage::disk('s3')->put($this->wireDoc->name, file_get_contents($fakeWireDocFile));

        // Load test route
        $response = $this->get(route('userDownloadWire', [
            'id' => $this->wireDoc->id,
        ]));

        // Get current file mime type
        $mimetype = Storage::disk('s3')->getMimetype($this->wireDoc->path);

        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $this->assertTrue($response->headers->get('content-type') === $mimetype);
        $this->assertTrue($response->headers->get('content-description') === 'File Transfer');
        $this->assertTrue($response->headers->get('content-disposition') === 'attachment; filename="' . $this->wireDoc->name . '"');

        // Clean up after test
        Storage::disk('s3')->delete($fakeWireDocFile);
    }

    /**
     * Test downloadWire method, check user access
     * Behaviour: fail
     */
    public function test_download_wire_method_fail_user_access()
    {
        // Create new wire doc but it's not belongs for buyer files
        $newWireDoc = factory(WireDoc::class)
            ->create([
                'title_id' => $this->title->id
            ]);

        // Load test route
        $response = $this->get(route('userDownloadWire', [
            'id' => $newWireDoc->id,
        ]));
        $response->assertStatus(404);
        $response->assertSessionMissing('errors');
    }

    /**
     * Test downloadWire method, with nonexist wire doc
     * Behaviour: fail
     */
    public function test_download_wire_method_fail_wire_doc_id_not_found()
    {
        // Load test route
        $response = $this->get(route('userDownloadWire', [
            'id' => 0,
        ]));
        $response->assertStatus(404);
        $response->assertSessionMissing('errors');
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
        $response = $this->get(route('userDownloadDocument', [
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
        $newBuyer = factory(Buyer::class)->create([
            'title_id' => $this->title->id,
            'email' => $this->faker->unique()->safeEmail,
            'username' => $this->faker->unique()->safeEmail . $this->user->id,
        ]);
        $newBranch = factory(Branch::class)
            ->create([
                'title_id' => $this->title->id
            ]);
        $fakeWireDocName = snake_case($this->faker->name) . '.doc';
        $newWireDoc = factory(WireDoc::class)
            ->create([
                'title_id' => $this->title->id,
                'path' => $fakeWireDocName,
                'name' => $fakeWireDocName,
                'branch_id' => $newBranch->id
            ]);
        $newFile = factory(BuyerFile::class)->create([
            'buyer_id' => $newBuyer->id,
            'branch_id' => $newBranch->id,
            'wire_doc_id' => $newWireDoc->id,
            'created_by_user_id' => $this->user->id
        ]);
        $newDocument = factory(BuyerDocument::class)->create([
            'buyer_file_id' => $newFile->id
        ]);

        // Load test route
        $response = $this->get(route('userDownloadDocument', [
            'document' => $newDocument->id
        ]));
        $response->assertStatus(404);
        $response->assertSessionMissing('errors');
    }

    /**
     * Test downloadDocument method, with nonexist document
     * Behaviour: fail
     */
    public function test_download_document_method_fail_wire_doc_id_not_found()
    {
        // Load test route
        $response = $this->get(route('userDownloadDocument', [
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
        $response = $this->post(route('userAttachDocuments', [
            'buyerFile' => $this->buyerFiles->first()->id
        ]), [
            'notify' => 'notify',
            'documents' => [
                'file' => $fakeDocumentFile
            ]
        ]);
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('Documents attached with notification');

        Notification::assertSentTo([$this->buyer], \App\Notifications\BuyerDocumentNotification::class);

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
        $response = $this->post(route('userAttachDocuments', [
            'buyerFile' => $this->buyerFiles->first()->id
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
        $response = $this->post(route('userAttachDocuments', [
            'buyerFile' => $this->buyerFiles->first()->id
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
        $response = $this->post(route('userAttachDocuments', [
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
     * Test attach documents, with not valid file size
     * Behaviour: fail
     */
    public function test_attach_documents_method_fail_validation_file_max_size()
    {
        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name, 5121); // add 1 extra kb

        // Load test route
        $response = $this->post(route('userAttachDocuments', [
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
     * Test attach documents, check access
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
        $newBranch = factory(Branch::class)
            ->create([
                'title_id' => $this->title->id
            ]);
        $fakeWireDocName = snake_case($this->faker->name) . '.doc';
        $newWireDoc = factory(WireDoc::class)
            ->create([
                'title_id' => $this->title->id,
                'path' => $fakeWireDocName,
                'name' => $fakeWireDocName,
                'branch_id' => $newBranch->id
            ]);
        $newFile = factory(BuyerFile::class)->create([
            'buyer_id' => $newBuyer->id,
            'branch_id' => $newBranch->id,
            'wire_doc_id' => $newWireDoc->id,
            'created_by_user_id' => $this->user->id
        ]);

        // Load test route
        $response = $this->post(route('userAttachDocuments', [
            'buyerFile' => $newFile->id
        ]), [
            'documents' => [
                'file' => $fakeDocumentFile
            ]
        ]);
        $response->assertStatus(404);

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
        $this->account->allows_buyer_documents = true;
        $this->account->saveOrFail();

        // Create more documents for this buyer file
        factory(BuyerDocument::class, 10)->create([
            'buyer_file_id' => $this->buyerFiles->first()->id,
            'path' => $fakeDocumentName,
            'name' => $fakeDocumentName,
            'uploader_role' => 'user'
        ]);

        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name);

        // Load test route
        $response = $this->post(route('userAttachDocuments', [
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
     * Test upload document method
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
        $response = $this->post(route('userUploadDocument'), [
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
        $response = $this->post(route('userUploadDocument'));
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
        $response = $this->post(route('userUploadDocument'), [
            'file' => $fakeDocumentFile
        ]);
        $response->assertStatus(422);
        $response->assertSee('The file must be a file of type: doc, docx, pdf, png, bmp, jpg, jpeg, zip.');

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
        $response = $this->post(route('userUploadDocument'), [
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
    public function test_upload_document_method_fail_allows_buyer_documents()
    {
        // Create and upload document file
        $fakeDocumentFile = UploadedFile::fake()->create($this->documents->first()->name);

        // Load test route
        $response = $this->post(route('userUploadDocument'), [
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
        $response = $this->delete(route('userRemoveDocument', [
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
        $response = $this->delete(route('userRemoveDocument', [
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
        // Recreate some data for new file
        $newBuyer = factory(Buyer::class)->create([
            'title_id' => $this->title->id,
            'email' => $this->faker->unique()->safeEmail,
            'username' => $this->faker->unique()->safeEmail . $this->user->id,
        ]);
        $newBranch = factory(Branch::class)
            ->create([
                'title_id' => $this->title->id
            ]);
        $fakeWireDocName = snake_case($this->faker->name) . '.doc';
        $newWireDoc = factory(WireDoc::class)
            ->create([
                'title_id' => $this->title->id,
                'path' => $fakeWireDocName,
                'name' => $fakeWireDocName,
                'branch_id' => $newBranch->id
            ]);
        $newFile = factory(BuyerFile::class)->create([
            'buyer_id' => $newBuyer->id,
            'branch_id' => $newBranch->id,
            'wire_doc_id' => $newWireDoc->id,
            'created_by_user_id' => $this->user->id
        ]);
        $fakeDocumentName = snake_case($this->faker->name) . '.doc';
        $newDocument = factory(BuyerDocument::class)->create([
            'buyer_file_id' => $newFile->id,
            'path' => $fakeDocumentName,
            'name' => $fakeDocumentName
        ]);

        // Load test route
        $response = $this->delete(route('userRemoveDocument', [
            'document' => $newDocument->id
        ]));
        $response->assertStatus(404);
    }

    /**
     * Test notify method
     * Behaviour: success
     */
    public function test_notify_method_success()
    {
        Notification::fake();

        // Load test route
        $response = $this->post(route('notifyBuyer', [
            'buyerFile' => $this->buyerFiles->first()->id
        ]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('notified buyer');

        Notification::assertSentTo([$this->buyer], WireInstructionsNotification::class);
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
        $response = $this->post(route('notifyBuyer', [
            'buyerFile' => $this->buyerFiles->first()->id
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

        // Recreate some data
        $newBuyer = factory(Buyer::class)->create([
            'title_id' => $newTitle->id,
            'email' => $this->faker->unique()->safeEmail,
            'username' => $this->faker->unique()->safeEmail . $this->user->id,
        ]);
        $newBranch = factory(Branch::class)
            ->create([
                'title_id' => $this->title->id
            ]);
        $fakeWireDocName = snake_case($this->faker->name) . '.doc';
        $newWireDoc = factory(WireDoc::class)
            ->create([
                'title_id' => $this->title->id,
                'path' => $fakeWireDocName,
                'name' => $fakeWireDocName,
                'branch_id' => $newBranch->id
            ]);
        $newFile = factory(BuyerFile::class)->create([
            'buyer_id' => $newBuyer->id,
            'branch_id' => $newBranch->id,
            'wire_doc_id' => $newWireDoc->id,
            'created_by_user_id' => $this->user->id
        ]);

        // Load test route
        $response = $this->post(route('notifyBuyer', [
            'buyerFile' => $newFile->id
        ]));
        $response->assertStatus(404);
    }

    /**
     * Test destroy method
     * Behaviour: success
     */
    public function test_destroy_method_success()
    {
        // Re-create some fake data
        $newFile = factory(BuyerFile::class)->create([
            'buyer_id' => $this->buyer->id,
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

        // Create and upload fake wire doc file
        $fakeDocumentFile = UploadedFile::fake()->create($newDocument->name);
        Storage::disk('s3')->put($newDocument->name, file_get_contents($fakeDocumentFile));

        // Load test route
        $response = $this->delete(route('deleteBuyerFile', [
            'buyerFile' => $newFile->id
        ]));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('deleted buyer');

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
        $response = $this->delete(route('deleteBuyerFile', [
            'buyerFile' => $this->buyerFiles->first()->id
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
        $fakeWireDocName = snake_case($this->faker->name) . '.doc';
        $newWireDoc = factory(WireDoc::class)
            ->create([
                'title_id' => $this->title->id,
                'path' => $fakeWireDocName,
                'name' => $fakeWireDocName,
                'branch_id' => $newBranch->id
            ]);
        $newFile = factory(BuyerFile::class)->create([
            'buyer_id' => $this->buyer->id,
            'branch_id' => $newBranch->id,
            'wire_doc_id' => $newWireDoc->id,
            'created_by_user_id' => $this->user->id
        ]);
        $fakeDocumentName = snake_case($this->faker->name) . '.doc';
        $newDocument = factory(BuyerDocument::class)->create([
            'buyer_file_id' => $newFile->id,
            'path' => $fakeDocumentName,
            'name' => $fakeDocumentName
        ]);

        // Create and upload fake wire doc file
        $fakeDocumentFile = UploadedFile::fake()->create($newDocument->name);
        Storage::disk('s3')->put($newDocument->name, file_get_contents($fakeDocumentFile));

        // Load test route
        $response = $this->delete(route('deleteBuyerFile', [
            'buyerFile' => $newFile->id
        ]));
        $response->assertStatus(404);
        $response->assertSessionMissing('errors');

        // Clean up after test
        Storage::disk('s3')->delete($fakeDocumentFile);
    }

    /**
     * Test buyersTable method
     * Behaviour: success
     */
    public function test_buyers_table_method_success()
    {
        // Load test route
        $response = $this->post(route('buyersTable'), [
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
                    'email',
                    'phone',
                    'phone_tooltip',
                    'passcode',
                    'ref_number',
                    'wire_doc',
                    'company',
                    'branch',
                    'created_at',
                    'notified_at',
                    'retrieved_at',
                    'documents_sent',
                    'documents_received',
                    'notes',
                    'options',
                ]
            ]
        ]);
    }

    /**
     * Test buyersTable method with search filter
     * Behaviour: success
     */
    public function test_buyers_table_method_success_with_search()
    {
        factory(BuyerFile::class)
            ->create([
                'buyer_id' => $this->buyer->id,
                'branch_id' => $this->branch->id,
                'wire_doc_id' => $this->wireDoc->id,
                'created_by_user_id' => $this->user->id,
                'ref_number' => 'unique word',
            ]);

        // Load test route
        $response = $this->post(route('buyersTable'), [
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
     * Test buyersTable method with search filter by each of column values
     * Behaviour: success
     */
    public function test_buyers_table_method_success_with_search_by_columns()
    {
        $numTestBuyerFiles = 20;
        $numTestDocumentsPerEach = 3;

        $buyerFiles = collect();
        for ($i = 0; $i < $numTestBuyerFiles; $i++) {
            $buyerFiles->push($this->makeBuyerFileWithRelations($numTestDocumentsPerEach));
        }

        foreach ($buyerFiles as $key => $files) {
            // search by email
            $this->requestSearchBuyersTable(
                $files->first()->buyer->email,
                ($key + 1),
                $numTestDocumentsPerEach,
                (($numTestBuyerFiles * $numTestDocumentsPerEach) + 3) // 3 is for three docs, created by setUp method
            );

            // search by phone
            $this->requestSearchBuyersTable(
                $files->first()->buyer->phone,
                ($key + 1),
                $numTestDocumentsPerEach,
                (($numTestBuyerFiles * $numTestDocumentsPerEach) + 3) // 3 is for three docs, created by setUp method
            );

            // search by address
            $this->requestSearchBuyersTable(
                $files->first()->address,
                ($key + 1),
                $numTestDocumentsPerEach,
                (($numTestBuyerFiles * $numTestDocumentsPerEach) + 3) // 3 is for three docs, created by setUp method
            );

            // search by ref_number
            $this->requestSearchBuyersTable(
                $files->first()->ref_number,
                ($key + 1),
                $numTestDocumentsPerEach,
                (($numTestBuyerFiles * $numTestDocumentsPerEach) + 3) // 3 is for three docs, created by setUp method
            );

            // search by branch name
            $this->requestSearchBuyersTable(
                $files->first()->branch->title->name,
                ($key + 1),
                (($numTestBuyerFiles * $numTestDocumentsPerEach) + 3), // branch is the same for all buyer files, so results must be same as total
                (($numTestBuyerFiles * $numTestDocumentsPerEach) + 3) // 3 is for three docs, created by setUp method
            );

            // search by wiredoc name
            $this->requestSearchBuyersTable(
                $files->first()->wireDoc->name,
                ($key + 1),
                $numTestDocumentsPerEach,
                (($numTestBuyerFiles * $numTestDocumentsPerEach) + 3) // 3 is for three docs, created by setUp method
            );
        }
    }

    /**
     * Test buyersTable method with allowed documents
     * Behaviour: success
     */
    public function test_buyers_table_method_success_with_allowed_documents()
    {
        // Update current account
        $this->account->allows_buyer_documents = true;
        $this->account->saveOrFail();

        // Load test route
        $response = $this->post(route('buyersTable'), [
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
     * Test buyersTable method with allowed notes
     * Behaviour: success
     */
    public function test_buyers_table_method_success_with_allowed_notes()
    {
        // Update current account
        $this->account->allows_buyer_notes = true;
        $this->account->saveOrFail();

        // Load test route
        $response = $this->post(route('buyersTable'), [
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
     * Test buyersTable method, without required fields
     * Behaviour: fail
     */
    public function test_buyers_table_method_fail_validation_fields_are_required()
    {
        // Load test route
        $response = $this->post(route('buyersTable'));
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'draw' => 'The draw field is required.',
            'start' => 'The start field is required.',
            'length' => 'The length field is required.',
        ]);
    }

    /**
     * Test orderIntegrationList route
     * Behaviour: success
     */
    public function test_order_integration_list_route_success()
    {
        $this->branch->allows_closingmarket = true;
        $this->branch->enterprise_service_id = $this->cmEnterpriseServiceID;
        $this->branch->save();

        // Load test route
        $response = $this->get(route('orderIntegrationList', ['branch' => $this->branch->id]));
        $response->assertStatus(200);
        static::assertGreaterThan(0, count(json_decode($response->getContent())));
    }

    /**
     * Test orderIntegrationList route, fail with "no access" error
     * Behaviour: fail
     */
    public function test_order_integration_list_route_fail_no_access()
    {
        $this->branch->allows_closingmarket = false;
        $this->branch->enterprise_service_id = $this->cmEnterpriseServiceID;
        $this->branch->save();
        $response = $this->get(route('orderIntegrationList', ['branch' => $this->branch->id]));
        $response->assertStatus(403);

        $this->branch->allows_closingmarket = true;
        $this->branch->enterprise_service_id = null;
        $this->branch->save();
        $response = $this->get(route('orderIntegrationList', ['branch' => $this->branch->id]));
        $response->assertStatus(403);

        $this->branch->allows_closingmarket = false;
        $this->branch->enterprise_service_id = null;
        $this->branch->save();
        $response = $this->get(route('orderIntegrationList', ['branch' => $this->branch->id]));
        $response->assertStatus(403);
    }

    /**
     * Test orderIntegrationItem route
     * Behaviour: success
     */
    public function test_order_integration_item_route_success()
    {
        $this->branch->allows_closingmarket = true;
        $this->branch->enterprise_service_id = $this->cmEnterpriseServiceID;
        $this->branch->save();

        $orders = $this->cmService->GetOrderList($this->branch);

        // Load test route
        $response = $this->get(route('orderIntegrationItem', [
            'branch' => $this->branch->id,
            'identifier' => $orders->keys()->last()
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'emails' => [],
            'phone' => [],
            'addresses' => [],
            'close_date' => [],
            'amount_down' => [],
            'earnest_money' => [],
            'sales_price' => [],
        ]);
    }

    /**
     * Test orderIntegrationItem route
     * Behaviour: fail
     */
    public function test_order_integration_item_route_fail_no_access()
    {
        $this->branch->allows_closingmarket = true;
        $this->branch->enterprise_service_id = $this->cmEnterpriseServiceID;
        $this->branch->save();
        $orders = $this->cmService->GetOrderList($this->branch);
        $firstOrderID = null;
        foreach ($orders as $id => $refNumber) {
            $firstOrderID = $id;
            break;
        }

        $this->branch->allows_closingmarket = false;
        $this->branch->enterprise_service_id = $this->cmEnterpriseServiceID;
        $this->branch->save();
        $response = $this->get(route('orderIntegrationItem', [
            'branch' => $this->branch->id,
            'identifier' => $firstOrderID
        ]));
        $response->assertStatus(403);

        $this->branch->allows_closingmarket = true;
        $this->branch->enterprise_service_id = null;
        $this->branch->save();
        $response = $this->get(route('orderIntegrationItem', [
            'branch' => $this->branch->id,
            'identifier' => $firstOrderID
        ]));
        $response->assertStatus(403);

        $this->branch->allows_closingmarket = false;
        $this->branch->enterprise_service_id = null;
        $this->branch->save();
        $response = $this->get(route('orderIntegrationItem', [
            'branch' => $this->branch->id,
            'identifier' => $firstOrderID
        ]));
        $response->assertStatus(403);
    }
}
