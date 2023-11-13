<?php
/**
 * Copyright (c) 2018, BuyerDocs, LLC.
 * All rights reserved.
 */

namespace Tests\Browser;

use App\User;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;

class adminManageAdminsTest extends DuskTestCase
{
    /**
     * @group user
     * @throws
     * @return void
     */
    public function testAddExistingAdmin()
    {
        $this->browse(function (Browser $browser) {

            $admin = User::where([
                ['name', 'Test User'],
                ['email', 'test@test.test'],
            ])->firstOrFail();

            $browser->loginAs($admin);
                // ->visit(route('userHome'))
                // ->waitForText('ADD CLIENTS');

            $browser->visit(route('manage'))
                ->waitForText('ADD USER')
                ->assertSee('ADD USER');

            $browser->element('#openAddModal')->getLocationOnScreenOnceScrolledIntoView();

            // Add existing user
            $browser->pause(1000)
                ->type('name', 'Test User Add Existing Admin')
                ->type('email', 'test@test.test')
                ->type('password', 'currentPassword1')
                ->click('#openAddModal')
                ->waitForText('Confirm');

            $browser->waitFor('#addAdminModal.modal.fade.show')
                ->pause(500)// allow modal to open
                ->press('Confirm');

            $browser->element('#openAddModal')->getLocationOnScreenOnceScrolledIntoView();

            // Wait for error message on page reload
            $browser->waitForText('The email has already been taken.')
                ->assertSee('Error');
            // Dismiss toastr notification
            $browser->click('#toast-container')
                ->waitUntilMissing('#toast-container')
                ->assertDontSee('Error');
        });
    }

    /**
     * @group user
     * @throws
     * @return void
     */
    public function testAddNewAdmin()
    {
        $this->browse(function (Browser $browser) {

            $admin = User::where([
                ['name', 'Test User'],
                ['email', 'test@test.test'],
            ])->firstOrFail();

            $browser->visit(route('home'));

            $browser->loginAs($admin);
            // ->visit(route('userHome'))
            // ->waitForText('ADD CLIENTS');

            $browser->visit(route('manage'))
                ->waitForText('ADD USER')
                ->assertSee('ADD USER');

            $browser->element('#openAddModal')->getLocationOnScreenOnceScrolledIntoView();

            $browser->pause(1000)
                ->type('name', 'Test User Added Admin')
                ->type('email', 'dusktest1@buyerdocs.com')
                ->type('password', 'currentPassword1')
                ->click('#openAddModal')
                ->waitForText('Confirm');

            $browser->waitFor('#addAdminModal.modal.fade.show')
                ->pause(500)// allow modal to open
                ->press('Confirm');

            // Wait for toastr notification
            $browser->waitForText('Added User');
            // Dismiss toastr notification
            $browser->click('#toast-container')
                ->waitUntilMissing('#toast-container')
                ->assertDontSee('Added User');
        });
    }

    /**
     * @group user
     * @throws
     * @return void
     */
    public function testUpdateAdmin()
    {
        $this->browse(function (Browser $browser) {

            $admin = User::where([
                ['name', 'Test User'],
                ['email', 'test@test.test'],
            ])->firstOrFail();

            $browser->visit(route('home'));
            $browser->loginAs($admin);

            $adminToUpdate = User::where('email', 'dusktest1@buyerdocs.com')->firstOrFail();

            // Wait for datatable to load
            $browser->visit(route('manage'))
                ->waitFor('#openUpdateModal-' . $adminToUpdate->id);

            // Scroll to update button
            $browser->element('#openUpdateModal-' . $adminToUpdate->id)->getLocationOnScreenOnceScrolledIntoView();

            $browser->pause(500)
                ->click('#openUpdateModal-' . $adminToUpdate->id)
                ->waitFor('#updateAdminModal.modal.fade.show')
                ->waitForText('Edit User')  // check modal
                ->pause(500)                // allow modal to open
                ->assertSelected('#select-role', "User")
                ->assertSelected('#select-company', "")
                ->assertSelected('#select-branch', "")
                ->press('Confirm');

            // Wait for and dismiss toastr notification
            $browser->waitForText('Updated User')
                ->click('#toast-container')
                ->waitUntilMissing('#toast-container')
                ->assertDontSee('Updated User');

            // Make sure user is not removed from table
            $browser->assertSee($adminToUpdate->name)
                ->assertSee($adminToUpdate->email)
                ->assertSee($adminToUpdate->role);

            // Scroll to update button
            $browser->element('#openUpdateModal-' . $adminToUpdate->id)->getLocationOnScreenOnceScrolledIntoView();

            // Assure nothing changed and then update user
            $browser->pause(500)
                ->click('#openUpdateModal-' . $adminToUpdate->id)
                ->waitFor('#updateAdminModal.modal.fade.show')
                ->waitForText('Edit User')  // check modal
                ->pause(500)                // allow modal to open
                ->assertSelected('#select-role', "User")
                ->assertSelected('#select-company', "")
                ->assertSelected('#select-branch', "")
                ->pause(500)
                // Select Role: Admin (toggle drop down, select Role)
                ->click('#updateAdminForm > div.modal-body > div:nth-child(3) > div > div > button')
                ->pause(100)
                ->click('#updateAdminForm > div.modal-body > div:nth-child(3) > div > div > div > ul > li:nth-child(1) > label > span')
                // Select Company: 'American Title' (toggle drop down, select Company, toggle drop down)
                ->pause(100)
                ->click('#updateAdminForm > div.modal-body > div:nth-child(4) > div > div > button')
                ->pause(100)
                ->click('#updateAdminForm > div.modal-body > div:nth-child(4) > div > div > div > ul > li:nth-child(2) > label > input[type="checkbox"]')
                ->pause(100)
                ->click('#updateAdminForm > div.modal-body > div:nth-child(4) > div > div > button')
                // Select Branches: 'Austin' and 'Demo Branch' (toggle drop down, select Branches, toggle drop down)
                ->pause(100)
                // not needed as company has only one branch, and it automatically pre-selects
//                ->click('#updateAdminForm > div.modal-body > div:nth-child(5) > div > div > button')
//                ->pause(100)
//                ->click('#updateAdminForm > div.modal-body > div:nth-child(5) > div > div > div > ul > li:nth-child(2) > label > input[type="checkbox"]')
//                ->pause(100)
//                ->click('#updateAdminForm > div.modal-body > div:nth-child(5) > div > div > button')
//                ->pause(100)
                ->assertSee('Admin')
                ->assertSee('Title Name')
                ->assertSee('Company: Title Name')
                ->press('Confirm');

            // Wait for and dismiss toastr notification
            $browser->waitForText('Updated User')
                ->click('#toast-container')
                ->waitUntilMissing('#toast-container')
                ->assertDontSee('Updated User');

            // Scroll to update button
            $browser->element('#openUpdateModal-' . $adminToUpdate->id)->getLocationOnScreenOnceScrolledIntoView();

            // Make sure user is not removed from table
            $browser->assertSee($adminToUpdate->name)
                ->assertSee($adminToUpdate->email)
                ->assertSee($adminToUpdate->role);

            // Assure update occurred
            $browser->pause(500)
                ->click('#openUpdateModal-' . $adminToUpdate->id)
                ->waitFor('#updateAdminModal.modal.fade.show')
                ->waitForText('Edit User')  // check modal
                ->pause(500)                // allow modal to open
                ->assertSee("Admin")
                ->assertSee('Title Name')
                ->assertSee('Company: Title Name')
                ->pause(500)
                // Select Role: User (toggle drop down, select Role)
                ->click('#updateAdminForm > div.modal-body > div:nth-child(3) > div > div > button')
                ->click('#updateAdminForm > div.modal-body > div:nth-child(3) > div > div > div > ul > li:nth-child(2) > label > span')
                // Select Company: '' (toggle drop down, select Company 'American Title' to uncheck, toggle drop down)
                ->click('#updateAdminForm > div.modal-body > div:nth-child(4) > div > div > button')
                ->click('#updateAdminForm > div.modal-body > div:nth-child(4) > div > div > div > ul > li:nth-child(2) > label > input[type="checkbox"]')
                ->click('#updateAdminForm > div.modal-body > div:nth-child(4) > div > div > button')
                // Branches clear automatically when Company selection is null
                ->assertSee('Admin')
                ->assertDontSee('Company: Title Name')
                ->press('Confirm');

            // Wait for and dismiss toastr notification
            $browser->waitForText('Updated User')
                ->click('#toast-container')
                ->waitUntilMissing('#toast-container')
                ->assertDontSee('Updated User');

            // Scroll to update button
            $browser->element('#openUpdateModal-' . $adminToUpdate->id)->getLocationOnScreenOnceScrolledIntoView();

            $browser->pause(500)
                ->click('#openUpdateModal-' . $adminToUpdate->id)
                ->waitFor('#updateAdminModal.modal.fade.show')
                ->waitForText('Edit User')  // check modal
                ->pause(500)                // allow modal to open
                ->assertSelected('#select-role', "User")
                ->assertSelected('#select-company', "")
                ->assertSelected('#select-branch', "")
                ->press('Confirm');

            // Wait for and dismiss toastr notification
            $browser->waitForText('Updated User')
                ->click('#toast-container')
                ->waitUntilMissing('#toast-container')
                ->assertDontSee('Updated User');

            // Make sure user is not removed from table
            $browser->assertSee("0 assigned")
                ->assertSee($adminToUpdate->name)
                ->assertSee($adminToUpdate->email)
                ->assertSee($adminToUpdate->role);

        });
    }

    /**
     * @group user
     * @throws
     * @return void
     */
    public function testDeleteAdmin()
    {
        $this->browse(function (Browser $browser) {

            $admin = User::where([
                ['name', 'Test User'],
                ['email', 'test@test.test'],
            ])->firstOrFail();

            $browser->visit(route('home'));
            $browser->loginAs($admin);

            $adminToDelete = User::where('email', 'dusktest1@buyerdocs.com')->firstOrFail();

            // Wait for datatable to load
            $browser->visit(route('manage'))
                ->waitFor('#openDeleteModal-' . $adminToDelete->id);

            // Scroll to delete button
            $browser->element('#openDeleteModal-' . $adminToDelete->id)->getLocationOnScreenOnceScrolledIntoView();

            $browser->pause(500)
                ->click('#openDeleteModal-' . $adminToDelete->id)
                ->waitFor('#deleteAdminModal.modal.fade.show')
                ->waitForText('Delete Admin')  // check modal
                ->pause(500)                // allow modal to open
                ->press('Delete');

            // Wait for and dismiss toastr notification
            $browser->waitForText('Deleted User')
                ->click('#toast-container')
                ->waitUntilMissing('#toast-container')
                ->assertDontSee('Deleted User');

            // Make sure user is removed from table
            $browser->assertDontSee($adminToDelete->name)
                ->assertDontSee($adminToDelete->email);
        });
    }
}
