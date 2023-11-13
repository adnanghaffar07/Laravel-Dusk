*** Settings ***
Library           SeleniumLibrary  timeout=20
Suite Teardown    Close All Browsers
Test Teardown     Close All Browsers
Resource          ../Resources/keywords.robot

*** Variables ***
${Login_Button_Buyer}               id=checkforPasscode
${Login_Email_Buyer}                css=#email[type='email']
${Company_Dropdown_Buyer}           css=#title_id
${Agreement_Checkbox_Buyer}         css=.custom-checkbox
${Select_Buyer_Comapny}             xpath=//*[@id="title_id"]/option[2]
${Select_Buyer_Comapny_2}           xpath=//*[@id="title_id"]/option[3]
${Unregistered_Buyer_Email}         unregistered@user.com
${Registered_Buyer_Email}           taimur.aamer@yahoo.com
${Valid_Token}                      106817698
${Invalid_Token}                    123456
${Company_Encircled_Red}            css=[onsubmit='return false;']
${Invalid_Buyer_Email}              taimur.com
${Email_Encircled_Red}              css=[onsubmit='return false;']
${Agreement_Encircled_Red}          css=[onsubmit='return false;']
${Select_Phone_Type}                css=#selectPhoneTypeModal .modal-content
${Terms_Of_Services_Link}           css=[href='#termsModal']
${TOS_Modal}                        css=#termsModal .modal-header
${Close_TOS_Popup_Button_Cross}     css=.close[data-dismiss='modal']
${Close_TOS_Popup_Button_Cancel}    css=
${Notification_Popup}               css=#selectPhoneTypeModal .modal-content
${Already_Have_Passcode}            css=[href='javascript:void(0);']
${Passcode_Field}                   css=#passcode
${Submit_Passcode_Button}           css=#submitPasscode
${Fruad_Warning_Message}            css=#warnModal .modal-dialog .container-fluid
${Accept_Warning}                   css=#submitBuyerLogin #submitBuyerLoginText
${Logout_Button}                    css=[href='${BUYER_PORTAL_URL}/logout']
${Cancel_Passcode_Button}           css=#submitPasscodeCancel


*** Test Cases ***

Scenario:To Verify that user can access buyers portal
   Given Navigate to buyers portal
    Then I should be on the buyers portal

Scenario: Verify the presense of login field on the buyers portal page
   Given Navigate to buyers portal
    Then I should see the login input field

Scenario: Verify the presense of compnay dropdown on the buyers portal page
   Given Navigate to buyers portal
    Then I should see the compnay selection dropdown

Scenario: Verify the presence of agreement checkbox on the buyers portal page
   Given Navigate to buyers portal
    Then I should see the agreement checkbox

Scenario: Verify the presence of Login button on the buyers portal page
   Given Navigate to buyers portal
    Then I should see the login button

Scenario: Verify that the user cannot login with unregistered email
   Given Navigate to buyers portal
    When I login with unregistered email
    Then I should not be able to login and an error should be shown

Scenario: Verify that the user cannot login without selecting a company from the dropdown
   Given Navigate to buyers portal
    When I login without selecting a company
    Then I should not be able to login and the company field should be encircled red

Scenario: Verify that the user cannot login with invalid email
   Given Navigate to buyers portal
    When I login with invalid email
    Then I should not be able to login and the email field should be encircled red

Scenario: Verify that the user cannot login without agreeing the terms of service
   Given Navigate to buyers portal
    When I login without agreeing to the terms of service
    Then I should not be able to agreement and the email field should be encircled red

Scenario: Verify that the user can login by click login button
   Given Navigate to buyers portal
    When I enter valid credentials and click login button
    Then I should be able to proceed with login steps

Scenario: Verify that the user can login by pressing enter/return
   Given Navigate to buyers portal
    When I enter valid credentials and press enter
    Then I should be able to proceed with login steps

Scenario: Verify that the user can open terms of service link
   Given Navigate to buyers portal
    When I click on terms of service link
    Then I should the terms of service popup

Scenario: Verify that the user can close the terms of service (TOS) popup by clicking the cross icon
   Given Navigate to buyers portal
    When I click the cross icon on TOS popup
    Then I should see the TOS popup being closed

Scenario: Verify that the user can close the terms of service (TOS) popup by clicking the close button
   Given Navigate to buyers portal
    When I click the close button on TOS popup
    Then I should see the TOS popup being closed

Scenario: Verify that the user cannot login to unassociated company
   Given Navigate to buyers portal
    When I login to unassociated company
    Then I should not be able to login and an error should be shown

Scenario: Verify that the user can see the notification popup
   Given Navigate to buyers portal
    When I access the notifcation popup
    Then I should see the notifcation popup

Scenario: Verify that the user can use the already assigned passcode
    Given Navigate to buyers portal
     When I login with the already assigned passcode
     Then I should be able to login

Scenario: Verify that the user can access the passcode popup by clicking the link
    Given Navigate to buyers portal
     When I click the already have passcode link
     Then I should see the passcode popup

Scenario: Verify that the user can see the passcode field on notification popup
    Given Navigate to buyers portal
     When I click the already have passcode link
     Then I should see the passcode field

Scenario: Verify that the user cannot login with invalid passcode
    Given Navigate to buyers portal
     When I login with invalid passcode
     Then I should see an error message and I should not login

Scenario: Verify that the user can login with valid passcode
    Given Navigate to buyers portal
     When I login with valid passcode
     Then I should be able to login

Scenario: Verify that the user can login by press enter on submit button
    Given Navigate to buyers portal
     When I login with valid passcode by pressing enter on submit button
     Then I should be able to login

Scenario: Verify that the user cannot login by cliking cancel button
    Given Navigate to buyers portal
     When I login with valid passcode by pressing cancel button
     Then I should be returned to the login page


*** Keywords ***

#Given---------------------------------------------------------------------------

#When---------------------------------------------------------------------------
I login with unregistered email
  Wait Until Page Contains Element    ${Login_Button_Buyer}
  Page Should Contain Element    ${Login_Button_Buyer}
  Location Should Be     ${BUYER_PORTAL_URL}
  Input Text    ${Login_Email_Buyer}    ${Unregistered_Buyer_Email}
  Sleep    1s
  Click Element    ${Company_Dropdown_Buyer}
  Wait Until Page Contains Element    ${Select_Buyer_Comapny}
  Sleep    1s
  Click Element    ${Select_Buyer_Comapny}
  Click Element    ${Agreement_Checkbox_Buyer}
  Sleep    0.5s
  Click Element    ${Login_Button_Buyer}

I login without selecting a company
  Wait Until Page Contains Element    ${Login_Button_Buyer}
  Page Should Contain Element    ${Login_Button_Buyer}
  Location Should Be     ${BUYER_PORTAL_URL}
  Input Text    ${Login_Email_Buyer}    ${Registered_Buyer_Email}
  Sleep    0.5s
  Click Element    ${Agreement_Checkbox_Buyer}
  Sleep    0.5s
  Click Element    ${Login_Button_Buyer}

I login with invalid email
  Wait Until Page Contains Element    ${Login_Button_Buyer}
  Page Should Contain Element    ${Login_Button_Buyer}
  Location Should Be     ${BUYER_PORTAL_URL}
  Input Text    ${Login_Email_Buyer}    ${Invalid_Buyer_Email}
  Sleep    1s
  Click Element    ${Company_Dropdown_Buyer}
  Wait Until Page Contains Element    ${Select_Buyer_Comapny}
  Sleep    1s
  Click Element    ${Select_Buyer_Comapny}
  Click Element    ${Agreement_Checkbox_Buyer}
  Sleep    0.5s
  Click Element    ${Login_Button_Buyer}

I login without agreeing to the terms of service
  Wait Until Page Contains Element    ${Login_Button_Buyer}
  Page Should Contain Element    ${Login_Button_Buyer}
  Location Should Be     ${BUYER_PORTAL_URL}
  Input Text    ${Login_Email_Buyer}    ${Registered_Buyer_Email}
  Sleep    1s
  Click Element    ${Company_Dropdown_Buyer}
  Wait Until Page Contains Element    ${Select_Buyer_Comapny}
  Sleep    1s
  Click Element    ${Select_Buyer_Comapny}
  Sleep    0.5s
  Click Element    ${Login_Button_Buyer}

I enter valid credentials and click login button
  Wait Until Page Contains Element    ${Login_Button_Buyer}
  Page Should Contain Element    ${Login_Button_Buyer}
  Location Should Be     ${BUYER_PORTAL_URL}
  Input Text    ${Login_Email_Buyer}    ${Registered_Buyer_Email}
  Sleep    1s
  Click Element    ${Company_Dropdown_Buyer}
  Wait Until Page Contains Element    ${Select_Buyer_Comapny}
  Sleep    1s
  Click Element    ${Select_Buyer_Comapny}
  Click Element    ${Agreement_Checkbox_Buyer}
  Sleep    0.5s
  Click Element    ${Login_Button_Buyer}

I enter valid credentials and press enter
  Wait Until Page Contains Element    ${Login_Button_Buyer}
  Page Should Contain Element    ${Login_Button_Buyer}
  Location Should Be     ${BUYER_PORTAL_URL}
  Input Text    ${Login_Email_Buyer}    ${Registered_Buyer_Email}
  Sleep    1s
  Click Element    ${Company_Dropdown_Buyer}
  Wait Until Page Contains Element    ${Select_Buyer_Comapny}
  Sleep    1s
  Click Element    ${Select_Buyer_Comapny}
  Click Element    ${Agreement_Checkbox_Buyer}
  Sleep    0.5s
  Set Focus To Element    ${Login_Button_Buyer}
  Execute JavaScript  window.document.querySelector("#checkforPasscode").scrollIntoView()
  Execute JavaScript  window.document.querySelector("#checkforPasscode").focus()
  Press Key    ${Login_Button_Buyer}   \\13

I click on terms of service link
  Wait Until Page Contains Element    ${Login_Button_Buyer}
  Page Should Contain Element    ${Login_Button_Buyer}
  Location Should Be     ${BUYER_PORTAL_URL}
  Wait Until Page Contains Element    ${Login_Email_Buyer}
  Page Should Contain Element    ${Login_Email_Buyer}
  Wait Until Page Contains Element    ${Company_Dropdown_Buyer}
  Page Should Contain Element    ${Company_Dropdown_Buyer}
  Wait Until Page Contains Element    ${Agreement_Checkbox_Buyer}
  Page Should Contain Element    ${Agreement_Checkbox_Buyer}
  Page Should Contain    I agree to the
  Click Element     ${Terms_Of_Services_Link}

I click the cross icon on TOS popup
  Wait Until Page Contains Element    ${Login_Button_Buyer}
  Page Should Contain Element    ${Login_Button_Buyer}
  Location Should Be     ${BUYER_PORTAL_URL}
  Wait Until Page Contains Element    ${Login_Email_Buyer}
  Page Should Contain Element    ${Login_Email_Buyer}
  Wait Until Page Contains Element    ${Company_Dropdown_Buyer}
  Page Should Contain Element    ${Company_Dropdown_Buyer}
  Wait Until Page Contains Element    ${Agreement_Checkbox_Buyer}
  Page Should Contain Element    ${Agreement_Checkbox_Buyer}
  Page Should Contain    I agree to the
  Click Element     ${Terms_Of_Services_Link}
  Wait Until Page Contains Element    ${TOS_Modal}
  Page Should Contain Element    ${TOS_Modal}
  Wait Until Element Is Enabled    ${Close_TOS_Popup_Button_Cross}
  Wait Until Element Is Visible    ${Close_TOS_Popup_Button_Cross}
  Wait Until Page Contains Element    ${Close_TOS_Popup_Button_Cross}
  Sleep    2s
  Click Element    ${Close_TOS_Popup_Button_Cross}

I click the close button on TOS popup
  Wait Until Page Contains Element    ${Login_Button_Buyer}
  Page Should Contain Element    ${Login_Button_Buyer}
  Location Should Be     ${BUYER_PORTAL_URL}
  Wait Until Page Contains Element    ${Login_Email_Buyer}
  Page Should Contain Element    ${Login_Email_Buyer}
  Wait Until Page Contains Element    ${Company_Dropdown_Buyer}
  Page Should Contain Element    ${Company_Dropdown_Buyer}
  Wait Until Page Contains Element    ${Agreement_Checkbox_Buyer}
  Page Should Contain Element    ${Agreement_Checkbox_Buyer}
  Page Should Contain    I agree to the
  Click Element     ${Terms_Of_Services_Link}
  Wait Until Page Contains Element    ${TOS_Modal}
  Page Should Contain Element    ${TOS_Modal}
  Wait Until Element Is Enabled    ${Close_TOS_Popup_Button_Cross}
  Wait Until Element Is Visible    ${Close_TOS_Popup_Button_Cross}
  Wait Until Page Contains Element    ${Close_TOS_Popup_Button_Cross}
  Sleep    2s
  Click Button    Close

I login to unassociated company
  Wait Until Page Contains Element    ${Login_Button_Buyer}
  Page Should Contain Element    ${Login_Button_Buyer}
  Location Should Be     ${BUYER_PORTAL_URL}
  Input Text    ${Login_Email_Buyer}    ${Registered_Buyer_Email}
  Sleep    1s
  Click Element    ${Company_Dropdown_Buyer}
  Wait Until Page Contains Element    ${Select_Buyer_Comapny_2}
  Sleep    1s
  Click Element    ${Select_Buyer_Comapny}
  Click Element    ${Agreement_Checkbox_Buyer}
  Sleep    0.5s
  Click Element    ${Login_Button_Buyer}

I access the notifcation popup
  Wait Until Page Contains Element    ${Login_Button_Buyer}
  Page Should Contain Element    ${Login_Button_Buyer}
  Location Should Be     ${BUYER_PORTAL_URL}
  Input Text    ${Login_Email_Buyer}    ${Registered_Buyer_Email}
  Sleep    1s
  Click Element    ${Company_Dropdown_Buyer}
  Wait Until Page Contains Element    ${Select_Buyer_Comapny}
  Sleep    1s
  Click Element    ${Select_Buyer_Comapny}
  Click Element    ${Agreement_Checkbox_Buyer}
  Sleep    0.5s
  Set Focus To Element    ${Login_Button_Buyer}
  Execute JavaScript  window.document.querySelector("#checkforPasscode").scrollIntoView()
  Execute JavaScript  window.document.querySelector("#checkforPasscode").focus()
  Press Key    ${Login_Button_Buyer}   \\13
  Sleep    3s

I login with the already assigned passcode
  Wait Until Page Contains Element    ${Login_Button_Buyer}
  Page Should Contain Element    ${Login_Button_Buyer}
  Location Should Be     ${BUYER_PORTAL_URL}
  Input Text    ${Login_Email_Buyer}    ${Registered_Buyer_Email}
  Sleep    1s
  Click Element    ${Company_Dropdown_Buyer}
  Wait Until Page Contains Element    ${Select_Buyer_Comapny}
  Sleep    1s
  Click Element    ${Select_Buyer_Comapny}
  Click Element    ${Agreement_Checkbox_Buyer}
  Sleep    0.5s
  Set Focus To Element    ${Login_Button_Buyer}
  Execute JavaScript  window.document.querySelector("#checkforPasscode").scrollIntoView()
  Execute JavaScript  window.document.querySelector("#checkforPasscode").focus()
  Press Key    ${Login_Button_Buyer}   \\13
  Sleep    3s
  Wait Until Page Contains    Select notification type
  Wait Until Page Contains Element    ${Notification_Popup}
  Page Should Contain Element    ${Notification_Popup}
  Wait Until Page Contains Element    ${Already_Have_Passcode}
  Click Element    ${Already_Have_Passcode}
  Wait Until Page Contains Element    ${Passcode_Field}
  Input Text    ${Passcode_Field}    ${Valid_Token}
  Click Element    ${Submit_Passcode_Button}
  Sleep    2s

I click the already have passcode link
  Wait Until Page Contains Element    ${Login_Button_Buyer}
  Page Should Contain Element    ${Login_Button_Buyer}
  Location Should Be     ${BUYER_PORTAL_URL}
  Input Text    ${Login_Email_Buyer}    ${Registered_Buyer_Email}
  Sleep    1s
  Click Element    ${Company_Dropdown_Buyer}
  Wait Until Page Contains Element    ${Select_Buyer_Comapny}
  Sleep    1s
  Click Element    ${Select_Buyer_Comapny}
  Click Element    ${Agreement_Checkbox_Buyer}
  Sleep    1s
  Set Focus To Element    ${Login_Button_Buyer}
  Execute JavaScript  window.document.querySelector("#checkforPasscode").scrollIntoView()
  Execute JavaScript  window.document.querySelector("#checkforPasscode").focus()
  Press Key    ${Login_Button_Buyer}   \\13
  Sleep    3s
  Wait Until Page Contains    Select notification type
  Wait Until Page Contains Element    ${Notification_Popup}
  Page Should Contain Element    ${Notification_Popup}
  Wait Until Page Contains Element    ${Already_Have_Passcode}
  Click Element    ${Already_Have_Passcode}

I login with invalid passcode
  Wait Until Page Contains Element    ${Login_Button_Buyer}
  Page Should Contain Element    ${Login_Button_Buyer}
  Location Should Be     ${BUYER_PORTAL_URL}
  Input Text    ${Login_Email_Buyer}    ${Registered_Buyer_Email}
  Sleep    1s
  Click Element    ${Company_Dropdown_Buyer}
  Wait Until Page Contains Element    ${Select_Buyer_Comapny}
  Sleep    1s
  Click Element    ${Select_Buyer_Comapny}
  Click Element    ${Agreement_Checkbox_Buyer}
  Sleep    1s
  Set Focus To Element    ${Login_Button_Buyer}
  Execute JavaScript  window.document.querySelector("#checkforPasscode").scrollIntoView()
  Execute JavaScript  window.document.querySelector("#checkforPasscode").focus()
  Press Key    ${Login_Button_Buyer}   \\13
  Sleep    3s
  Wait Until Page Contains    Select notification type
  Wait Until Page Contains Element    ${Notification_Popup}
  Page Should Contain Element    ${Notification_Popup}
  Wait Until Page Contains Element    ${Already_Have_Passcode}
  Click Element    ${Already_Have_Passcode}
  Wait Until Page Contains Element    ${Passcode_Field}
  Sleep    1s
  Input Text    ${Passcode_Field}    ${Invalid_Token}
  Click Element    ${Submit_Passcode_Button}
  Sleep    2s

I login with valid passcode
  Wait Until Page Contains Element    ${Login_Button_Buyer}
  Page Should Contain Element    ${Login_Button_Buyer}
  Location Should Be     ${BUYER_PORTAL_URL}
  Input Text    ${Login_Email_Buyer}    ${Registered_Buyer_Email}
  Sleep    1s
  Click Element    ${Company_Dropdown_Buyer}
  Wait Until Page Contains Element    ${Select_Buyer_Comapny}
  Sleep    1s
  Click Element    ${Select_Buyer_Comapny}
  Click Element    ${Agreement_Checkbox_Buyer}
  Sleep    1s
  Set Focus To Element    ${Login_Button_Buyer}
  Execute JavaScript  window.document.querySelector("#checkforPasscode").scrollIntoView()
  Execute JavaScript  window.document.querySelector("#checkforPasscode").focus()
  Press Key    ${Login_Button_Buyer}   \\13
  Sleep    3s
  Wait Until Page Contains    Select notification type
  Wait Until Page Contains Element    ${Notification_Popup}
  Page Should Contain Element    ${Notification_Popup}
  Wait Until Page Contains Element    ${Already_Have_Passcode}
  Click Element    ${Already_Have_Passcode}
  Wait Until Page Contains Element    ${Passcode_Field}
  Sleep    1s
  Input Text    ${Passcode_Field}    ${Valid_Token}
  Click Element    ${Submit_Passcode_Button}
  Sleep    2s

I login with valid passcode by pressing enter on submit button
  Wait Until Page Contains Element    ${Login_Button_Buyer}
  Page Should Contain Element    ${Login_Button_Buyer}
  Location Should Be     ${BUYER_PORTAL_URL}
  Input Text    ${Login_Email_Buyer}    ${Registered_Buyer_Email}
  Sleep    1s
  Click Element    ${Company_Dropdown_Buyer}
  Wait Until Page Contains Element    ${Select_Buyer_Comapny}
  Sleep    1s
  Click Element    ${Select_Buyer_Comapny}
  Click Element    ${Agreement_Checkbox_Buyer}
  Sleep    1s
  Set Focus To Element    ${Login_Button_Buyer}
  Execute JavaScript  window.document.querySelector("#checkforPasscode").scrollIntoView()
  Execute JavaScript  window.document.querySelector("#checkforPasscode").focus()
  Press Key    ${Login_Button_Buyer}     \\13
  Sleep    3s
  Wait Until Page Contains    Select notification type
  Wait Until Page Contains Element    ${Notification_Popup}
  Page Should Contain Element    ${Notification_Popup}
  Wait Until Page Contains Element    ${Already_Have_Passcode}
  Click Element    ${Already_Have_Passcode}
  Wait Until Page Contains Element    ${Passcode_Field}
  Sleep    1s
  Input Text    ${Passcode_Field}    ${Valid_Token}
  Set Focus To Element     ${Submit_Passcode_Button}
  Execute JavaScript  window.document.querySelector("#submitPasscode").scrollIntoView()
  Execute JavaScript  window.document.querySelector("#submitPasscode").focus()
  Press Key    ${Submit_Passcode_Button}     \\13
  Sleep    2s

I login with valid passcode by pressing cancel button
  Wait Until Page Contains Element    ${Login_Button_Buyer}
  Page Should Contain Element    ${Login_Button_Buyer}
  Location Should Be     ${BUYER_PORTAL_URL}
  Input Text    ${Login_Email_Buyer}    ${Registered_Buyer_Email}
  Sleep    1s
  Click Element    ${Company_Dropdown_Buyer}
  Wait Until Page Contains Element    ${Select_Buyer_Comapny}
  Sleep    1s
  Click Element    ${Select_Buyer_Comapny}
  Click Element    ${Agreement_Checkbox_Buyer}
  Sleep    1s
  Set Focus To Element    ${Login_Button_Buyer}
  Execute JavaScript  window.document.querySelector("#checkforPasscode").scrollIntoView()
  Execute JavaScript  window.document.querySelector("#checkforPasscode").focus()
  Press Key    ${Login_Button_Buyer}     \\13
  Sleep    3s
  Wait Until Page Contains    Select notification type
  Wait Until Page Contains Element    ${Notification_Popup}
  Page Should Contain Element    ${Notification_Popup}
  Wait Until Page Contains Element    ${Already_Have_Passcode}
  Click Element    ${Already_Have_Passcode}
  Wait Until Page Contains Element    ${Passcode_Field}
  Sleep    1s
  Input Text    ${Passcode_Field}    ${Valid_Token}
  Click Element    ${Cancel_Passcode_Button}
  Sleep    2s


#Then---------------------------------------------------------------------------
I should be on the buyers portal
  Wait Until Page Contains Element    ${Login_Button_Buyer}
  Page Should Contain Element    ${Login_Button_Buyer}
  Location Should Be     ${BUYER_PORTAL_URL}

I should see the login input field
  Wait Until Page Contains Element    ${Login_Button_Buyer}
  Page Should Contain Element    ${Login_Button_Buyer}
  Location Should Be     ${BUYER_PORTAL_URL}
  Wait Until Page Contains Element    ${Login_Email_Buyer}
  Page Should Contain Element    ${Login_Email_Buyer}

I should see the compnay selection dropdown
  Wait Until Page Contains Element    ${Login_Button_Buyer}
  Page Should Contain Element    ${Login_Button_Buyer}
  Location Should Be     ${BUYER_PORTAL_URL}
  Wait Until Page Contains Element    ${Login_Email_Buyer}
  Page Should Contain Element    ${Login_Email_Buyer}
  Wait Until Page Contains Element    ${Company_Dropdown_Buyer}
  Page Should Contain Element    ${Company_Dropdown_Buyer}

I should see the agreement checkbox
  Wait Until Page Contains Element    ${Login_Button_Buyer}
  Page Should Contain Element    ${Login_Button_Buyer}
  Location Should Be     ${BUYER_PORTAL_URL}
  Wait Until Page Contains Element    ${Login_Email_Buyer}
  Page Should Contain Element    ${Login_Email_Buyer}
  Wait Until Page Contains Element    ${Company_Dropdown_Buyer}
  Page Should Contain Element    ${Company_Dropdown_Buyer}
  Wait Until Page Contains Element    ${Agreement_Checkbox_Buyer}
  Page Should Contain Element    ${Agreement_Checkbox_Buyer}
  Page Should Contain    I agree to the

I should see the login button
  Wait Until Page Contains Element    ${Login_Button_Buyer}
  Page Should Contain Element    ${Login_Button_Buyer}
  Location Should Be     ${BUYER_PORTAL_URL}
  Wait Until Page Contains Element    ${Login_Email_Buyer}
  Page Should Contain Element    ${Login_Email_Buyer}
  Wait Until Page Contains Element    ${Company_Dropdown_Buyer}
  Page Should Contain Element    ${Company_Dropdown_Buyer}
  Wait Until Page Contains Element    ${Agreement_Checkbox_Buyer}
  Page Should Contain Element    ${Agreement_Checkbox_Buyer}
  Page Should Contain    I agree to the

I should not be able to login and an error should be shown
  Wait Until Page Contains    We were unable to locate this account
  Page Should Contain    We were unable to locate this account
  Wait Until Page Contains     If you verified your credentials and no information was found, please contact

I should not be able to login and the company field should be encircled red
  Wait Until Page Contains Element    ${Company_Encircled_Red}
  Page Should Contain Element    ${Company_Encircled_Red}

I should not be able to login and the email field should be encircled red
  Wait Until Page Contains Element    ${Email_Encircled_Red}
  Page Should Contain Element    ${Email_Encircled_Red}

I should not be able to agreement and the email field should be encircled red
  Wait Until Page Contains Element    ${Agreement_Encircled_Red}
  Page Should Contain Element    ${Email_Encircled_Red}

I should be able to proceed with login steps
  Wait Until Page Contains Element    ${Select_Phone_Type}
  Page Should Contain Element    ${Select_Phone_Type}

I should the terms of service popup
  Wait Until Page Contains Element    ${TOS_Modal}
  Page Should Contain Element    ${TOS_Modal}

I should see the TOS popup being closed
  Wait Until Element Is Not Visible    ${TOS_Modal}
  Element Should Not Be Visible    ${TOS_Modal}

I should see the notifcation popup
  Wait Until Page Contains    Select notification type
  Wait Until Page Contains Element    ${Notification_Popup}
  Page Should Contain Element    ${Notification_Popup}

I should be able to login
  Wait Until Page Contains Element    ${Fruad_Warning_Message}
  Wait Until Page Contains Element    ${Accept_Warning}
  Click Element    ${Accept_Warning}
  Wait Until Page Contains Element    ${Logout_Button}

I should see the passcode popup
  Wait Until Page Contains Element    ${Passcode_Field}
  Sleep    2s

I should see the passcode field
  Wait Until Page Contains Element    ${Passcode_Field}
  Page Should Contain Element    ${Passcode_Field}
  Sleep    2s

I should see an error message and I should not login
  Wait Until Page Contains    Error!
  Wait Until Page Contains    Invalid passcode. Please try again.
  Page Should Contain Element    ${Passcode_Field}

I should be returned to the login page
  Wait Until Page Contains Element    ${Login_Email_Buyer}
  Page Should Contain Element    ${Login_Email_Buyer}
  Element Should Be Visible    ${Login_Button_Buyer}
