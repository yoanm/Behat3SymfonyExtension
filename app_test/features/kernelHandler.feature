Feature: kernelHandler
  In order to boot/shudown/reboot app symfony kernel
  As behat context
  I need to have kernelHandler injected

  Scenario: Reboot Kernel
    Given I listen for symfony kernel event
    When I reboot symfony kernel
    Then I should have caugh 4 symfony kernel events
    And I should have caught events for symfony kernel shutdown
    And I should have caught events for symfony kernel boot

  Scenario: Shutdown and then boot Kernel
    Given I listen for symfony kernel event
    When I shutdown symfony kernel
    Then I should have caugh 2 symfony kernel events
    And I should have caught events for symfony kernel shutdown
    When I listen for symfony kernel event
    And I boot symfony kernel
    Then I should have caugh 2 symfony kernel events
    And I should have caught events for symfony kernel boot
