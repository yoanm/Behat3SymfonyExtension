 @debug-mode @enable-behat-step-listener
Feature: Behat steps logger
  In order to understand what happens behind the scene
  As dev
  I need to catch events for each backgrounds/scenarios/steps executed

  Background: check background logs entry and event catch
    Given I should have caught event regarding current scenario start event
    And A log entry must exist for current scenario start event
    Then I will caught event regarding current background end event
    And I will have a log entry regarding current background end event

  Scenario: check logs entry and event catch
     Given I should have caught event regarding current step start event and will have the end event
     And A log entry must exist for current step start event and I will have the one regarding end event
     Then I will caught event regarding current scenario end event
     And I will have a log entry regarding current scenario end event
