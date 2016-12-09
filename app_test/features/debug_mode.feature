Feature: Debug mode
  In order to understand what extension does
  As dev
  I need to be able to enable debug mode

   @default-config-check
  Scenario: Default false
    Given extension param "debug_mode" is false

   @custom-config-check
  Scenario: check true
     Given extension param "debug_mode" is true
