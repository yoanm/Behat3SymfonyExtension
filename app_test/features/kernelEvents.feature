Feature: kernelEvents
  In order to be aware of symfony app kernel life cycle
  As behat context
  I need to receive events

  Scenario: Shutdown and then boot Kernel
    Given I listen for symfony kernel event
    When I shutdown symfony kernel
    Then I should have caugh 2 symfony kernel events
    And I should have caught events for symfony kernel shutdown
    When I listen for symfony kernel event
    And I boot symfony kernel
    Then I should have caugh 2 symfony kernel events
    And I should have caught events for symfony kernel boot
