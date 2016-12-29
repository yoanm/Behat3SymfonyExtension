Feature: kernel
  In order to play with the app symfony kernel
  As behat context
  I need to have kernel injected

  Scenario: Access container parameter
    Given I have access to symfony app container
    Then The container test parameter is set
