def create_profile():
  from selenium import webdriver
  fp =webdriver.FirefoxProfile()
  # Changing native-is-localhost may or may not be needed. If subdomain is not working properly, enable it
  # fp.set_preference("network.dns.native-is-localhost",True)
  fp.update_preferences()
  return fp.path
