*** Settings ***
Library           SeleniumLibrary  timeout=20
Suite Teardown    Close All Browsers
Test Teardown     Close All Browsers
Resource          ../Resources/keywords.robot

*** Variables ***

*** Test Cases ***

Scenario:To Verify that user can access Admin page
   Given Navigate to "buyerdocs.com"
    When I navigate to Admin page
    Then I should be on the admin page

Scenario: Verify that the login screen contains username/password/Login button
   Given Navigate to "buyerdocs.com"
    When I navigate to Admin page
    Then I should see Email field, password field, Reset password and Login button

Scenario: Username and password fields should be mandatory and marked with *
   Given Navigate to "buyerdocs.com"
     And I navigate to Admin page
    When I click Login button without entering credentials
    Then I should see an error notification

Scenario: Verify the sign in button can be pressed by hitting Enter
   Given Navigate to "buyerdocs.com"
     And I navigate to Admin page
    When I enter valid credentials
     And I press enter or return from keyboard
    Then I should be able to login

Scenario: Verify the user can login with valid credentials to admin dashboard
   Given Navigate to "buyerdocs.com"
     And I navigate to Admin page
    When I enter valid login credentials
    Then I should be able to login to the admin page

Scenario: Verify that the user cannot login with invalid username and invalid password to admin dashboard
   Given Navigate to "buyerdocs.com"
     And I navigate to Admin page
    When I enter invalid username and invalid password
    Then I should not be able to login to the admin dashboard

Scenario: Verify that the user cannot login with invalid username and valid password to the admin dashboard
   Given Navigate to "buyerdocs.com"
     And I navigate to Admin page
    When I enter invalid username and valid password
    Then I should not be able to login to the admin dashboard

Scenario: Verify that the user cannot login with valid username and invalid password
  Given Navigate to "buyerdocs.com"
    And I navigate to Admin page
   When I enter valid username and invalid password
   Then I should not be able to login to the admin dashboard

Scenario: Verify that the user cannot login with blank fields
  Given Navigate to "buyerdocs.com"
    And I navigate to Admin page
   When I leave the username and password fields empty
   Then I should see an error notification

Scenario: Verify spaces are not allowed before password
  Given Navigate to "buyerdocs.com"
    And I navigate to Admin page
   When I add some spaces before password
   Then I should not be able to login to the admin dashboard

Scenario: Verify that email address is case sensitive
  Given Navigate to "buyerdocs.com"
    And I navigate to Admin page
   When I add email in capital letters
   Then I should not be able to login to the admin dashboard

Scenario: Verify that password address is case sensitive
  Given Navigate to "buyerdocs.com"
    And I navigate to Admin page
   When I add password in capital letters
   Then I should not be able to login to the admin dashboard

# Scenario: Verify the attention message is displayed when caps lock is ON
#   Given Navigate to "buyerdocs.com"
#     And I navigate to Admin page
#    When I turn on caps lock button
#    Then I should see a notification that caps lock is on

# Scenario: User can access Admin page
#    Given Navigate to "buyerdocs.com"
#     When I navigate to Admin page
#     Then I should see demo admin credentials pre-entered

*** Keywords ***

#Given---------------------------------------------------------------------------

#When---------------------------------------------------------------------------

I click Login button without entering credentials
  Wait Until Page Contains Element  ${Login_Button}
  Click Element    ${Login_Button}

I enter valid credentials
  Wait Until Page Contains Element    ${Email_Field}
  Input Text    ${Email_Field}    ${Valid_Username}
  Input Text    ${Password_Field}   ${Valid_Password}

I press enter or return from keyboard
  Wait Until Page Contains Element    ${Login_Button}
  Press Key    ${Login_Button}    \\13                #Ascii code for Enter or return

I enter valid login credentials
  Wait Until Page Contains Element    ${Email_Field}
  Input Text    ${Email_Field}    ${Valid_Username}
  Input Text    ${Password_Field}   ${Valid_Password}
  Click Element    ${Login_Button}

I enter invalid username and invalid password
  Wait Until Page Contains Element    ${Email_Field}
  Input Text    ${Email_Field}    ${Invalid_Username}
  Input Text    ${Password_Field}   ${Invalid_Password}
  Click Element    ${Login_Button}

I enter invalid username and valid password
  Wait Until Page Contains Element    ${Email_Field}
  Input Text    ${Email_Field}    ${Invalid_Username}
  Input Text    ${Password_Field}   ${Valid_Password}
  Click Element    ${Login_Button}

I enter valid username and invalid password
  Wait Until Page Contains Element    ${Email_Field}
  Input Text    ${Email_Field}    ${Valid_Username}
  Input Text    ${Password_Field}   ${Invalid_Password}
  Click Element    ${Login_Button}

I leave the username and password fields empty
  Wait Until Page Contains Element    ${Login_Button}
  Click Element    ${Login_Button}

I add some spaces before password
  Wait Until Page Contains Element    ${Email_Field}
  Input Text    ${Email_Field}    ${Valid_Username}
  Press Key    ${Password_Field}    \\32   #Ascii code for Space
  Press Key    ${Password_Field}    \\32
  Press Key    ${Password_Field}    \\98     #Ascii code for b
  Press Key    ${Password_Field}    \\117    #Ascii code for u
  Press Key    ${Password_Field}    \\121    #Ascii code for y
  Press Key    ${Password_Field}    \\101    #Ascii code for e
  Press Key    ${Password_Field}    \\114    #Ascii code for r
  Press Key    ${Password_Field}    \\100    #Ascii code for d
  Press Key    ${Password_Field}    \\111    #Ascii code for o
  Press Key    ${Password_Field}    \\99     #Ascii code for c
  Press Key    ${Password_Field}    \\115    #Ascii code for s
  Press Key    ${Password_Field}    \\108    #Ascii code for i
  Press Key    ${Password_Field}    \\115    #Ascii code for s
  Press Key    ${Password_Field}    \\103    #Ascii code for g
  Press Key    ${Password_Field}    \\114    #Ascii code for r
  Press Key    ${Password_Field}    \\101    #Ascii code for e
  Press Key    ${Password_Field}    \\97     #Ascii code for a
  Press Key    ${Password_Field}    \\116    #Ascii code for t
  Wait Until Page Contains Element    ${Login_Button}
  Page Should Contain Element    ${Login_Button}
  Click Element    ${Login_Button}

I add email in capital letters
  Wait Until Page Contains Element    ${Email_Field}
  Input Text    ${Email_Field}    ${Email_In_Capital}
  Input Text    ${Password_Field}   ${Valid_Password}
  Click Element    ${Login_Button}

I add password in capital letters
  Wait Until Page Contains Element    ${Email_Field}
  Input Text    ${Email_Field}   ${Valid_Username}
  Input Text    ${Password_Field}    ${Password_In_Capital}
  Click Element    ${Login_Button}


#Then---------------------------------------------------------------------------
I should be on the admin page
  Wait Until Page Contains    Admin Login
  Page Should Contain    Admin Login
  Location Should Be     ${Admin_URL}

I should see Email field, password field, Reset password and Login button
  Wait Until Page Contains Element    ${Email_Field}
  Page Should Contain Element    ${Email_Field}
  Wait Until Page Contains Element    ${Password_Field}
  Page Should Contain Element    ${Password_Field}
  Wait Until Page Contains Element    ${Reset_Password_Link}
  Page Should Contain Element    ${Reset_Password_Link}
  Wait Until Page Contains Element    ${Login_Button}
  Page Should Contain Element    ${Login_Button}

I should see demo admin credentials pre-entered
  Wait Until Page Contains Element    ${Email_Field}
  Page Should Contain Element    ${Email_Field}
  Wait Until Page Contains    admin@demo.com

I should see an error notification
  Wait Until Page Contains Element    ${Email_Is_Required}
  Page Should Contain Element    ${Email_Is_Required}

I should be able to login
  Wait Until Page Contains Element    ${Client_Button}
  Page Should Contain Element    ${Client_Button}

I should be able to login to the admin page
  Wait Until Page Contains Element    ${Client_Button}
  Page Should Contain Element    ${Client_Button}

I should not be able to login to the admin dashboard
  Wait Until Page Contains    These credentials do not match our records.
  Page Should Contain    These credentials do not match our records.
  Page Should Contain Element    ${Email_Field}
  Page Should Contain Element    ${Password_Field}
  Page Should Contain Element    ${Login_Button}
