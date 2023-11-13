*** Settings ***
Library           SeleniumLibrary  timeout=20
Suite Teardown    Close All Browsers
Test Teardown     Close All Browsers
Resource          ../Resources/keywords.robot
Library           robot.libraries.DateTime

*** Variables ***
#POM------
${Pre_Close_Button}                      css=.mega-menu [href='${SERVER}user/home']
${Pre_Close_URL}                         ${SERVER}user/home
${Client_Email_Field}                    css=.client_contact_container [data-id='email.0'][placeholder='Enter client email']
${Property_Address_Field}                css=#locationField #address
${Reference_Number_Field}                css=#refnum
${Add_Client_Button}                     css=button#openAddModal.add-modal.btn.btn-brand.btn-circle[type='button']
${Upload_Document}                       xpath=//input[@type='file']
${Company_Dropdown}                      css=#select-company
${Select_Company}                        xpath=//*[@id="select-company"]/option[2]
${Branch_Dropdown}                       css=#select-branch
${Select_Branch}                         xpath=//*[@id="select-branch"]/option[2]
${Client_Email_Address}                  taimur.aamer@yahoo.com
${Property_Address}                      House 13, Street 10, Sector k-4, Austin
${Reference_Number}                      TA-786
${Add_Client_Confirmation_dialogue}      css=#addModal .modal-body
${Add_Client_Confirmation_Button}        css=#addButton
${Search_Field}                          css=[type='search'][aria-controls='buyerTable']
${Search_Email}                          buyer@test.test
${Search_Phone}                          (500) 555-0006
${Search_Passcode}                       106814698
${Email_Table}                           xpath=//*[@id="buyerTable"]/tbody/tr/td[1]
${Phone_Table}                           css=[data-dt-column='3'] .table-responsive td:nth-child(3)
${Reference_Number_Table}                xpath=//*[@id="buyerTable"]/tbody/tr/td[1]
${Search_Element}                        css=#clientTable tbody tr td:nth-child(1)
${Search_Reference_Number}               TA-786
${Search_Company}                        xpath=//*[@id="buyerTable"]/tbody/tr[1]/td[7]
${Search_Empty}                          css=#clientTable tbody tr td
${Delete_Client_Icon}                    css=[title='Delete file']
${Confirm_Delete_Client_Button}          css=#deleteButton
${Remove_Client_File_Text}               Removed client file
${Notify_Client_Icon}                    css=.notify-modal.fa-envelope-o.fa-lg[title='Send notification(s)']
${Confirm_Notify_Client_Button}          css=#notifyButton #notifyButtonText .fa-bell-o
${Notify_Client_Text}                    Notified client
${Show_Event_Log_Icon}                   css=.history-modal[title='Show event log']
${Edit_Client_Icon}                      css=[title='Edit file']
${Confirm_Edit_Client_Btn}               css=#updateButton
${Phone_Number_Field}                    css=#edit_client_phone
${Success_Message}                       css=.toast-message
${Address_Field}                         css=#edit_file_address
${Actual_Email_Address}                  css=[data-dt-column='3'] .table-responsive td:nth-child(1)
${Client_Phone_Number_Field}             css=.client-contact-phone .client-phone[placeholder='Enter phone number (optional)']

*** Test Cases ***

Scenario:To Verify that admin can access "pre close clients"
  Given Navigate to "buyerdocs.com"
    And Login with valid credentials
   When I navigate to "Pre close client"
   Then I should be on the "Pre close clients" page

Scenario:To Verify that admin can add a pre close client
  Given Navigate to "buyerdocs.com"
    And Login with valid credentials
    And I navigate to "Pre close client"
   When I add a new "pre close client"
   Then I should see "pre close client" being successfully added
#
Scenario:To Verify that admin can add a "pre close client" without "property address"
  Given Navigate to "buyerdocs.com"
    And Login with valid credentials
    And I navigate to "Pre close client"
   When I add a "pre close client" with optional "property address"
   Then I should see "pre close client" being successfully added

Scenario:To Verify that admin can add a "pre close client" without uploading documents
  Given Navigate to "buyerdocs.com"
    And Login with valid credentials
    And I navigate to "Pre close client"
   When I add a "pre close client" without uploading documents
   Then I should see "pre close client" being successfully added

Scenario:To Verify that admin can notify a "pre close client"
  Given Navigate to "buyerdocs.com"
    And Login with valid credentials
    And I navigate to "Pre close client"
   When I notify "pre close client"
   Then I should see "post close client" being notified

Scenario:To Verify that admin can see event logs for "post close client"
  Given Navigate to "buyerdocs.com"
    And Login with valid credentials
    And I navigate to "Pre close client"
   When I click show events log icon for "pre close client"
   Then I should see events log for "pre close client"

Scenario:To Verify that admin can edit a "pre close client"
  Given Navigate to "buyerdocs.com"
    And Login with valid credentials
    And I navigate to "Pre close client"
   When I click edit icon for "pre close client"
    And I add an address
   Then I should see the address added to the "pre close client"

Scenario: Search pre close clients by email address
  Given Navigate to "buyerdocs.com"
    And Login with valid credentials
    And I navigate to "Pre close client"
   When I search clients by email address
   Then I should see the corresponding search results of email addresses

Scenario: Search pre close clients by phone number
  Given Navigate to "buyerdocs.com"
    And Login with valid credentials
    And I navigate to "Pre close client"
   When I search clients by phone number
   Then I should see the corresponding search results of phone number

Scenario: Search pre close clients by reference number
  Given Navigate to "buyerdocs.com"
    And Login with valid credentials
    And I navigate to "Pre close client"
   When I search clients by reference number
   Then I should see the corresponding search results of the reference number

Scenario: Search pre close clients by company
  Given Navigate to "buyerdocs.com"
    And Login with valid credentials
    And I navigate to "Pre close client"
   When I search clients by company
   Then I should see the corresponding search result of the company

Scenario:To Verify that admin can delete a "pre close client"
  Given Navigate to "buyerdocs.com"
    And Login with valid credentials
    And I navigate to "Pre close client"
   When I delete the "pre close client"
   Then I should see "pre close client" being deleted

*** Keywords ***

#Given---------------------------------------------------------------------------

#When---------------------------------------------------------------------------
I navigate to "Pre close client"
   Wait Until Page Contains Element    ${Client_Button}
   Page Should Contain Element    ${Client_Button}

I add a new "pre close client"
   Wait Until Page Contains Element    ${Company_Dropdown}
   Click Element    ${Company_Dropdown}
   Sleep    1s
   Wait Until Page Contains Element    ${Select_Company}
   Click Element    ${Select_Company}
   Wait Until Page Contains Element    ${Branch_Dropdown}
   Sleep    1s
   Click Element    ${Branch_Dropdown}
   Sleep    1s
   Wait Until Page Contains Element    ${Select_Branch}
   Click Element    ${Select_Branch}
   Sleep    1s
   Click Element    ${Branch_Dropdown}
   Sleep    1s
   ${CurrentDate}=  Get Current Date  result_format=%Y-%m-%d%H
   Input Text    ${Reference_Number_Field}    ${Reference_Number} ${CurrentDate}
   Input Text    ${Client_Email_Field}        ${CurrentDate}${Client_Email_Address}
  # Input Text    ${Property_Address_Field}    ${Property_Address}${CurrentDate}
   Sleep    1s
   Input Text    ${Client_Phone_Number_Field}    5005550006
   Press Key   ${Add_Client_Button}    \\13
   Sleep    30s
   Wait Until Page Contains Element    ${Add_Client_Confirmation_dialogue}
   Wait Until Page Contains Element    ${Add_Client_Confirmation_Button}
   Wait Until Element Is Enabled       ${Add_Client_Confirmation_Button}
   Sleep    1s
   Click Element    ${Add_Client_Confirmation_Button}
  # Wait Until Page Does Not Contain Element    ${Add_Client_Confirmation_dialogue}

I add a "pre close client" with optional "property address"
   Wait Until Page Contains Element    ${Company_Dropdown}
   Click Element    ${Company_Dropdown}
   Sleep    1s
   Wait Until Page Contains Element    ${Select_Company}
   Click Element    ${Select_Company}
   Wait Until Page Contains Element    ${Branch_Dropdown}
   Sleep    1s
   Click Element    ${Branch_Dropdown}
   Sleep    1s
   Wait Until Page Contains Element    ${Select_Branch}
   Click Element    ${Select_Branch}
   Sleep    1s
   Click Element    ${Branch_Dropdown}
   Sleep    1s
   ${CurrentDate}=  Get Current Date  result_format=%Y-%m-%d%H
   Input Text    ${Reference_Number_Field}    ${Reference_Number} ${CurrentDate}
   Input Text    ${Client_Email_Field}        ${CurrentDate}${Client_Email_Address}
   Sleep    1s
   Input Text    ${Client_Phone_Number_Field}    5005550006
   Press Key   ${Add_Client_Button}    \\13
   Wait Until Page Contains Element    ${Add_Client_Confirmation_dialogue}
   Wait Until Page Contains Element    ${Add_Client_Confirmation_Button}
   Wait Until Element Is Enabled       ${Add_Client_Confirmation_Button}
   Sleep    1s
   Click Element    ${Add_Client_Confirmation_Button}

I add a "pre close client" without uploading documents
   Wait Until Page Contains Element    ${Company_Dropdown}
   Click Element    ${Company_Dropdown}
   Sleep    1s
   Wait Until Page Contains Element    ${Select_Company}
   Click Element    ${Select_Company}
   Wait Until Page Contains Element    ${Branch_Dropdown}
   Sleep    1s
   Click Element    ${Branch_Dropdown}
   Sleep    1s
   Wait Until Page Contains Element    ${Select_Branch}
   Click Element    ${Select_Branch}
   Sleep    1s
   Click Element    ${Branch_Dropdown}
   Sleep    1s
   ${CurrentDate}=  Get Current Date  result_format=%Y-%m-%d%H
   Input Text    ${Reference_Number_Field}    ${Reference_Number} ${CurrentDate}
   Input Text    ${Client_Email_Field}        ${CurrentDate}${Client_Email_Address}
   Sleep    1s
   Input Text    ${Client_Phone_Number_Field}    5005550006
   Press Key   ${Add_Client_Button}    \\13
   Wait Until Page Contains Element    ${Add_Client_Confirmation_dialogue}
   Wait Until Page Contains Element    ${Add_Client_Confirmation_Button}
   Wait Until Element Is Enabled       ${Add_Client_Confirmation_Button}
   Sleep    1s
   Click Element    ${Add_Client_Confirmation_Button}

I delete the "pre close client"
   Wait Until Page Contains Element    ${Delete_Client_Icon}
   Sleep    1s
   Wait Until Element Is Enabled    ${Delete_Client_Icon}
   Wait Until Element Is Visible    ${Delete_Client_Icon}
   Execute JavaScript  window.document.querySelector("[title='Delete file']").scrollIntoView()
   Set Focus To Element    ${Delete_Client_Icon}
   Press Key    ${Delete_Client_Icon}    \\13
   Sleep    1s
   Wait Until Page Contains Element    ${Confirm_Delete_Client_Button}
   Wait Until Element Is Enabled    ${Confirm_Delete_Client_Button}
   Wait Until Element Is Visible    ${Confirm_Delete_Client_Button}
   Click Element    ${Confirm_Delete_Client_Button}

I notify "pre close client"
   Wait Until Page Contains Element    ${Notify_Client_Icon}
   Sleep    1s
   Wait Until Element Is Enabled    ${Notify_Client_Icon}
   Wait Until Element Is Visible    ${Notify_Client_Icon}
   Set Focus To Element    ${Notify_Client_Icon}
   Press Key    ${Notify_Client_Icon}    \\13
   Sleep    1s
   Wait Until Page Contains Element    ${Confirm_Notify_Client_Button}
   Wait Until Element Is Enabled    ${Confirm_Notify_Client_Button}
   Wait Until Element Is Visible    ${Confirm_Notify_Client_Button}
   Click Element    ${Confirm_Notify_Client_Button}

I click show events log icon for "pre close client"
   Wait Until Page Contains Element    ${Show_Event_Log_Icon}
   Sleep    1s
   Wait Until Element Is Enabled    ${Show_Event_Log_Icon}
   Wait Until Element Is Visible    ${Show_Event_Log_Icon}
   Set Focus To Element    ${Show_Event_Log_Icon}
   Press Key    ${Show_Event_Log_Icon}   \\13
   Sleep    1s

I click edit icon for "pre close client"
   Wait Until Page Contains Element    ${Edit_Client_Icon}
   Sleep    1s
   Wait Until Element Is Enabled    ${Edit_Client_Icon}
   Wait Until Element Is Visible    ${Edit_Client_Icon}
   Set Focus To Element    ${Edit_Client_Icon}
   Press Key    ${Edit_Client_Icon}    \\13
   Sleep    1s

I add an address
   Wait Until Page Contains Element    ${Address_Field}
   Sleep    1s
   Wait Until Element Is Enabled     ${Address_Field}
   Wait Until Element Is Visible     ${Address_Field}
   Input Text     ${Address_Field}     my edited address
   Sleep    1s
   Set Focus To Element    ${Confirm_Edit_Client_Btn}
   Click Element    ${Confirm_Edit_Client_Btn}
   Sleep    1s

I search clients by email address
   Wait Until Page Contains Element    ${Search_Field}
   Input Text    ${Search_Field}    ${Search_Email}
   Sleep    1s

I search clients by phone number
   Wait Until Page Contains Element    ${Search_Field}
   Input Text    ${Search_Field}    TA-786
   Wait Until Page Contains Element    ${Email_Table}
   Sleep    3s
   Click Element    ${Email_Table}
   Sleep    1s

I search clients by reference number
   Wait Until Page Contains Element    ${Search_Field}
   Input Text    ${Search_Field}    ${Search_Reference_Number}
   Sleep    1s

I search clients by company
   Wait Until Page Contains Element    ${Search_Field}
   Input Text    ${Search_Field}    Title Name
   Sleep    1s


#Then---------------------------------------------------------------------------
I should be on the "Pre close clients" page
   Wait Until Page Contains Element    ${Client_Email_Field}
   Page Should Contain Element    ${Client_Email_Field}
   Location Should Be     ${Pre_Close_URL}

I should see "pre close client" being successfully added
   Wait Until Page Contains    Success!
   Page Should Contain    Success!
   Wait Until Element Is Not Visible    ${Success_Message}
   Element Should Not Be Visible    ${Success_Message}
   sleep  3s

I should see "pre close client" being deleted
   Wait Until Page Contains    Success!
   Page Should Contain    Success!
   Wait Until Element Is Not Visible    ${Success_Message}
   Element Should Not Be Visible    ${Success_Message}


I should see "post close client" being notified
   Wait Until Page Contains    Success!
   Page Should Contain    Success!
   Wait Until Element Is Not Visible    ${Success_Message}
   Element Should Not Be Visible    ${Success_Message}

I should see events log for "pre close client"
   Wait Until Page Contains    Event log
   Page Should Contain    Event log

I should see the address added to the "pre close client"
   Wait Until Page Contains    Success!
   Wait Until Element Is Not Visible    ${Success_Message}
   Wait Until Page Contains    my edited address
   Page Should Contain    	my edited address

I should see the corresponding search results of email addresses
   Wait Until Page Contains Element    ${Email_Table}
   Sleep    1s
   Click Element    ${Email_Table}
   Sleep    1s
   Wait Until Page Contains    buyer@test.test
   Page Should Contain    buyer@test.test
   Element Text Should Be    ${Actual_Email_Address}     buyer@test.test

I should see the corresponding search results of phone number
   Wait Until Page Contains    taimur.aamer@yahoo.com
   Page Should Contain    taimur.aamer@yahoo.com
   Element Text Should Be    ${Phone_Table}    ${Search_Phone}

I should see the corresponding search results of the reference number
   Sleep    2s
   Wait Until Element Is Visible    ${Reference_Number_Table}
   Wait Until Page Contains   ${Search_Reference_Number}
   Page Should Contain    ${Search_Reference_Number}

I should see the corresponding search result of the company
   Wait Until Element Is Visible    ${Search_Company}
   Sleep    3s
   Wait Until Page Contains   	Title Name
   Page Should Contain    	Title Name
   Element Text Should Be    ${Search_Company}   	Title Name
