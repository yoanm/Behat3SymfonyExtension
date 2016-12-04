Feature: RebootKernelSubscriber
  In order to behave like real symfony app
  As tester
  I need to have a clean kernel at scenario beginning

    @test-kernel-reboot
  Scenario: Scenario made to get the current container instance
    Given I backup container instance

    @test-kernel-reboot
  Scenario: Assert that container instance has changed
    Given Current container instance has changed
