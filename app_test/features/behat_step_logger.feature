 @debug-mode @enable-behat-step-listener @truncate-log-file
Feature: Behat steps logger
  In order to understand what happens behind the scene
  As dev
  I need to catch events for each example/scenarios/steps executed

  Scenario Outline: check logs entry and event catch
    Given A log entry must exist for current example start event using var "<var>"
    Then I should have caught event regarding current step start event and will have the end event
    And A log entry must exist for current step start event and I will have the one regarding end event
    Then I will caught event regarding current example end event using var "<var>"
    And I will have a log entry regarding current example end event using var "<var>"
    And I will have a log entry regarding current background end event
    Examples:
    | var   |
    | value |
