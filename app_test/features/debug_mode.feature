Feature: Debug mode
  In order to understand what extension does
  As dev
  I need to be able to enable debug mode

   @default-config-check
  Scenario: Default false
    Given extension config "debug_mode" is false

   @debug-mode
  Scenario: check true
     Given extension config "debug_mode" is true
