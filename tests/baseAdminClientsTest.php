<?php
/**
 * Copyright (c) 2018, BuyerDocs, LLC.
 * All rights reserved.
 */

namespace Tests;

use Laravel\Dusk\Browser;

use App\Buyer;
use App\WireDoc;

trait baseAdminClientsTest
{

    protected $user;
    protected $default_company_id;
    protected $default_branch_id;
    protected $selected_company_id;
    protected $selected_branch_id;
    protected $selected_wireDoc;

    protected $notificationMessage;

    /**
     * @group adminClients
     * @throws
     * @return void
     */
    public function setUp()
    {
        parent::setup();

        $this->additionalSetUp();

        // Set the default company/branch if single company/branch for User
        $titles = $this->user->titles();
        $this->default_company_id = ($titles->count() == 1) ? $titles->firstOrFail()->id : '';
        $branches = $this->user->branches();
        $this->default_branch_id = ($branches->count() == 1) ? $branches->firstOrFail()->id : '';

        $this->loginAndVisitMainPage($this->user);
    }

    abstract function additionalSetUp();

    private function loginAndVisitMainPage($user)
    {
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                // ->maximize()
                ->visit(route('userHome'))
                ->waitForText('ADD CLIENTS')
                ->assertSee('ADD CLIENTS');
        });
    }

    /**
     * Function to return the Add Client form input values for every input
     * @param Browser $browser
     * @return \Illuminate\Support\Collection
     */
    private function getAddClientInputValues(Browser $browser)
    {
        $inputValues = collect([
            'company' => $browser->value('#select-company'),
            'branch' => $browser->value('#select-branch'),
            'amount_down' => $browser->value('#amount_down'),
            'earnest_money' => $browser->value('#earnest_money'),
            'sales_price' => $browser->value('#sales_price'),
            'refnum' => $browser->value('#refnum'),
            'email0' => $browser->value('#email0'),
            'phone0' => $browser->value('#phone0'),
            'address' => $browser->value('#address'),
            'notify' => $browser->value('#notify'),
            'cm_order_id' => $browser->value('#cm_order_id'),
        ]);

        if ($this->user->account->allows_balance_due) {
            $inputValues->put('balance_due', $browser->value('#balance_due'));
        }
        if ($this->user->account->allows_close_date) {
            $inputValues->put('close_date', $browser->value('#close_date'));
        }
        if ($this->user->account->allows_buyer_notes) {
            $inputValues->put('notes', $browser->value('#notes'));
        }

        return $inputValues;
    }

    /**
     * Function to verify the Add Client form input values.
     * @param Browser $browser
     * @param string $company_id
     * @param string $branch_id
     */
    private function verifyAddClientInputValues(Browser $browser, string $company_id, string $branch_id)
    {
        $inputValues = $this->getAddClientInputValues($browser);
        foreach ($inputValues as $key => $value) {
            switch ($key) {
                case 'company':
                    $this->assertTrue($value == $company_id);
                    break;
                case 'branch':
                    $this->assertTrue($value == $branch_id);
                    break;
                case 'notify':
                    $this->assertTrue($value == "notify");
                    break;
                default:
                    $this->assertTrue($value == "");
                    break;
            }
        }
        $browser->assertInputValue('notify', 'notify');
        $browser->assertChecked('notify');
    }

    /**
     * Function to select company, branch, wireDoc in 'Add Clients' form.
     * Also verifies default state of all input fields for form.
     * @param Browser $browser
     */
    private function initAndSelectAddClientForm(Browser $browser) {
        $this->verifyAddClientInputValues($browser, $this->default_company_id, $this->default_branch_id);

        $this->selected_company_id = $this->default_company_id ? : '2';
        $this->selected_branch_id = $this->default_branch_id ? : '3';

        // Select Title/Branch if they are not default selected
        if ( !$this->default_company_id) {
            $browser->assertPresent('#select-company');
            $browser->select('company', $this->selected_company_id);
        } else {
            $browser->assertMissing('#select-company');
        }
        if ( !$this->default_branch_id) {
            $browser->assertPresent('#select-company');
            $browser->select('branch', $this->selected_branch_id);
        } else {
            $browser->assertMissing('#select-branch');
        }

        // Use last WireDoc
        $this->selected_wireDoc = WireDoc::where('branch_id', $this->selected_branch_id)->orderBy('created_at', 'desc')->firstOrFail();
        $browser->assertPresent('#select-wireDoc');
        $browser->select('wireDoc', $this->selected_wireDoc->id);
    }

    /**
     * @throws
     * @return void
     */
    public function addError()
    {
        $this->browse(function (Browser $browser) {

            $this->initAndSelectAddClientForm($browser);

            // Add client
            $browser->waitFor('#email0')
                    ->type('email[0]', 'dusk@autotest');
            $browser->element('#openAddModal')->getLocationOnScreenOnceScrolledIntoView();
            $browser->waitFor('#openAddModal');
            $browser->click('#openAddModal');
            $browser->waitForText('Please specify a reference file number.');
            $browser->assertSee('Please specify a reference file number.');
            $browser->waitFor('#refnum');
            $browser->element('#refnum')->getLocationOnScreenOnceScrolledIntoView();
            $browser->pause(500);
            // The pause is hacky, but fixes the "Element is not currently interactable and may not be manipulated" error
            // Issue is related to Chrome and chromedriver version but there is no legitimate way to rollback Chrome version:
            // (Session info: headless chrome=70.0.3538.110)
            // (Driver info: chromedriver=2.44.609538,platform=Windows NT 10.0.17134 x86_64)
            $browser->type('refnum', 'File 123456 from dusk');
            $browser->element('#openAddModal')->getLocationOnScreenOnceScrolledIntoView();
            $browser->click('#openAddModal');
            $browser->waitForText('Confirm');  // check modal
            $browser->assertSee($this->selected_wireDoc->name);
            $browser->assertSee($this->notificationMessage);

            // Click in modal to add client
            $browser->waitFor('#addModal.modal.fade.show')
                    ->waitForText('Confirm')
                    ->press('Confirm');

            // Dismiss toastr notification
            $browser->waitForText('Error!')
                    ->click('#toast-container')
                    ->waitUntilMissing('#toast-container')
                    ->assertDontSee('Error!');

            $browser->waitForText('The email address must be in a valid format.')
                    ->assertSee('The email address must be in a valid format.');
        });
    }

    /**
     * @group adminClients
     * @return void
     */
    public function addSuccess1()
    {
        $this->browse(function (Browser $browser) {

            $this->initAndSelectAddClientForm($browser);

            $browser->waitFor('#email0')
                    ->type('email[0]', 'dusk@autotest.com')
                    ->type('refnum', 'File 123456 from dusk');

            $browser->element('#openAddModal')->getLocationOnScreenOnceScrolledIntoView();

            $browser->waitFor('#openAddModal')
                ->click('#openAddModal')
                ->waitForText('Confirm')  // check modal
                ->assertSee($this->selected_wireDoc->name)
                ->assertSee($this->notificationMessage);

            // Click in modal to add client
            $browser->waitFor('#addModal.modal.fade.show')
                ->waitForText('Confirm')
                ->press('Confirm');

            // Dismiss toastr notification
            $browser->waitForText('Success!', 10)
                ->click('#toast-container')
                ->waitUntilMissing('#toast-container')
                ->assertDontSee('Success!');

            $this->verifyAddClientInputValues($browser, $this->selected_company_id, $this->selected_branch_id);
        });
    }

    /**
     * @group adminClients
     * @return void
     */
    public function addSuccess2()
    {
        $this->browse(function (Browser $browser) {

            $this->initAndSelectAddClientForm($browser);

            // Add client 1
            $browser->waitFor('#email0')
                    ->type('email[0]', 'dusk@autotest.com')
                    ->type('refnum', 'File 234567 from dusk');

            $browser->waitFor('.add_client_btn')
                    ->click('.add_client_btn')
                    ->waitFor('#email1')
                    ->type('email[1]', 'dusk@autotest.com');

            $browser->click('.remove_client_btn')
                    ->waitUntilMissing('#email1');

            $browser->click('.add_client_btn')
                    ->waitFor('#email1')
                    ->type('email[1]', 'dusk2@autotest.com');

            $browser->element('#openAddModal')->getLocationOnScreenOnceScrolledIntoView();

            $browser->waitFor('#openAddModal')
                    ->click('#openAddModal')
                    ->waitForText('Confirm')  // check modal
                    ->assertSee($this->selected_wireDoc->name)
                    ->assertSee($this->notificationMessage);

            // Click in modal to add client
            $browser->waitFor('#addModal.modal.fade.show')
                    ->waitForText('Confirm')
                    ->press('Confirm');

            // Dismiss toastr notification
            $browser->waitForText('Success!', 10)
                    ->click('#toast-container')
                    ->waitUntilMissing('#toast-container')
                    ->assertDontSee('Success!');

            $this->verifyAddClientInputValues($browser, $this->selected_company_id, $this->selected_branch_id);
        });
    }

    /**
     * @group adminClients
     * @throws
     * @return void
     */
    public function addWithPhone()
    {
        $this->browse(function (Browser $browser) {

            // No test needed if account doesn't allow phone
            if ( !$this->user->account->allows_buyer_phone) {
                $this->browse(function (Browser $browser) {
                    $browser->assertMissing('#phone0');
                });
            } else {
                $this->initAndSelectAddClientForm($browser);

                // Add client 1
                $browser->waitFor('#email0')
                    ->type('email[0]', 'dusk@autotest.com')
                    ->type('refnum', 'File 987654321 from dusk');

                $browser->element('#openAddModal')->getLocationOnScreenOnceScrolledIntoView();

                $browser->waitFor('#openAddModal')
                    ->click('#openAddModal')
                    ->waitForText('Confirm')// check modal
                    ->assertSee($this->selected_wireDoc->name)
                    ->assertSee($this->notificationMessage);

                // Click in modal to cancel add client
                $browser->waitFor('#addModal.modal.fade.show')
                    ->waitForText('Cancel')
                    ->press('Cancel')
                    ->waitUntilMissing('Cancel');

                // Check client side phone validation
                $browser->waitForText('Add Client')
                    ->type('phone[0]', '256')
                    ->click('#openAddModal')
                    ->waitForText('Please enter a valid phone number.')
                    ->assertSee('Please enter a valid phone number.');

                // Enter valid phone number
                $browser->type('phone[0]', '+1(256.457-2639')
                    ->click('#openAddModal')
                    ->waitForText('Confirm')// check modal
                    ->assertSee($this->selected_wireDoc->name)
                    ->assertSee($this->notificationMessage);

                // Click in modal to add client
                $browser->waitFor('#addModal.modal.fade.show')
                    ->waitForText('Confirm')
                    ->press('Confirm');

                // Dismiss toastr notification
                $browser->waitForText('Success!', 10)
                    ->click('#toast-container')
                    ->waitUntilMissing('#toast-container')
                    ->assertDontSee('Success!');

                $this->verifyAddClientInputValues($browser, $this->selected_company_id, $this->selected_branch_id);
            }
        });
    }

    /**
     * @group adminClients
     * @return void
     */
    public function addNotificationText()
    {
        $this->browse(function (Browser $browser) {

            $this->initAndSelectAddClientForm($browser);

            // Add client
            $browser->waitFor('#email0')
                ->type('email[0]', 'dusk@autotest.com')
                ->type('refnum', 'No notification from dusk');

            $browser->element('#openAddModal')->getLocationOnScreenOnceScrolledIntoView();

            $browser->waitFor('#openAddModal')
                ->click('#notify-label')
                ->click('#openAddModal')
                ->waitForText('Confirm')// check modal
                ->assertSee($this->selected_wireDoc->name)
                ->assertSee('*Will not receive any notification');

            // Click in modal to cancel add client
            $browser->waitFor('#addModal.modal.fade.show')
                ->waitForText('Cancel')
                ->press('Cancel')
                ->waitUntilMissing('.modal-backdrop');

            $browser->click('#notify-label')  // check notify
                ->click('#openAddModal')
                ->waitForText('Confirm')// check modal
                ->assertSee($this->selected_wireDoc->name)
                ->assertSee($this->notificationMessage)
                ->press('Confirm');

            // Dismiss toastr notification
            $browser->waitForText('Success!', 10)
                ->click('#toast-container')
                ->waitUntilMissing('#toast-container')
                ->assertDontSee('Success!');

            $this->verifyAddClientInputValues($browser, $this->selected_company_id, $this->selected_branch_id);
        });
    }

    /**
     * Check that an 'inactive' WireDoc functions properly for adding clients and viewable in 'Settings'
     * @group adminClients
     * @return void
     */
    public function checkInactiveWireDocText()
    {
        $this->browse(function (Browser $browser) {

            // Initialize selected_wireDoc
            $this->initAndSelectAddClientForm($browser);

            $prevActiveFlag = $this->selected_wireDoc->active;

            $this->selected_wireDoc->forceFill(['active' => 0])->save();

            // Check add client for '(inactive)' text
            $browser->visit(route('userHome'))
                    ->waitForText('ADD CLIENTS');

            $this->initAndSelectAddClientForm($browser);

            $browser->waitFor('#email0')
                    ->type('email[0]', 'dusk@autotest.com');

            if ($this->user->role == 'Admin') {
                // Only 'Admins' can see inactive wire instructions when adding clients
                $browser->assertSee($this->selected_wireDoc->name . ' (inactive)');
            } elseif ($this->user->role == 'User') {
                // 'Users' only have active wire instructions available when adding clients
                $browser->assertDontSee($this->selected_wireDoc->name . ' (inactive)');
            } else {
                $this->assertTrue(false, 'Unhandled user role: ' . $this->user->role);
            }

            // Check settings for '(inactive)' text
            $browser->visit(route('settings'))
                    ->waitForText('AVAILABLE WIRE INSTRUCTIONS')
                    ->assertSee($this->selected_wireDoc->name . ' (inactive)');

            $this->selected_wireDoc->forceFill(['active' => 1])->save();

            // Check add client for missing '(inactive)' text
            $browser->visit(route('userHome'))
                    ->waitForText('ADD CLIENTS');

            $this->initAndSelectAddClientForm($browser);

            $browser->waitFor('#email0')
                    ->type('email[0]', 'dusk@autotest.com')
                    ->assertSee($this->selected_wireDoc->name)
                    ->assertDontSee($this->selected_wireDoc->name . ' (inactive)');

            // Check settings for missing '(inactive)' text
            $browser->visit(route('settings'))
                    ->waitForText('AVAILABLE WIRE INSTRUCTIONS')
                    ->assertSee($this->selected_wireDoc->name)
                    ->assertDontSee($this->selected_wireDoc->name . ' (inactive)');

            $this->selected_wireDoc->forceFill(['active' => $prevActiveFlag])->save();
        });
    }

    /**
     * @group adminClients
     * @return void
     */
    public function notifyClient()
    {
        $this->browse(function (Browser $browser) {

            $usernames = ['dusk@autotest.com2', 'dusk2@autotest.com2'];

            foreach ($usernames as $username) {
                $buyer = Buyer::where('username', $username)->firstOrFail();

                foreach ($buyer->buyerFiles as $buyerFile) {
                    // Wait for datatable to load
                    $browser->waitForText($buyer->email);

                    // Scroll to delete button
                    $browser->element('#notify-' . $buyerFile->id)->getLocationOnScreenOnceScrolledIntoView();

                    $browser->waitFor('#notify-' . $buyerFile->id)
                        ->click('#notify-' . $buyerFile->id)
                        ->waitFor('#notifyModal.modal.fade.show')
                        ->waitForText('Notify Client')  // check modal
                        ->assertSee($buyerFile->wireDoc->name)
                        ->press('Notify');

                    // Dismiss toastr notification
                    $browser->waitForText('Notified client')
                        ->click('#toast-container')
                        ->waitUntilMissing('#toast-container')
                        ->assertDontSee('Notified client');
                }
            }
            $this->verifyAddClientInputValues($browser, $this->default_company_id, $this->default_branch_id);
        });
    }

    /**
     * @group adminClients
     * @return void
     */
    public function deleteClient()
    {
        $this->browse(function (Browser $browser) {

            $usernames = ['dusk@autotest.com2', 'dusk2@autotest.com2'];

            foreach ($usernames as $username) {
                $buyer = Buyer::where('username', $username)->firstOrFail();

                foreach ($buyer->buyerFiles as $buyerFile) {
                    // Wait for datatable to load
                    $browser->waitForText($buyer->email);

                    // Scroll to delete button
                    $browser->element('#delete-' . $buyerFile->id)->getLocationOnScreenOnceScrolledIntoView();

                    $browser->waitFor('#delete-' . $buyerFile->id)
                            ->click('#delete-' . $buyerFile->id)
                            ->waitFor('#deleteModal.modal.fade.show')
                            ->waitForText('Delete Client')  // check modal
                            ->assertSee($buyerFile->wireDoc->name)
                            ->press('Delete');

                    // Dismiss toastr notification
                    $browser->waitForText('Removed client')
                            ->click('#toast-container')
                            ->waitUntilMissing('#toast-container')
                            ->assertDontSee('Removed client');
                }
            }
            $this->verifyAddClientInputValues($browser, $this->default_company_id, $this->default_branch_id);
        });
    }
}
