*** Settings ***
Library           SeleniumLibrary  timeout=20
Suite Teardown    Close All Browsers
Test Teardown     Close All Browsers
Resource          ../Resources/keywords.robot
Library           robot.libraries.DateTime

*** Variables ***
# Users variables
${User_Button}                     css=[href='${SERVER}user/manage']
${User_Name}                       mansoor
${User_Email}                      mansoornasir@icp.edu.pk
${User_Password}                   buyerdocsisgreat
${User_Name_Field}                 css=#name
${User_Email_Field}                css=#email
${User_Password_Field}             css=#password
${Add_User_Button}                 css=#openAddModal
${Confirm_Add_User_Button}         css=button#submitAddForm.btn-circle.btn-brand.btn
${User_Search_Field}               css=#adminTable_filter > label > input
${User_Delete_Button}              css=i.fa-times.fa-lg.fa
${User_Confirm_Delete_Button}      css=#deleteAdminForm button.btn-danger
${User_Edit_Button}                css=i.fa.fa-pencil.fa-lg
${User_Edit_Name_Field}            css=#update_admin_name
${User_Edit_Email_Field}           css=#update_admin_email
${User_Edit_Role_Field}            css=.ms-choice [title='User']
${User_Edit_Role}                  css=.ms-choice [title='Admin']
${User_Role_Admin}                 css=ul li:nth-child(1) label
${User_Role_User}                  css=ul li:nth-child(2) label
${User_Edit_Confirm_Button}        css=button.btn.btn-success.btn-circle[type='Submit']
${Email_Missing_Error}             css=#email-error
${Cancel_Delete_User}              css=#deleteAdminModal .modal-footer button[data-dismiss='modal']
${Select_Company_Dropdown}         css=.modal-body div:nth-child(4) .ms-choice
${Select_Single_Company}           css=ul li:nth-child(2) label input[type="checkbox"]
${Company_Table}                   css=#adminTable tbody tr td:nth-child(4)
${User_Updated_Message}            css=.toast-success .toast-message
${Select_Multiple_Companies}       css=ul li:nth-child(3) label input[type="checkbox"]
${Select_All_Companies}            css=ul li:nth-child(1) label input[type="checkbox"]
${Branch_Table}                    css=#adminTable tbody tr td:nth-child(5)
${Pagination_Next}                 css=#adminTable_next a
${Page_2_Button_Active}            css=.active [data-dt-idx='2']
${Pagination_Previous}             css=#adminTable_previous a
${Page_1_Button_Active}            css=.active.paginate_button [data-dt-idx='1']
${Pagination_2_Button}             css=[data-dt-idx='2']
${Pagination_1_Button}             css=[data-dt-idx='1']
${Pagination_Dropdown}             css=.input-sm[name='adminTable_length']
${Select_25}                       css=#adminTable_length label select option:nth-child(2)

*** Test Cases ***

Scenario: Verify that admin can add a new user
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
     When I add new "User"
     Then I should see the new user being added

Scenario: Verify that admin cannot add user with emp
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
     When I add a new "User" without email
     Then I should not be able to add the user and see an error

Scenario: Search for non existant user
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
     When I search for invalid user
     Then I should see no users

Scenario: Search for existing user by "name"
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
     When I search for valid users by name
     Then I should see matching names

Scenario: Search for existing user by "email"
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
     When I search for valid users by email
     Then I should see matching email

Scenario: Search for existing user by "role"
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
     When I search for valid users by role
     Then I should see matching role

Scenario: Verify that admin can update the name of the user
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
      And Search for valid users by name
     When I edit the name of the user
     Then I should see the name of the user being edited

Scenario: Verify that admin can update the email address of the user
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
      And Search for valid users by name
     When I edit the email of the user
     Then I should see the email of the user being edited

Scenario: Verify that admin can change user roles user to admin
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
      And Search for valid users by name
     When I update the permissions of the user from "user" to "admin"
     Then I should see the permissions being updated user to admin

Scenario: Verify that admin can change user roles from admin to user
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
      And Search for valid users by name
     When I update the permissions of the user from "admin" to "user"
     Then I should see the permissions being updated from admin to user

Scenario: Verify that a new user is not assigned a branch by default
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
     When Search for valid users by name
     Then I should see that the new user is not assigned a branch by default

Scenario: Verify that a new user is not assigned a company by default
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
     When Search for valid users by name
     Then I should see that the new user is not assigned a company by default

Scenario: Verify that admin can assign a company to newly added users
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
      And Search for valid users by name
     When I add a single companyagainst the new user
     Then I should see the company being added against the user

Scenario: Verify that admin can unassign a company to newly added users
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
      And Search for valid users by name
     When I unassign a single company against the new user
     Then I should see the company being unassigned against the user

Scenario: Verify that admin can assign multiple companies to a newly added users
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
      And Search for valid users by name
     When I assign multiple companies against the new user
     Then I should see multiple companies being added against the user

Scenario: Verify that admin can unassign multiple companies to a newly added users
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
      And Search for valid users by name
     When I unassign multiple companies against the a new user
     Then I should see multiple companies being unassigned against the user

Scenario: Verify that admin can assign all companies to a newly added users
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
      And Search for valid users by name
     When I assign all companies against the a new user
     Then I should see all companies being added against the user

Scenario: Verify that admin can unassign all companies to a newly added users
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
      And Search for valid users by name
     When I unassign all companies against the a new user
     Then I should see all companies being unassigned against the user

Scenario: Verify that user is not deleted when admin click cancel
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
      And Search for valid users by name
     When I click cancel delete user
     Then I should see the user not being deleted

Scenario: Verify that admin can delete a user
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
      And Search for valid users by name
     When I delete the user
     Then I should not see the deleted user

Scenario: Verify that admin cannot add a user without email address
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
     When I try to add a user without email
     Then I should not see the user being added and error being shown

Scenario: Verify that admin cannot add a user without Name address
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
     When I try to add a user without Name
     Then I should not see the user being added and error being shown

Scenario: Verify that admin cannot add a user without Name address
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
     When I try to add a user without Password
     Then I should not see the user being added and error being shown

Scenario: Verify that admin cannot add a user with empty fields
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
      And Navigate to "Users"
     When I try to add a user with empty fields
     Then I should not see the user being added and error being shown

# Scenario: Verify that pagination button "Next" is working
#     Given Navigate to "buyerdocs.com"
#       And Login with valid credentials
#       And Navigate to "Users"
#      When I click Next Button
#      Then I should be on the next page

# Scenario: Verify that pagination button "Previous" is working
#    Given Navigate to "buyerdocs.com"
#      And Login with valid credentials
#      And Navigate to "Users"
#     When I click Next Button
#      And I click Previous button
#     Then I should be on the previous page

# Scenario: Verify that pagination button "2" is working
#     Given Navigate to "buyerdocs.com"
#       And Login with valid credentials
#       And Navigate to "Users"
#      When I click pagination button "2"
#      Then I should be on page number "2"

# Scenario: Verify that pagination button "1" is working
#    Given Navigate to "buyerdocs.com"
#      And Login with valid credentials
#      And Navigate to "Users"
#     When I click pagination button "1"
#     Then I should be on the page number "1"

# Scenario: Verify that show entries dropdown is working
#    Given Navigate to "buyerdocs.com"
#      And Login with valid credentials
#      And Navigate to "Users"
#     When I increase the entries number to "25"
#     Then I should see "25" entries on the first page

*** Keywords ***

#Given---------------------------------------------------------------------------

Navigate to "Users"
  Wait Until Page Contains Element    ${User_Button}
  Click Element  ${User_Button}

Search for valid users by name
  Wait Until Page Contains Element  ${User_Search_Field}
  Input Text  ${User_Search_Field}  mansoor

# #When---------------------------------------------------------------------------

I add new "User"
  Wait Until Page Contains Element  ${User_Name_Field}
  Sleep     1s
  Input Text  ${User_Name_Field}  ${User_Name}
  Wait Until Element Is Enabled    ${User_Email_Field}
  ${CurrentDate}=  Get Current Date  result_format=%Y-%m-%d%H%S
  Input Text  ${User_Email_Field}  mansoor${CurrentDate}nasir@icp.edu.pk
  Input Text  ${User_Password_Field}  ${User_Password}
  Sleep    1s
  Click Element  ${Add_User_Button}
  Wait Until Page Contains Element  ${confirm_add_user_button}
  Wait Until Element Is Visible    ${confirm_add_user_button}
  Click Element  ${confirm_add_user_button}
  wait until page contains  Added User

I add a new "User" without email
  Wait Until Page Contains Element  ${User_Name_Field}
  Input Text  ${User_Name_Field}  ${User_Name}
  Sleep    2s
  Wait Until Element Is Enabled    ${User_Password_Field}
  Input Text  ${User_Password_Field}  ${User_Password}
  Click Element  ${Add_User_Button}

I search for invalid user
  Wait Until Page Contains Element  ${User_Search_Field}
  Input Text  ${User_Search_Field}  jjj

I search for valid users by name
  Wait Until Page Contains Element  ${User_Search_Field}
  Input Text  ${User_Search_Field}  Single Branch User

I search for valid users by email
  Wait Until Page Contains Element  ${User_Search_Field}
  Input Text  ${User_Search_Field}  singlebranchuser@test.test

I search for valid users by role
  Wait Until Page Contains Element  ${User_Search_Field}
  Input Text  ${User_Search_Field}  Admin

I delete the user
  Wait Until Page Contains Element    ${User_Delete_Button}
  Click Element    ${User_Delete_Button}
  Wait Until Page Contains Element    ${User_Confirm_Delete_Button}
  Wait Until Element Is Visible    ${User_Confirm_Delete_Button}
  Sleep    2s
  Click Element    ${User_Confirm_Delete_Button}

I click cancel delete user
  Wait Until Page Contains Element    ${User_Delete_Button}
  Click Element    ${User_Delete_Button}
  Sleep    2s
  Wait Until Element Is Visible    ${Cancel_Delete_User}
  Wait Until Element Is Enabled    ${Cancel_Delete_User}
  Click Button    ${Cancel_Delete_User}

I edit the name of the user
  Wait Until Page Contains Element    ${User_Edit_Button}
  Click Element    ${User_Edit_Button}
  Wait Until Page Contains Element    ${User_Edit_Name_Field}
  Wait Until Element Is Enabled    ${User_Edit_Name_Field}
  Wait Until Element Is Visible    ${User_Edit_Name_Field}
  Sleep    2s
  Input Text    ${User_Edit_Name_Field}    mansoor edited
  Wait Until Page Contains Element    ${User_Edit_Confirm_Button}
  Click Element    ${User_Edit_Confirm_Button}

I edit the email of the user
  Wait Until Page Contains Element    ${User_Edit_Button}
  Click Element    ${User_Edit_Button}
  Wait Until Page Contains Element    ${User_Edit_Email_Field}
  Wait Until Element Is Enabled    ${User_Edit_Email_Field}
  Wait Until Element Is Visible    ${User_Edit_Email_Field}
  Sleep    2s
  Input Text    ${User_Edit_Email_Field}    EDITED-mansoornasir@icp.edu.pk
  Wait Until Page Contains Element    ${User_Edit_Confirm_Button}
  Click Element    ${User_Edit_Confirm_Button}

I update the permissions of the user from "user" to "admin"
  Wait Until Page Contains Element    ${User_Edit_Button}
  Click Element    ${User_Edit_Button}
  Wait Until Page Contains Element    ${User_Edit_Role_Field}
  Wait Until Element Is Enabled    ${User_Edit_Role_Field}
  Wait Until Element Is Visible    ${User_Edit_Role_Field}
  Sleep    2s
  Click Element    ${User_Edit_Role_Field}
  Sleep    1s
  Wait Until Page Contains Element    ${User_Role_Admin}
  Click Element    ${User_Role_Admin}
  Wait Until Page Contains Element    ${User_Edit_Confirm_Button}
  Click Element    ${User_Edit_Confirm_Button}

I update the permissions of the user from "admin" to "user"
  Wait Until Page Contains Element    ${User_Edit_Button}
  Click Element    ${User_Edit_Button}
  Wait Until Page Contains Element    ${User_Edit_Role}
  Wait Until Element Is Enabled    ${User_Edit_Role}
  Wait Until Element Is Visible    ${User_Edit_Role}
  Sleep    2s
  Click Element    ${User_Edit_Role}
  Sleep    1s
  Wait Until Page Contains Element    ${User_Role_Admin}
  Click Element    ${User_Role_Admin}
  Wait Until Page Contains Element    ${User_Edit_Confirm_Button}
  Click Element    ${User_Edit_Confirm_Button}

I add a single company against the new user
  Wait Until Page Contains Element    ${User_Edit_Button}
  Click Element    ${User_Edit_Button}
  Wait Until Page Contains Element    ${User_Edit_Role}
  Wait Until Element Is Enabled    ${User_Edit_Role}
  Wait Until Element Is Visible    ${User_Edit_Role}
  Wait Until Page Contains Element    ${Select_Company_Dropdown}
  Sleep    2s
  Click Element    ${Select_Company_Dropdown}
  Sleep    1s
  Wait until page contains element    ${Select_Single_Company}
  Click Element    ${Select_Single_Company}
  Wait Until Page Contains Element    ${Select_Company_Dropdown}
  Click Element    ${Select_Company_Dropdown}
  Wait Until Page Contains Element    ${User_Edit_Confirm_Button}
  Click Element    ${User_Edit_Confirm_Button}
  Wait Until Page Contains Element    ${User_Updated_Message}

I unassign a single company against the new user
  Wait Until Page Contains Element    ${User_Edit_Button}
  Click Element    ${User_Edit_Button}
  Wait Until Page Contains Element    ${User_Edit_Role}
  Wait Until Element Is Enabled    ${User_Edit_Role}
  Wait Until Element Is Visible    ${User_Edit_Role}
  Wait Until Page Contains Element    ${Select_Company_Dropdown}
  Sleep    2s
  Click Element    ${Select_Company_Dropdown}
  Sleep    1s
  Wait until page contains element    ${Select_Single_Company}
  Click Element    ${Select_Single_Company}
  Wait Until Page Contains Element    ${Select_Company_Dropdown}
  Click Element    ${Select_Company_Dropdown}
  Wait Until Page Contains Element    ${User_Edit_Confirm_Button}
  Click Element    ${User_Edit_Confirm_Button}
  Wait Until Page Contains Element    ${User_Updated_Message}

I assign multiple companies against the new user
  Wait Until Page Contains Element    ${User_Edit_Button}
  Click Element    ${User_Edit_Button}
  Wait Until Page Contains Element    ${User_Edit_Role}
  Wait Until Element Is Enabled    ${User_Edit_Role}
  Wait Until Element Is Visible    ${User_Edit_Role}
  Wait Until Page Contains Element    ${Select_Company_Dropdown}
  Sleep    2s
  Click Element    ${Select_Company_Dropdown}
  Sleep    1s
  Wait until page contains element    ${Select_Single_Company}
  Click Element    ${Select_Single_Company}
  Sleep    1s
  Wait until page contains element    ${Select_Multiple_Companies}
  Click Element    ${Select_Multiple_Companies}
  Wait Until Page Contains Element    ${Select_Company_Dropdown}
  Click Element    ${Select_Company_Dropdown}
  Wait Until Page Contains Element    ${User_Edit_Confirm_Button}
  Click Element    ${User_Edit_Confirm_Button}
  Wait Until Page Contains Element    ${User_Updated_Message}

I unassign multiple companies against the a new user
  Wait Until Page Contains Element    ${User_Edit_Button}
  Click Element    ${User_Edit_Button}
  Wait Until Page Contains Element    ${User_Edit_Role}
  Wait Until Element Is Enabled    ${User_Edit_Role}
  Wait Until Element Is Visible    ${User_Edit_Role}
  Wait Until Page Contains Element    ${Select_Company_Dropdown}
  Sleep    2s
  Click Element    ${Select_Company_Dropdown}
  Sleep    1s
  Wait until page contains element    ${Select_Single_Company}
  Click Element    ${Select_Single_Company}
  Sleep    1s
  Wait until page contains element    ${Select_Multiple_Companies}
  Click Element    ${Select_Multiple_Companies}
  Wait Until Page Contains Element    ${Select_Company_Dropdown}
  Click Element    ${Select_Company_Dropdown}
  Wait Until Page Contains Element    ${User_Edit_Confirm_Button}
  Click Element    ${User_Edit_Confirm_Button}
  Wait Until Page Contains Element    ${User_Updated_Message}

I assign all companies against the a new user
  Wait Until Page Contains Element    ${User_Edit_Button}
  Click Element    ${User_Edit_Button}
  Wait Until Page Contains Element    ${User_Edit_Role}
  Wait Until Element Is Enabled    ${User_Edit_Role}
  Wait Until Element Is Visible    ${User_Edit_Role}
  Wait Until Page Contains Element    ${Select_Company_Dropdown}
  Sleep    2s
  Click Element    ${Select_Company_Dropdown}
  Sleep    1s
  Wait until page contains element    ${Select_All_Companies}
  Click Element    ${Select_All_Companies}
  Wait Until Page Contains Element    ${Select_Company_Dropdown}
  Click Element    ${Select_Company_Dropdown}
  Wait Until Page Contains Element    ${User_Edit_Confirm_Button}
  Click Element    ${User_Edit_Confirm_Button}
  Wait Until Page Contains Element    ${User_Updated_Message}

I unassign all companies against the a new user
  Wait Until Page Contains Element    ${User_Edit_Button}
  Click Element    ${User_Edit_Button}
  Wait Until Page Contains Element    ${User_Edit_Role}
  Wait Until Element Is Enabled    ${User_Edit_Role}
  Wait Until Element Is Visible    ${User_Edit_Role}
  Wait Until Page Contains Element    ${Select_Company_Dropdown}
  Sleep    2s
  Click Element    ${Select_Company_Dropdown}
  Sleep    1s
  Wait until page contains element    ${Select_All_Companies}
  Click Element    ${Select_All_Companies}
  Wait Until Page Contains Element    ${Select_Company_Dropdown}
  Click Element    ${Select_Company_Dropdown}
  Wait Until Page Contains Element    ${User_Edit_Confirm_Button}
  Click Element    ${User_Edit_Confirm_Button}
  Wait Until Page Contains Element    ${User_Updated_Message}

I try to add a user without email
   Wait Until Page Contains Element  ${User_Name_Field}
   Input Text  ${User_Name_Field}  ${User_Name}
   Sleep    2s
   Wait Until Element Is Enabled    ${User_Email_Field}
   Input Text  ${User_Password_Field}  ${User_Password}
   Click Element  ${Add_User_Button}

I try to add a user without Name
   Wait Until Page Contains Element  ${User_Name_Field}
   Sleep    2s
   Wait Until Element Is Enabled    ${User_Email_Field}
   Input Text  ${User_Email_Field}  ${User_Email}
   Sleep    2s
   Input Text  ${User_Password_Field}  ${User_Password}
   Click Element  ${Add_User_Button}

I try to add a user without Password
   Wait Until Page Contains Element  ${User_Name_Field}
   Input Text  ${User_Name_Field}  ${User_Name}
   Sleep    2s
   Wait Until Element Is Enabled    ${User_Email_Field}
   Input Text  ${User_Email_Field}  ${User_Email}
   Click Element  ${Add_User_Button}

I try to add a user with empty fields
   Wait Until Page Contains Element  ${User_Name_Field}
   Wait Until Element Is Enabled    ${User_Email_Field}
   Wait Until Page Contains Element  ${User_Email_Field}
   Wait Until Page Contains Element  ${User_Password_Field}
   Sleep    2s
   Click Element  ${Add_User_Button}

I click Next Button
   Wait Until Page Contains Element  ${User_Name_Field}
   Wait Until Page Contains Element  ${User_Name_Field}
   Wait Until Element Is Enabled    ${User_Email_Field}
   Wait Until Page Contains Element  ${User_Email_Field}
   Wait Until Page Contains Element  ${User_Password_Field}
   Set Focus To Element    ${Pagination_Next}
   Sleep    2s
   Click Element    ${Pagination_Next}
   Wait Until Page Contains Element    ${Page_2_Button_Active}
   Page Should Contain Element    ${Page_2_Button_Active}
   Sleep    2s

I click Previous button
   Set Focus To Element    ${Pagination_Previous}
   Sleep    2s
   Click Element    ${Pagination_Previous}
   Wait Until Page Contains Element    ${Page_1_Button_Active}
   Page Should Contain Element    ${Page_1_Button_Active}

I click pagination button "2"
   Wait Until Page Contains Element  ${User_Name_Field}
   Wait Until Page Contains Element  ${User_Name_Field}
   Wait Until Element Is Enabled    ${User_Email_Field}
   Wait Until Page Contains Element  ${User_Email_Field}
   Wait Until Page Contains Element  ${User_Password_Field}
   Set Focus To Element    ${Pagination_2_Button}
   Sleep    2s
   Click Element    ${Pagination_2_Button}
   Wait Until Page Contains Element    ${Page_2_Button_Active}
   Page Should Contain Element    ${Page_2_Button_Active}
   Sleep    2s

I click pagination button "1"
    Wait Until Page Contains Element  ${User_Name_Field}
    Wait Until Page Contains Element  ${User_Name_Field}
    Wait Until Element Is Enabled    ${User_Email_Field}
    Wait Until Page Contains Element  ${User_Email_Field}
    Wait Until Page Contains Element  ${User_Password_Field}
    Set Focus To Element    ${Pagination_2_Button}
    Sleep    2s
    Click Element    ${Pagination_2_Button}
    Wait Until Page Contains Element    ${Page_2_Button_Active}
    Page Should Contain Element    ${Page_2_Button_Active}
    Sleep    2s
    Set Focus To Element    ${Pagination_1_Button}
    Sleep    2s
    Click Element    ${Pagination_1_Button}
    Wait Until Page Contains Element    ${Page_1_Button_Active}
    Page Should Contain Element    ${Page_1_Button_Active}

I increase the entries number to "25"
   Wait Until Page Contains Element     ${Pagination_Dropdown}
   Click Element    ${Pagination_Dropdown}
   Sleep    0.5s
   Wait Until Element Is Enabled         ${Select_25}
   Click Element    ${Select_25}



#Then---------------------------------------------------------------------------

I should see the new user being added
  Wait Until Page Contains   @icp.edu.pk
  Page Should Contain    mansoor

I should not be able to add the user and see an error
  Wait Until Page Contains   This field is required.
  Page Should Contain    This field is required.
  Element Text Should Be    ${Email_Missing_Error}    This field is required.

I should see no users
  Sleep    1s
  Wait until page contains  No matching records found
  Page Should Contain    No matching records found
  Wait Until Page Contains    Showing 0 to 0 of 0 entries
  Page Should Contain    Showing 0 to 0 of 0 entries

I should see matching names
  Wait Until Page Contains    Single Branch User
  Page Should Contain    Single Branch User

I should see matching email
  Wait Until Page Contains    singlebranchuser@test.test
  Page Should Contain    singlebranchuser@test.test

I should see matching role
  Wait Until Page Contains    test@test.test
  Page Should Contain    Admin

I should not see the deleted user
  Wait Until Page Contains    Deleted User
  Page Should Contain    Deleted User

I should see the user not being deleted
  Wait Until Page Contains    mansoor
  Page Should Contain    mansoor

I should see the name of the user being edited
  Wait Until Page Contains    mansoor edited
  Page Should Contain    mansoor edited

I should see the email of the user being edited
  Wait Until Page Contains    EDITED-mansoornasir@icp.edu.pk
  Page Should Contain    EDITED-mansoornasir@icp.edu.pk

I should see the permissions being updated user to admin
  Wait Until Page Contains Element    ${User_Search_Field}
  Input Text    ${User_Search_Field}    mansoor edited
  Wait Until Page Contains    Admin
  Page Should Contain    Admin

I should see the permissions being updated from admin to user
  Wait Until Page Contains Element    ${User_Search_Field}
  Input Text    ${User_Search_Field}    mansoor edited
  Wait Until Page Contains    User
  Page Should Contain    User

I should see the company being added against the user
  Wait Until Page Contains Element    ${User_Search_Field}
  Input Text    ${User_Search_Field}    mansoor edited
  Wait Until Page Contains Element    ${Company_Table}
  Element Text Should Be    ${Company_Table}    1 assigned

I should see the company being unassigned against the user
  Wait Until Page Contains Element    ${User_Search_Field}
  Input Text    ${User_Search_Field}    mansoor edited
  Wait Until Page Contains Element    ${Company_Table}
  Element Text Should Be    ${Company_Table}    0 assigned

I should see multiple companies being added against the user
  Wait Until Page Contains Element    ${User_Search_Field}
  Input Text    ${User_Search_Field}    mansoor edited
  Wait Until Page Contains Element    ${Company_Table}
  Element Text Should Be    ${Company_Table}    1 assigned

I should see all companies being added against the user
  Wait Until Page Contains Element    ${User_Search_Field}
  Input Text    ${User_Search_Field}    mansoor edited
  Wait Until Page Contains Element    ${Company_Table}
  Element Text Should Be    ${Company_Table}    1 assigned

I should see that the new user is not assigned a company by default
  Wait Until Page Contains Element    ${User_Search_Field}
  Input Text    ${User_Search_Field}    mansoor edited
  Wait Until Page Contains Element    ${Company_Table}
  Element Text Should Be    ${Company_Table}    0 assigned

I should see that the new user is not assigned a branch by default
  Wait Until Page Contains Element    ${User_Search_Field}
  Input Text    ${User_Search_Field}    mansoor edited
  Wait Until Page Contains Element    ${Branch_Table}
  Element Text Should Be    ${Branch_Table}    0 assigned

I should see multiple companies being unassigned against the user
  Wait Until Page Contains Element    ${User_Search_Field}
  Input Text    ${User_Search_Field}    mansoor edited
  Wait Until Page Contains Element    ${Company_Table}
  Element Text Should Be    ${Company_Table}    0 assigned

I should see all companies being unassigned against the user
  Wait Until Page Contains Element    ${User_Search_Field}
  Input Text    ${User_Search_Field}    mansoor edited
  Wait Until Page Contains Element    ${Company_Table}
  Element Text Should Be    ${Company_Table}    1 assigned

I should not see the user being added and error being shown
  Wait Until Page Contains    This field is required.
  Page Should Contain    This field is required.

I should be on the next page
   Wait Until Page Contains Element    ${Page_2_Button_Active}
   Page Should Contain Element    ${Page_2_Button_Active}

I should be on the previous page
   Wait Until Page Contains Element    ${Page_1_Button_Active}
   Page Should Contain Element    ${Page_1_Button_Active}

I should be on page number "2"
  Wait Until Page Contains Element    ${Page_2_Button_Active}
  Page Should Contain Element    ${Page_2_Button_Active}

I should be on the page number "1"
   Wait Until Page Contains Element    ${Page_1_Button_Active}
   Page Should Contain Element    ${Page_1_Button_Active}

I should see "25" entries on the first page
   Wait Until Page Does Not Contain    ${Pagination_2_Button}
   Page Should Not Contain    ${Pagination_2_Button}
