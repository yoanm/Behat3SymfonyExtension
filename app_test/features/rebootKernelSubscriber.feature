Feature: RebootKernelSubscriber
  In order to behave like real symfony app
  As tester
  I need to have a clean kernel at scenario beginning

  Scenario: Scenario made to get the current container instance
      Given I backup container instance

  Scenario: Assert that kernel is rebooted before each scenario
      Given current container instance has changed

  Scenario: Assert that kernel is not rebooted for the first request, but rebooted for all following one
     Given I backup container instance
     And I listen for symfony kernel event
     When I call my symfony app with a valid route
     # Assert container instance is the same one as kernel is a new one
     Then current container instance must not have changed
     And I should have caught 2 symfony kernel events
     And I should have caught events for client request
     When I backup container instance
     And I listen for symfony kernel event
     # Container should be rebooted before this request
     And I call my symfony app with a valid route
     Then current container instance has changed
     Then I should have caught 6 symfony kernel events
     And I should have caught events for symfony kernel shutdown
     And I should have caught events for symfony kernel boot
     And I should have caught events for client request
