*** Settings ***
Library           SeleniumLibrary  timeout=20
Suite Teardown    Close All Browsers
Test Teardown     Close All Browsers
Resource          ../Resources/keywords.robot

*** Variables ***

${Reset_Password_Email_Field}      css=#email
${Reset_Password_URL}              ${SERVER}password/reset
${Submit_Reset_Password}           css=#passwordResetButton
${Email_Required_Error_Message}    css=.text-danger
${Success_Message_Reset_Password}  css=.alert-success



*** Test Cases ***

Scenario:To Verify that reset password link is present on the login page
   Given Navigate to "buyerdocs.com"
    When I navigate to Admin page
    Then I should see the reset password link

Scenario:To Verify that reset password link is accessable
   Given Navigate to "buyerdocs.com"
     And I navigate to Admin page
    When I access reset password link
    Then I should be on the reset password page

Scenario:Verify that password cannot be reset without email address
   Given Navigate to "buyerdocs.com"
     And I navigate to Admin page
     And I access reset password link
    When I try to reset password without email
    Then I should see an error message

Scenario:Verify that password cannot be reset with incorrect email format
   Given Navigate to "buyerdocs.com"
     And I navigate to Admin page
     And I access reset password link
    When I enter incorrect email format
    Then I should see an error message regarding email

Scenario:To Verify that user can reset password
   Given Navigate to "buyerdocs.com"
     And I navigate to Admin page
    When I reset my password
    Then I should be able to reset my password

*** Keywords ***

#Given---------------------------------------------------------------------------

#When---------------------------------------------------------------------------
I access reset password link
  Wait Until Page Contains Element    ${Reset_Password_Link}
  Page Should Contain Element    ${Reset_Password_Link}
  Click Element    ${Reset_Password_Link}

I try to reset password without email
  Wait Until Page Contains Element    ${Reset_Password_Email_Field}
  Wait Until Page Contains Element    ${Submit_Reset_Password}
  Click Element    ${Submit_Reset_Password}

I enter incorrect email format
  Wait Until Page Contains Element    ${Reset_Password_Email_Field}
  Input Text    ${Reset_Password_Email_Field}    taimur.com.pk
  Click Element    ${Submit_Reset_Password}

I reset my password
  Wait Until Page Contains Element    ${Reset_Password_Link}
  Page Should Contain Element    ${Reset_Password_Link}
  Click Element    ${Reset_Password_Link}
  Wait Until Page Contains Element    ${Reset_Password_Email_Field}
  Input Text    ${Reset_Password_Email_Field}    taimur.aamer@gmail.com
  Wait Until Page Contains Element    ${Submit_Reset_Password}
  Click Element    ${Submit_Reset_Password}

#Then---------------------------------------------------------------------------

I should see the reset password link
  Wait Until Page Contains Element    ${Reset_Password_Link}
  Page Should Contain Element    ${Reset_Password_Link}

I should be on the reset password page
  Wait Until Page Contains Element    ${Reset_Password_Email_Field}
  Page Should Contain Element    ${Reset_Password_Email_Field}
  Location Should Be    ${Reset_Password_URL}


I should see an error message
  Wait Until Page Contains Element    ${Email_Required_Error_Message}
  Element Text Should Be    ${Email_Required_Error_Message}   The email field is required.
  Wait Until Page Contains    The email field is required.
  Page Should Contain    The email field is required.

I should see an error message regarding email
  Wait Until Page Contains Element    ${Email_Required_Error_Message}
  Element Text Should Be    ${Email_Required_Error_Message}   The email must be a valid email address.
  Wait Until Page Contains    The email must be a valid email address.
  Page Should Contain    The email must be a valid email address.

I should be able to reset my password
  Wait Until Page Contains Element    ${Success_Message_Reset_Password}
  Element Text Should Be    ${Success_Message_Reset_Password}    We have e-mailed your password reset link!
  Wait Until Page Contains    We have e-mailed your password reset link!
  Page Should Contain    We have e-mailed your password reset link!
