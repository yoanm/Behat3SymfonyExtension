Feature: kernel
  In order to play with the app symfony kernel
  As behat context
  I need to have kernel injected

  Scenario: Access container parameter
    Given I have access to symfony app container
    Then The container test parameter is set

    @default-config-check
  Scenario: Assert default configuration
    Given kernel param "bootstrap" is "app/autoload.php"
    And kernel param "path" is "app/AppKernel.php"
    And kernel param "class" is "AppKernel"
    And kernel param "env" is "test"
    And kernel param "debug" is true
    And kernel param "reboot" is true

    @custom-config-check
  Scenario: Assert custom configuration
    Given kernel param "bootstrap" is "app/custom-autoload.php"
    And kernel param "path" is "app/CustomAppKernel.php"
    And kernel param "class" is "CustomAppKernel"
    And kernel param "env" is "dev"
    And kernel param "debug" is false
    And kernel param "reboot" is false
