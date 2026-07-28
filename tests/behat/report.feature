@local @local_aihub
Feature: Review AI usage served by the site keys
  In order to know whether the shared quota is being spent and whether a provider is broken
  As an administrator
  I need a report of every provider call, including the ones that failed

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | teacher1 | Ana       | Silva    | ana@example.com   |
    And the following AI usage entries exist:
      | user     | component      | description | provider | model               | success | errormessage           |
      | teacher1 | mod_codereview | Review      | Gemini   | gemini-flash-latest | 0       | Gemini: quota exceeded |
      | teacher1 | mod_codereview | Review      | Groq     | openai/gpt-oss-120b | 1       |                        |
    And I log in as "admin"
    And I am on the site AI usage report page

  Scenario: Both attempts of one request are listed, with the reason the first failed
    Then I should see "Site AI usage report"
    And I should see "Gemini"
    And I should see "Failed"
    And I should see "Gemini: quota exceeded"
    And I should see "Groq"
    And I should see "Succeeded"

  Scenario: The failures filter hides the attempts that worked
    When I follow "Only failures"
    Then I should see "Gemini: quota exceeded"
    And I should not see "openai/gpt-oss-120b"

  Scenario: The filter returns to the full list
    Given I follow "Only failures"
    When I follow "All attempts"
    Then I should see "openai/gpt-oss-120b"

  Scenario: A request made outside a session is attributed rather than shown as an id
    Given the following AI usage entries exist:
      | user  | component      | description      | provider | success |
      | admin | mod_codereview | Scheduled review | Groq     | 1       |
    When I am on the site AI usage report page
    Then I should see "Scheduled review"

  Scenario: The report downloads as CSV
    Then following "Download CSV" should download between "100" and "10000" bytes

  Scenario: The report downloads as Excel
    Then following "Download Excel" should download between "1000" and "200000" bytes
