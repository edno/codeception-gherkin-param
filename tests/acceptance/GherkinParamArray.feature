Feature: Parametrize Gherkin Feature (Array)
  In order to create dynamic Gherkin scenario
  As a tester
  I need to be able to retrieve array parameters

  Scenario: Get array parameters
    Given I have an array "test" with values [1, two, 3.14, IV, 101]
    Then I should see "{{test[0]}}" equals to 1
    And I should see "{{test[1]}}" equals to "two"
    And I should see "{{test[2]}}" equals to 3.14
    And I should see "{{test[3]}}" equals to "IV"
    And I should see "{{test[4]}}" equals to "101"

  Scenario: Key not exist (exception)
    Given I have an array "test" with values [1, two, 3.14, IV, 101]
    Then I should see "{{test[9999]}}" is null
