@local @local_aihub
Feature: Manage personal AI keys
  In order to use AI features with my own quota
  As a user allowed to bring my own key
  I need to store a personal API key without it being shown back to me

  Background:
    Given the following config values are set as admin:
      | enablepersonalkeys | 1 | local_aihub |
    And I log in as "admin"
    And I am on the My AI keys page

  Scenario: A provider starts unconfigured
    Then I should see "Not configured"

  Scenario: Saving a personal key marks it configured without revealing the value
    When I set the field "key_gemini" to "secret-gemini-key"
    And I press "Save"
    Then I should see "Your AI key settings were saved."
    And I should see "Configured"
    And the field "key_gemini" matches value ""
