Feature: Handling Parametrized Gherkin Errors Nullable
  In order to create dynamic Gherkin scenario
  As a tester
  I want `null` value when the parameter is invalid

Background:
  Given The configuration parameter "onErrorNull" is set to 1
  And The configuration parameter "onErrorThrowException" is set to 0

@standard
Scenario: Simple parameter null when it does not exist
    Given I do not have a parameter "notaparam"
    Then I should see "{{notaparam}}" is null

@table
Scenario: Scenario parameter null when it does not exist in a table
    Given I do not have a parameter "notaparam"
    Then I should see null:
      | parameter  | is null    |
      | {{ notaparam }} | true       |

@table-with-helper
Scenario: Table with helper and invalid parameter null when it does not exist
    Given I do not have a parameter "notaparam"
    When I have parameters
        | parameter | value      |
        | param1    | Fix Helper |
        | param2    | {{ notaparam }} |
    Then I should see "{{param2}}" is null

@array-invalid-name
Scenario: Array parameter null when it does not exist
    Given I do not have an array "notaparam"
    Then I should see "{{notaparam[9999]}}" is null

@array-invalid-key
Scenario: Array with invalid key null when it does not exist
    Given I have an array "test" with values [1, two, 3.14, IV, 101]
    Then I should see "{{test[9999]}}" is null

@outline
Scenario Outline: Outline example with parameter null when it does not exist
    Given I do not have a parameter "notaparam"
    Then I should see "<{{ notaparam }}>" is null
    And I should see "{{<notaparam>}}" is null
    Examples:
      | parameter | value |
      | param     | 1010  |

@config
Scenario: Config key null when does not exist
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