@tool @tool_usergenerator
Feature: Basic tests for User generator

  @javascript
  Scenario: Plugin tool_usergenerator appears in the list of installed additional plugins
    Given I log in as "admin"
    When I navigate to "Plugins > Plugins overview" in site administration
    And I follow "Additional plugins"
    Then I should see "User generator"
    And I should see "tool_usergenerator"

  @javascript
  Scenario: Generating users with the tool usergenerator
    Given I log in as "admin"
    When I navigate to "Development > User generator" in site administration
    And I set the following fields to these values:
      | Number of users to generate  | 5   |
      | Username prefix              | wow |
      | First index in the user name | 2   |
    And I press "Generate users"
    And the following should exist in the "generaltable" table:
      | Username | Email address    |
      | wow2     | wow2@example.com |
      | wow3     | wow3@example.com |
      | wow4     | wow4@example.com |
      | wow5     | wow5@example.com |
      | wow6     | wow6@example.com |
    And I should not see "wow1"
    And I should not see "wow7"
    And I set the following fields to these values:
      | Number of users to generate  | 5   |
      | Username prefix              | wow |
      | First index in the user name | 4   |
    And I press "Generate users"
    And I should see "User(s) wow4, wow5, wow6 already exist. Choose another prefix or another first index."
