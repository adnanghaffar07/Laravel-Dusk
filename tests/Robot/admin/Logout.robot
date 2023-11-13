*** Settings ***
Library           SeleniumLibrary  timeout=20
Suite Teardown    Close All Browsers
Test Teardown     Close All Browsers
Resource          ../Resources/keywords.robot

*** Variables ***
${Logout_Button}       css=[href='${SERVER}logout']
${Private_URL}         ${SERVER}user/home


*** Test Cases ***

Scenario:To Verify that logout button is visible once the user logs in
   Given Navigate to "buyerdocs.com"
    When Login with valid credentials
    Then I should see the logout button

Scenario:To Verify that user can logout by click logout button
   Given Navigate to "buyerdocs.com"
     And Login with valid credentials
    When I click the logout button
    Then I should be logged out

Scenario:To Verify that user is redirected to login page after successful logout
    Given Navigate to "buyerdocs.com"
      And Login with valid credentials
     When I click the logout button
     Then I should be logged out and redirected to login page

Scenario:To Verify that user cannot access private url if not logged in
    Given Navigate to "buyerdocs.com"
     When I access private URL
     Then I should be redirected to login page

Scenario: Verify that Logout option is not visible till the user is logged in
   Given Navigate to "buyerdocs.com"
    When I navigate to Admin page
    Then I should not see the logout button


*** Keywords ***

#Given---------------------------------------------------------------------------


#When---------------------------------------------------------------------------
I click the logout button
  Wait Until Page Contains Element    ${Logout_Button}
  Page Should Contain Element    ${Logout_Button}
  Click Element    ${Logout_Button}

I access private URL
  Go To    ${Private_URL}


#Then--------------------------------------------------------------------------
I should see the logout button
  Wait Until Page Contains Element    ${Logout_Button}
  Page Should Contain Element    ${Logout_Button}

I should be logged out
  Wait Until Page Contains Element    ${Login_Button}
  Location Should Be  ${Admin_URL}

I should be logged out and redirected to login page
  Wait Until Page Contains Element    ${Login_Button}
  Location Should Be  ${Admin_URL}

I should be redirected to login page
  Wait Until Page Contains Element    ${Login_Button}
  Location Should Be  ${Admin_URL}

I should not see the logout button
  Wait Until Page Does Not Contain Element    ${Logout_Button}
