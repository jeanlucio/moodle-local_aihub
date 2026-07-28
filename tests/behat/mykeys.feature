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

  Scenario: My own history shows why an attempt of mine failed
    Given the following AI usage entries exist:
      | user  | component      | description | provider | keysource | success | errormessage            |
      | admin | mod_codereview | Review      | Gemini   | personal  | 0       | Gemini: invalid API key |
    When I am on the My AI keys page
    Then I should see "Recent AI usage"
    And I should see "Failed"
    And I should see "Gemini: invalid API key"

  Scenario: My history downloads as CSV
    Given the following AI usage entries exist:
      | user  | component      | description | provider | keysource | success |
      | admin | mod_codereview | Review      | Groq     | personal  | 1       |
    When I am on the My AI keys page
    Then following "Download CSV" should download between "100" and "10000" bytes

  Scenario: My history downloads as Excel
    Given the following AI usage entries exist:
      | user  | component      | description | provider | keysource | success |
      | admin | mod_codereview | Review      | Groq     | personal  | 1       |
    When I am on the My AI keys page
    Then following "Download Excel" should download between "1000" and "200000" bytes
