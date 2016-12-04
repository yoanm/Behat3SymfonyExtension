@with-mink
Feature: SfKernelLogger
  In order to easily debug
  As dev
  I need to have have my symfony app event recorded

  Scenario: Request event
    Given I truncate log file
    And I listen for symfony kernel event
    When I call my symfony app with a valid route
    And A log entry for request event to valid route must exists

  Scenario: Exception event
    Given I truncate log file
    And I listen for symfony kernel event
    When I call my symfony app with an exception route
    # Check that kernel has been rebooted has we have already made a request in previous scenario
    Then I should have caugh 4 symfony kernel events
    And I should have caught events for symfony kernel shutdown
    And I should have caught events for symfony kernel boot
    And A log entry for request event to exception route must exists
    And A log entry for exception event must exists
