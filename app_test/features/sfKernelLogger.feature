@with-mink
Feature: SfKernelLogger
  In order to easily debug
  As dev
  I need to have have my symfony app event recorded

  Scenario: Request event
    Given I truncate log file
    And I listen for symfony kernel event
    When I call my symfony app with a valid route
    Then I should have caught 2 symfony kernel events
    And I should have caught events for client request
    And A log entry for request event to valid route must exists

  Scenario: Exception event
    Given I truncate log file
    And I listen for symfony kernel event
    When I call my symfony app with an exception route
    # Check that kernel has been rebooted has we have already made a request in previous scenario
    Then I should have caught 1 symfony kernel events
    And I should have caught events for client request, before event only
    And A log entry for request event to exception route must exists
    And A log entry for exception event must exists
