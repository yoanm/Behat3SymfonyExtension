Feature: Logger
  In order log something
  As behat context
  I need to have logger injected

  Scenario: Logger
    Given I have access to a logger
    And I truncate log file
    When I log a test message
    Then Test message is in log file

    @default-config-check
  Scenario: Assert default configuration
    Given extension logger config "path" is "var/log/behat.log"
    And extension logger config "level" is 100

    @custom-config-check
  Scenario: Assert custom configuration
    Given extension logger config "path" is "var/log/behat2.log"
    And extension logger config "level" is 200
