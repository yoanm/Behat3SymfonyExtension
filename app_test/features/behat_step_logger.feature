 @debug-mode @enable-behat-step-listener
Feature: Behat steps logger
  In order to understand what happens behind the scene
  As dev
  I need to have a log entry for each feature/Background/example/scenario/step executed

  Background: check background logs entry
    Given I have a log entry regarding current feature start
    And I have a log entry regarding current background start
    Then I will have a log entry regarding current background end

  Scenario: check logs entry
     And I have a log entry regarding current scenario start
     And I have a log entry regarding current step start and end
     Then I will have a log entry regarding current scenario end
     And I will have a log entry regarding current feature end

  Scenario Outline: check logs entry
    Given I have a log entry regarding current scenario outline start
    And I have a log entry regarding current example start using var "<var>"
    And I have a log entry regarding current step start and end
    Then I will have a log entry regarding current example end using var "<var>"
    And I will have a log entry regarding current scenario outline end
  Examples:
    | var   |
    | value |
