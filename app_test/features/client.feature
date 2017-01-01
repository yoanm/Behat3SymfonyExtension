Feature: Client
  In order to use mink
  As dev
  I need to have Client as mink driver client

  Scenario: Simple
    Given I have mink extension
    Then mink driver client must be a Client instance
