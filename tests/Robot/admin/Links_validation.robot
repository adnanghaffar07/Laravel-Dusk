*** Settings ***
Library           SeleniumLibrary  timeout=20
Suite Teardown    Close All Browsers
Test Teardown     Close All Browsers
Resource          ../Resources/keywords.robot

*** Variables ***

#buyerdocs Home Page ----------
${Logo}                          css=[src='${SERVER}images/logos/buyerdocs/logo_white_horizontal_plain.svg']

${News_Button}                   css=[href='${SERVER}news']  
${Contact_Button}                css=[href='${SERVER}contact']

#About Test
${About_Button}                  css=[href='/about']
${About_URL}                     ${SERVER}about

#FAQ test
${FAQ_Button}                    css=[href='${SERVER}faq']
${FAQ_URL}                       ${SERVER}faq

#Contact form
${Name_Text_Field}               css=#name
${Email_Text_Field}              css=#email
${Company_Text_Field}            css=#company
${Position_Text_Field}           css=#position
${Submit_Contact_Form}           css=#contactButton[type='submit']
${Message_Field_Notification}    css=.user-message .wpcf7-not-valid-tip

*** Test Cases ***

Scenario: User access buyerdocs.com and click on about Page
   Given Navigate to "buyerdocs.com"
    When I click the about button from the top menu
    Then I should go to the about page

Scenario: User access buyerdocs.com and click on news Page
   Given Navigate to "buyerdocs.com"
    When I click the news button from the top menu
    Then I should go to the news page

Scenario: User access buyerdocs.com and click on contact Page
   Given Navigate to "buyerdocs.com"
    When I click the contact button from the top menu
    Then I should go to the contact page

Scenario: User access buyerdocs.com and click on FAQ Page
   Given Navigate to "buyerdocs.com"
    When I click the FAQ button from the top menu
    Then I should go to the FAQ page

Scenario: User access buyerdocs.com and click on admin Page
   Given Navigate to "buyerdocs.com"
    When I click the admin button from the top menu
    Then I should go to the admin page

Scenario: User accesses home page from the logo
   Given Navigate to "buyerdocs.com"
    When I click the logo from top left corner
    Then I should go to the home page

#------------ Waiting for andy to disable captcha I would uncomment this test

# Scenario: User access buyerdocs.com and click on contact Page
#    Given Navigate to "buyerdocs.com"
#      And I click the contact button from the top menu
#     When I fill all the required fields of contact form except one
#     Then I should see the error notification message "The field is required"

*** Keywords ***

#Given---------------------------------------------------------------------------

#When---------------------------------------------------------------------------
I click the about button from the top menu
  Click Element  ${About_Button}

I click the news button from the top menu
  Wait Until Page Contains Element  ${News_Button}
  Click Element  ${News_Button}

I click the contact button from the top menu
  Wait Until Page Contains Element  ${Contact_Button}
  Click Element  ${Contact_Button}

I fill all the required fields of contact form except one
  Wait Until Page Contains Element  ${Name_Text_Field}
  Input Text  ${Name_Text_Field}  Taimur
  Sleep    2s
  Wait Until Page Contains Element  ${Email_Text_Field}
  Wait Until Element Is Enabled    ${Email_Text_Field}
  Input Text  ${Email_Text_Field}  taimur.aamer@gmail.com
  Wait Until Page Contains Element  ${Company_Text_Field}
  Wait Until Element Is Enabled    ${Company_Text_Field}
  Input Text  ${Company_Text_Field}   Sabahat group of companies
  Wait Until Page Contains Element  ${Position_Text_Field}
  Wait Until Element Is Enabled    ${Position_Text_Field}
  Input Text  ${Position_Text_Field}    SQA Engineer
  Wait Until Page Contains Element  ${Submit_Contact_Form}
  Wait Until Element Is Enabled    ${Submit_Contact_Form}
  Set Focus To Element     ${Submit_Contact_Form}
  Wait Until Element Is Visible    ${Submit_Contact_Form}
  Execute JavaScript  window.document.getElementById("contactButton").scrollIntoView(false)
  # Click Element  ${Submit_Contact_Form}
  Click Button    Send Request

I click the FAQ button from the top menu
  Wait Until Page Contains Element  ${FAQ_Button}
  Click Element  ${FAQ_Button}

I click the admin button from the top menu
  Wait Until Page Contains Element  ${Admin_Button}
  Click Element    ${Admin_Button}

 I click the logo from top left corner
  Wait Until Page Contains Element  ${Logo}
  Wait Until Element Is Visible    ${Logo}
  Click Element    ${Logo}

#Then---------------------------------------------------------------------------

I should go to the about page
  Wait until page contains  About BuyerDocs
  Page Should Contain    About BuyerDocs
  Location Should Be    ${About_URL}

I should go to the news page
  Wait Until Page Contains  In the News

I should go to the contact page
  Wait Until Page Contains  Contact Us

I should see the error notification message "The field is required"
  Wait Until Page Contains    The field is required.
  Wait Until Element Contains  ${Message_Field_Notification}    The field is required.

I should go to the FAQ page
  Wait Until Page Contains    What is BuyerDocs?
  Page should contain  What is BuyerDocs?
  Location Should Be     ${FAQ_URL}

I should go to the admin page
  Wait Until Page Contains    Admin Login
  Page Should Contain    Admin Login
  Location Should Be     ${Admin_URL}

I should go to the home page
  Wait Until Page Contains    Securing Wire Transfers for Real Estate
  Page Should Contain    Securing Wire Transfers for Real Estate
  Location Should Be    ${SERVER}
