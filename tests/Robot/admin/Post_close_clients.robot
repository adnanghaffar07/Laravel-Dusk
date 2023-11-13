*** Settings ***
Library           SeleniumLibrary  timeout=20
Suite Teardown    Close All Browsers
Test Teardown     Close All Browsers
Resource          ../Resources/keywords.robot
Library           robot.libraries.DateTime

*** Variables ***
#POM------
${Post_Close_Button}                     css=.nav-align [href='${SERVER}user/postclose']
${Post_Close_URL}                        ${SERVER}user/postclose
${Client_Email_Field}                    css=#email[placeholder='Enter client email']
${Property_Address_Field}                css=#locationField #address
${Reference_Number_Field}                css=#refnum
${Add_Client_Button}                     css=button#openAddModal.add-modal.btn.btn-brand.btn-circle[type='button']
${Upload_Document}                       xpath=//input[@type='file']
${Company_Dropdown}                      css=#addClientForm #select-company
${Select_Company}                        xpath=//*[@id="select-company"]/option[2]
${Branch_Dropdown}                       id=select-branch
${Select_Branch}                         xpath=//*[@id="select-branch"]/option[2]
${Client_Email_Address}                  taimur.aamer@yahoo.com
${Property_Address}                      House 13, Street 10, Sector k-4, Austin
${Reference_Number}                      TA-786
${Add_Client_Confirmation_dialogue}      css=#addModal .modal-body
${Add_Client_Confirmation_Button}        css=#addButton
${Search_Field}                          css=#clientTable_filter [type='search']
${Search_Element}                        css=#clientTable tbody tr td:nth-child(1)
${Search_Property}                       css=#clientTable tbody tr td:nth-child(2)
${Search_Reference_Number}               css=#clientTable tbody tr td:nth-child(3)
${Search_Company}                        css=#clientTable tbody tr td:nth-child(4)
${Search_Empty}                          css=#clientTable tbody tr td
${Success_Message}                       css=.toast-message


${Delete_Client_Icon}                   css=#clientTable [title='Delete client']
${Confirm_Delete_Client_Button}         css=#deleteButton
${Remove_Client_File_Text}              Removed client file
${Notify_Client_Icon}                   css=#clientTable [title='Send notification(s)']
${Confirm_Notify_Client_Button}         css=#notifyButton
${Notify_Client_Text}                   Notified client
${Show_Event_Log_Icon}                  css=#clientTable .history-modal[title='Show event log']
${Event_Logs_Dialog}                    css=#historyModal h3
${Event_Log_Close_Button}               css=#historyModal button.btn.btn-gray.btn-circle.cancel[type='button'][data-dismiss='modal']
${Street_View_Icon}                     css=#clientTable .streetview-modal[title='Show street view']
${Street_View_Dialog}                   css=#streetViewModal div.modal-header h3.modal-title
${Street_View_Close_Button}             css=#streetViewModal button.btn-default.btn[data-dismiss='modal'][type='button']


*** Test Cases ***

Scenario:To Verify that admin cannot add a post close client without email
  Given Navigate to "buyerdocs.com"
    And Login with valid credentials
    And I navigate to "Post close client"
   When I try to add a "post close client" without email
   Then I should see an error about the missing email

Scenario:To Verify that admin cannot add a post close client without reference
  Given Navigate to "buyerdocs.com"
    And Login with valid credentials
    And I navigate to "Post close client"
   When I try to add a "post close client" without "reference file number"
   Then I should see an error about the missing "reference file number"

Scenario:To Verify that admin can add a "post close client" without "property address"
  Given Navigate to "buyerdocs.com"
    And Login with valid credentials
    And I navigate to "Post close client"
   When I add a "post close client" with optional "property address"
   Then I should see "post close client" being successfully added

Scenario:To Verify that admin can add a "post close client" without uploading documents
  Given Navigate to "buyerdocs.com"
    And Login with valid credentials
    And I navigate to "Post close client"
   When I add a "post close client" without uploading documents
   Then I should see "post close client" being successfully added

Scenario:To Verify that admin can delete a "post close client"
  Given Navigate to "buyerdocs.com"
    And Login with valid credentials
    And I navigate to "Post close client"
   When I click delete icon for "post close client"
   Then I should see "post close client" being deleted

Scenario:To Verify that admin can notify a "post close client"
  Given Navigate to "buyerdocs.com"
    And Login with valid credentials
    And I navigate to "Post close client"
   When I click notify icon for "post close client"
   Then I should see "post close client" being notified

Scenario:To Verify that admin can see event logs for "post close client"
  Given Navigate to "buyerdocs.com"
    And Login with valid credentials
    And I navigate to "Post close client"
   When I click show events log icon for "post close client"
   Then I should see events log for "post close client"

# Scenario:To Verify that admin can see street view for "post close client"
#    Given Navigate to "buyerdocs.com"
#      And Login with valid credentials
#      And I navigate to "Post close client"
#     When I click show street view icon for "post close client"
#     Then I should see street view for "post close client"

Scenario:To Verify that user can access "post close client" from top menu
   Given Navigate to "buyerdocs.com"
     And Login with valid credentials
    When I navigate to "Post close client"
    Then I should see "post close client" page

Scenario:To Verify that admin can add a post close client
  Given Navigate to "buyerdocs.com"
    And Login with valid credentials
    And I navigate to "Post close client"
   When I add a post close client
   Then I should see "post close client" being added

Scenario: Search post close clients by email address
   Given Navigate to "buyerdocs.com"
     And Login with valid credentials
     And I navigate to "Post close client"
    When I search clients by email address
    Then I should see the corresponding search results of email addresses

Scenario: Search post close clients by property address
   Given Navigate to "buyerdocs.com"
     And Login with valid credentials
     And I navigate to "Post close client"
    When I search clients by property address
    Then I should see the corresponding search results of property addresses

Scenario: Search post close clients by reference number
   Given Navigate to "buyerdocs.com"
     And Login with valid credentials
     And I navigate to "Post close client"
    When I search clients by reference number
    Then I should see the corresponding search results of the reference numbers

Scenario: Search post close clients by branch
   Given Navigate to "buyerdocs.com"
     And Login with valid credentials
     And I navigate to "Post close client"
    When I search clients by branch
    Then I should see the corresponding search results of the branches

Scenario: Search should return no results for non existant post close clients
   Given Navigate to "buyerdocs.com"
     And Login with valid credentials
     And I navigate to "Post close client"
    When I search for non existant clents
    Then I should see no results

*** Keywords ***

#Given---------------------------------------------------------------------------

#When---------------------------------------------------------------------------

I try to add a "post close client" without email
  Sleep    1s
  Wait Until Page Contains Element    ${Add_Client_Button}
  Sleep    1s
  Page Should Contain Element    ${Add_Client_Button}
  Sleep    1s
  Input Text    ${Reference_Number_Field}    ${Reference_Number}
  Sleep    2s
  Wait Until Page Contains Element    ${Company_Dropdown}
  Click Element    ${Company_Dropdown}
  Sleep    0.5S
  Wait Until Page Contains Element    ${Select_Company}
  Click Element    ${Select_Company}
  Sleep    2s
  Wait Until Page Contains Element    ${Branch_Dropdown}
  Wait Until Element is Enabled   ${Branch_Dropdown}
  Set Focus To Element    ${Branch_Dropdown}
  Execute JavaScript  window.document.querySelector("#addClientForm #select-company").scrollIntoView()
  Execute JavaScript  window.document.querySelector("#addClientForm #select-branch").focus()
  Sleep  2s
  Click Element  ${Branch_Dropdown}
  Wait Until Page Contains Element    ${Select_Branch}
  sleep   2s
  Click Element  ${Select_Branch}
  Wait Until Page Contains Element    ${Add_Client_Button}
  Wait Until Element Is Enabled    ${Add_Client_Button}
  Set Focus To Element    ${Add_Client_Button}
  Wait Until Element Is Visible    ${Add_Client_Button}
  Sleep    1.5s
  Click Button   Add Client
  Sleep    1s

I try to add a "post close client" without "reference file number"
  Sleep    1s
  Wait Until Page Contains Element    ${Add_Client_Button}
  Sleep    1s
  Page Should Contain Element    ${Add_Client_Button}
  Sleep    2s
  Wait Until Page Contains Element    ${Company_Dropdown}
  Click Element    ${Company_Dropdown}
  Sleep    0.5S
  Wait Until Page Contains Element    ${Select_Company}
  Click Element    ${Select_Company}
  Sleep    2s
  Wait Until Page Contains Element    ${Branch_Dropdown}
  Wait Until Element is Enabled   ${Branch_Dropdown}
  Set Focus To Element    ${Branch_Dropdown}
  Execute JavaScript  window.document.querySelector("#addClientForm #select-company").scrollIntoView()
  Execute JavaScript  window.document.querySelector("#addClientForm #select-branch").focus()
  Sleep  2s
  Click Element  ${Branch_Dropdown}
  Wait Until Page Contains Element    ${Select_Branch}
  sleep   2s
  Click Element  ${Select_Branch}
  Wait Until Page Contains Element    ${Add_Client_Button}
  Wait Until Element Is Enabled    ${Add_Client_Button}
  Set Focus To Element    ${Add_Client_Button}
  Wait Until Element Is Visible    ${Add_Client_Button}
  Sleep    1s
  Click Button   Add Client
  Sleep    1s

I add a "post close client" with optional "property address"
  Sleep  1s
  Wait Until Page Contains Element    ${Add_Client_Button}
  Sleep  1s
  Page Should Contain Element    ${Add_Client_Button}
  Sleep  1s
  ${CurrentDate}=  Get Current Date  result_format=%Y-%m-%d%H
  Input Text    ${Client_Email_Field}    ${CurrentDate}${Client_Email_Address}
  Input Text    ${Reference_Number_Field}    ${Reference_Number}
  Sleep  2s
  Wait Until Page Contains Element    ${Company_Dropdown}
  Click Element    ${Company_Dropdown}
  Sleep    0.5S
  Wait Until Page Contains Element    ${Select_Company} 
  Click Element    ${Select_Company} 
  Sleep  2s
  Click Element    ${Company_Dropdown}
  Wait Until Page Contains Element    ${Branch_Dropdown}
  Wait Until Element is Enabled   ${Branch_Dropdown}
  Set Focus To Element    ${Branch_Dropdown}
  Execute JavaScript  window.document.querySelector("#addClientForm #select-company").scrollIntoView()
  Execute JavaScript  window.document.querySelector("#addClientForm #select-branch").focus()
  Sleep  2s
  Click Element  ${Branch_Dropdown}
  Wait Until Page Contains Element    ${Select_Branch}
  sleep   2s
  Click Element  ${Select_Branch}
  Wait Until Page Contains Element    ${Add_Client_Button}
  Wait Until Element Is Enabled    ${Add_Client_Button}
  Set Focus To Element    ${Add_Client_Button}
  Wait Until Element Is Visible    ${Add_Client_Button}
  Sleep    1.5s
  Click Button   Add Client
  Wait Until Page Contains Element    ${Add_Client_Confirmation_dialogue}
  Wait Until Page Contains Element    ${Add_Client_Confirmation_Button}
  Sleep    1s
  Click Element    ${Add_Client_Confirmation_Button}
  Sleep    1s

I add a "post close client" without uploading documents
  Sleep  1s
  Wait Until Page Contains Element    ${Add_Client_Button}
  Sleep  1s
  Page Should Contain Element    ${Add_Client_Button}
  Sleep  1s
  ${CurrentDate}=  Get Current Date  result_format=%Y-%m-%d%H
  Input Text    ${Client_Email_Field}    ${CurrentDate}${Client_Email_Address}
  Input Text    ${Property_Address_Field}   ${Property_Address}`
  Input Text    ${Reference_Number_Field}    ${Reference_Number}
  Sleep  2s
  Wait Until Page Contains Element    ${Company_Dropdown}
  Click Element    ${Company_Dropdown}
  Sleep    0.5S
  Wait Until Page Contains Element    ${Select_Company}
  Click Element    ${Select_Company}
  Sleep  2s
  Wait Until Page Contains Element    ${Branch_Dropdown}
  Wait Until Element is Enabled   ${Branch_Dropdown}
  Set Focus To Element    ${Branch_Dropdown}
  Execute JavaScript  window.document.querySelector("#addClientForm #select-company").scrollIntoView()
  Execute JavaScript  window.document.querySelector("#addClientForm #select-branch").focus()
  Sleep  2s
  Click Element  ${Branch_Dropdown}
  Wait Until Page Contains Element    ${Select_Branch}
  sleep   2s
  Click Element  ${Select_Branch}
  Wait Until Page Contains Element    ${Add_Client_Button}
  Wait Until Element Is Enabled    ${Add_Client_Button}
  Set Focus To Element    ${Add_Client_Button}
  Wait Until Element Is Visible    ${Add_Client_Button}
  Sleep    1.5s
  Click Button   Add Client
  Wait Until Page Contains Element    ${Add_Client_Confirmation_dialogue}
  Wait Until Page Contains Element    ${Add_Client_Confirmation_Button}
  Sleep    1s
  Click Element    ${Add_Client_Confirmation_Button}
  Sleep    1s

I click delete icon for "post close client"
  Wait Until Page Contains Element    ${Delete_Client_Icon}
  Sleep  2s
  Wait Until Element Is Enabled    ${Delete_Client_Icon}
  Wait Until Element Is Visible    ${Delete_Client_Icon}
  Execute JavaScript  window.document.querySelector("#clientTable [title='Delete client']").scrollIntoView()
  Set Focus To Element    ${Delete_Client_Icon}
  Press Key    ${Delete_Client_Icon}    \\13
  Wait Until Page Contains Element  ${Confirm_Delete_Client_Button}
  Sleep  1s
  Page Should Contain Element  ${Confirm_Delete_Client_Button}
  Click Element  ${Confirm_Delete_Client_Button}
  Sleep  1s

I click notify icon for "post close client"
  Wait Until Page Contains Element    ${Notify_Client_Icon}
  Page Should Contain Element  ${Notify_Client_Icon}
  Wait Until Element Is Enabled  ${Notify_Client_Icon}
  Set Focus To Element    ${Notify_Client_Icon}
  Wait Until Element Is Visible    ${Notify_Client_Icon}
  Press Key    ${Notify_Client_Icon}   \\13
  Wait Until Page Contains Element  ${Confirm_Notify_Client_Button}
  Sleep  1s
  Page Should Contain Element  ${Confirm_Notify_Client_Button}
  Sleep  2s
  Click Element  ${Confirm_Notify_Client_Button}
  Sleep  1s

I click show events log icon for "post close client"
  Wait Until Page Contains Element    ${Show_Event_Log_Icon}
  Page Should Contain Element  ${Show_Event_Log_Icon}
  Wait Until Element Is Enabled  ${Show_Event_Log_Icon}
  Set Focus To Element    ${Show_Event_Log_Icon}
  Wait Until Element Is Visible    ${Show_Event_Log_Icon}
  Press Key    ${Show_Event_Log_Icon}    \\13
  Wait Until Page Contains Element  ${Event_Logs_Dialog}

I click show street view icon for "post close client"
  Wait Until Page Contains Element    ${Street_View_Icon}
  Page Should Contain Element  ${Street_View_Icon}
  Wait Until Element Is Enabled  ${Street_View_Icon}
  Set Focus To Element    ${Street_View_Icon}
  Wait Until Element Is Visible    ${Street_View_Icon}
  Press Key    ${Street_View_Icon}   \\13
  Wait Until Page Contains Element  ${Street_View_Dialog}

I navigate to "Post close client"
  Wait Until Page Contains Element    ${Client_Button}
  Sleep    0.5s
  Page Should Contain Element    ${Client_Button}
  Sleep    0.5s
  Mouse Over    ${Client_Button}
#  Sleep    0.5s
  Wait Until Page Contains Element    ${Post_Close_Button}
  Sleep    0.5s
  Click Element    ${Post_Close_Button}
  #Handle Alert

I add a post close client
  Wait Until Page Contains Element    ${Client_Email_Field}
  Sleep    2s
  Input Text    ${Client_Email_Field}    ${Client_Email_Address}
  Input Text    ${Property_Address_Field}   ${Property_Address}
  Input Text    ${Reference_Number_Field}    ${Reference_Number}
  Wait Until Page Contains Element    ${Company_Dropdown}
  Click Element    ${Company_Dropdown}
  Sleep    0.5S
  Wait Until Page Contains Element    ${Select_Company}
  Click Element    ${Select_Company}
  Wait Until Page Contains Element    ${Branch_Dropdown}
  Wait Until Element is Enabled   ${Branch_Dropdown}
  Set Focus To Element    ${Branch_Dropdown}
  Execute JavaScript  window.document.querySelector("#addClientForm #select-company").scrollIntoView()
  Execute JavaScript  window.document.querySelector("#addClientForm #select-branch").focus()
  Sleep  2s
  Click Element  ${Branch_Dropdown}
  Wait Until Page Contains Element    ${Select_Branch}
  sleep   2s
  Click Element  ${Select_Branch}
  # //choose file   ${Upload_Document}  ${CURDIR}/1.jpg
  Sleep  2s
  Wait Until Page Contains Element    ${Add_Client_Button}
  Wait Until Element Is Enabled    ${Add_Client_Button}
  Set Focus To Element    ${Add_Client_Button}
  Wait Until Element Is Visible    ${Add_Client_Button}
  Sleep    0.5s
  Click Button   Add Client
  Wait Until Page Contains Element    ${Add_Client_Confirmation_dialogue}
  Wait Until Page Contains Element    ${Add_Client_Confirmation_Button}
  Sleep    1s
  Click Element    ${Add_Client_Confirmation_Button}
  Sleep    3s

I search clients by email address
  Wait Until Page Contains Element    ${Search_Field}
  Input Text    ${Search_Field}    frami.murray@mosciski.com

I search clients by property address
  Wait Until Page Contains Element    ${Search_Field}
  Input Text    ${Search_Field}    	4747 Noma Drive

I search clients by reference number
  Wait Until Page Contains Element    ${Search_Field}
  Input Text    ${Search_Field}    	AUS-57819

I search clients by branch
  Wait Until Page Contains Element    ${Search_Field}
  Input Text    ${Search_Field}    	Austin

I search for non existant clents
  Wait Until Page Contains Element    ${Search_Field}
  Input Text    ${Search_Field}    	mr grey


#Then---------------------------------------------------------------------------

I should see an error about the missing email
  Page Should Contain  Please specify an email address.

I should see an error about the missing "reference file number"
  Page Should Contain  Please specify a reference file number.

I should see "post close client" being successfully added
  Sleep  1s
  Wait Until Page Contains    Success!
  Page Should Contain    Success!
  Wait Until Element Is Not Visible    ${Success_Message}
  Element Should Not Be Visible    ${Success_Message}

I should see "post close client" being deleted
  Page Should Contain  ${Remove_Client_File_Text}

I should see "post close client" being notified
  Page Should Contain  ${Notify_Client_Text}

I should see events log for "post close client"
  Sleep  1s
  Page Should Contain Element  ${Event_Logs_Dialog}
  Sleep  2s
  Click Element  ${Event_Log_Close_Button}
  Sleep  1s
  Wait Until Page Contains    Add Post-Close Clients
  Page Should Contain    Add Post-Close Clients

I should see street view for "post close client"
  Sleep  1s
  Page Should Contain Element  ${Street_View_Dialog}
  Sleep  2s
  Click Element  ${Street_View_Close_Button}
  Sleep  1s
  Wait Until Page Contains    Add Post-Close Clients
  Page Should Contain    Add Post-Close Clients


I should see "post close client" page
  Wait Until Page Contains    Add Post-Close Clients
  Page Should Contain    Add Post-Close Clients
  Location Should Be     ${Post_Close_URL}

I should see "post close client" being added
  Wait Until Page Contains   Success!
  Page should contain   Success!

I should see the corresponding search results of email addresses
  Sleep    1s
  Element Text Should Be    ${Search_Element}    No matching records found
  Wait until page contains  No matching records found
  Page Should Contain    No matching records found


I should see the corresponding search results of property addresses
  Sleep    2s
  Wait until page contains  No matching records found
  Page Should Contain    No matching records found

I should see the corresponding search results of the reference numbers
  Sleep    1s
  Wait until page contains  No matching records found
  Page Should Contain    No matching records found

I should see the corresponding search results of the branches
  Sleep    1s
  Wait until page contains  Austin
  Page Should Contain    Austin


I should see no results
  Sleep    1s
  Element Text Should Be    ${Search_Empty}    No matching records found
  Wait until page contains  No matching records found
  Page Should Contain    No matching records found
