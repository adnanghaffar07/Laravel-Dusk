# This file contains global variables and keywords that are shared across all
# tests. Please do not add any test specific keywords here!
# robot -T -d reports admin ( to run robot tests)
*** Settings ***
Library           SeleniumLibrary  timeout=20
Library           robot.libraries.DateTime
Library           functions.py
#Library           OperatingSystem
Library           Collections

*** Variables ***
${SERVER}                           http://localhost:8000/
${BROWSER}                          Chrome
${BUYER_PORTAL_URL}                 http://portal.localhost:8000/buyer


#Admin test
${Admin_Button}                     css=[href='${SERVER}user/home']
${Admin_URL}                        ${SERVER}login
${Email_Field}                      css=#email
${Password_Field}                   css=#password
${Reset_Password_Link}              css=[href='${SERVER}password/reset']
${Login_Button}                     css=#adminLoginButton
${Email_Is_Required}                css=[required='']
${Valid_Username}                   test@test.test      #admin@demo.com
${Valid_Password}                   buyerdocsisgreat
${Invalid_Username}                 admin1@demo.com
${Invalid_Password}                 123buyerdocsisgreat
${Client_Button}                    css=.menu-item-has-children
${Email_In_Capital}                 ADMIN@DEMO.COM
${Password_In_Capital}              BUYERDOCSISGREAT

${Buyer_Email_Field}                css=#email


*** Keywords ***
#Given
Navigate to "buyerdocs.com"
    # Needed for FireFox
    ${profile_path}  Create Profile

    # Needed for InternetExplorer
    ${dc}   Evaluate    sys.modules['selenium.webdriver'].DesiredCapabilities.INTERNETEXPLORER  sys, selenium.webdriver
    Set To Dictionary   ${dc}   ie.forceCreateProcessApi    ${True}
    Set To Dictionary   ${dc}   ie.ensureCleanSession       ${True}
#    Set To Dictionary   ${dc}   ignoreProtectedModeSettings     ${True}
#    Set To Dictionary   ${dc}   ie.browserCommandLineSwitches=-private  # causes loss of connection even with requireWindowFocus
#    Set To Dictionary   ${dc}   requireWindowFocus      ${True}

    Open Browser  ${SERVER}  ${BROWSER}   desired_capabilities=${dc}   ff_profile_dir=${profile_path}
    Set Window Size    1360    768
    Maximize Browser Window
    Wait until page contains element  ${Admin_Button}

I navigate to Admin page
  Wait Until Page Contains Element  ${Admin_Button}
  Click Element    ${Admin_Button}

Login with valid credentials
  Wait Until Page Contains Element  ${Admin_Button}
  Click Element    ${Admin_Button}
  Wait Until Page Contains Element    ${Email_Field}
  Input Text    ${Email_Field}    ${Valid_Username}
  Input Text    ${Password_Field}   ${Valid_Password}
  Click Element    ${Login_Button}

Navigate to buyers portal
    # Needed for FireFox
    ${profile_path}  Create Profile

    # Needed for InternetExplorer
    ${dc}   Evaluate    sys.modules['selenium.webdriver'].DesiredCapabilities.INTERNETEXPLORER  sys, selenium.webdriver
    Set To Dictionary   ${dc}   ie.forceCreateProcessApi    ${True}
    Set To Dictionary   ${dc}   ie.ensureCleanSession       ${True}
#    Set To Dictionary   ${dc}   ignoreProtectedModeSettings     ${True}
#    Set To Dictionary   ${dc}   ie.browserCommandLineSwitches=-private  # causes loss of connection even with requireWindowFocus
#    Set To Dictionary   ${dc}   requireWindowFocus      ${True}

    Open Browser  ${BUYER_PORTAL_URL}  ${BROWSER}   desired_capabilities=${dc}   ff_profile_dir=${profile_path}
    Set Window Size    1360    768
    Maximize Browser Window
    Wait Until Page Contains Element    ${Buyer_Email_Field}
# --- Views ------------------------------------------------------------------


# --- Content ----------------------------------------------------------------
