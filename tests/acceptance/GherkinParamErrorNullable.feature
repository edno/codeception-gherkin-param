Feature: Handling Parametrized Gherkin Errors
  In order to create dynamic Gherkin scenario
  As a tester
  I want `null` value when the parameter is invalid

@standard
Scenario: Simple parameter does not exist
    Given I do not have a parameter "test"
    Then I should see "{{test}}" is null

@table
Scenario: Scenario parameter does not exist in a table
    Given I do not have a parameter "test"
    Then I should see null:
      | parameter  | is null    |
      | {{ test }} | true       |

@table-with-helper
Scenario: Table with helper and invalid parameter
    Given I do not have a parameter "test"
    When I have parameters
        | parameter | value      |
        | param1    | Fix Helper |
        | param2    | {{ test }} |
    Then I should see "{{param2}}" is null

@array-invalid-name
Scenario: Array does not exist
    Given I do not have an array "test"
    Then I should see "{{test[9999]}}" is null

@array-invalid-key
Scenario: Array with invalid key
    Given I have an array "test" with values [1, two, 3.14, IV, 101]
    Then I should see "{{test[9999]}}" is null

@outline
Scenario Outline: Outline example with parameter that does not exist
    Given I do not have a parameter "parameter"
    Then I should see "<{{ parameter }}>" is null
    And I should see "{{<test>}}" is null
    Examples:
      | parameter | value |
      | param     | 1010  |

@config
Scenario: Config key does not exist
    Given I have a configuration file "codeception.yml"
      """
      actor: Tester

      paths:
        tests: tests
        log: tests/_output
        data: tests/_data
        support: tests/_support
        envs: tests/_envs

      settings:
        bootstrap: _bootstrap.php
        colors: true
        memory_limit: 512M
        my_user: 'a value'

      extensions:
          enabled:
              - Codeception\Extension\GherkinParam
      """
    When I execute a scenario calling the parameter 'config:not_a_param'
    Then I should see "{{ config:not_a_param }}" is null