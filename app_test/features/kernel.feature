Feature: kernel
  In order to play with the app symfony kernel
  As behat context
  I need to have kernel injected

  Scenario: Access container parameter
    Given I have access to symfony app container
    Then The container test parameter is set

    @default-config-check
  Scenario: Assert default configuration
    Given extension kernel config "bootstrap" is "app/autoload.php"
    And extension kernel config "path" is "app/AppKernel.php"
    And extension kernel config "class" is "AppKernel"
    And extension kernel config "env" is "test"
    And extension kernel config "debug" is true
    And extension kernel config "reboot" is true

    @custom-config-check
  Scenario: Assert custom configuration
    Given extension kernel config "bootstrap" is "app/custom-autoload.php"
    And extension kernel config "path" is "app/CustomAppKernel.php"
    And extension kernel config "class" is "CustomAppKernel"
    And extension kernel config "env" is "dev"
    And extension kernel config "debug" is false
    And extension kernel config "reboot" is false
