@local @local_aihub
Feature: Choose the order the AI providers are tried in
  In order to send requests first to the provider that answers best for my site
  As an administrator
  I need to drag the providers into the order they are tried in

  Background:
    Given I log in as "admin"
    And I am on the AI provider order page

  Scenario: The order starts as the built-in one
    Then "Gemini" "text" should appear before "Groq" "text" in the ".local-aihub-providerorder" "css_element"
    And "Groq" "text" should appear before "DeepSeek" "text" in the ".local-aihub-providerorder" "css_element"
    And I should not see "Back to automatic"

  Scenario: Saving without touching the list keeps the order automatic
    When I press "Save order"
    Then I should see "The provider order was saved."
    And I should not see "Back to automatic"

  @javascript
  Scenario: Moving a provider from the keyboard dialogue saves the site's order
    When I click on "[data-provider='deepseek'] [data-drag-type='move']" "css_element"
    And I click on "To the top of the list" "link"
    And I press "Save order"
    Then I should see "The provider order was saved."
    And I should see "Back to automatic"
    And "DeepSeek" "text" should appear before "Gemini" "text" in the ".local-aihub-providerorder" "css_element"

  @javascript
  Scenario: Going back to automatic restores the built-in order
    When I click on "[data-provider='deepseek'] [data-drag-type='move']" "css_element"
    And I click on "To the top of the list" "link"
    And I press "Save order"
    And I press "Back to automatic"
    Then I should not see "Back to automatic"
    And "Gemini" "text" should appear before "DeepSeek" "text" in the ".local-aihub-providerorder" "css_element"
