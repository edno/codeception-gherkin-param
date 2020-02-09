Feature: Handling Parametrized Gherkin Errors Defaulted
  In order to create dynamic Gherkin scenario
  As a tester
  I need to have consistent behaviour for error cases

Background:
  Given The configuration parameter "onErrorThrowException" is set to 0
  And The configuration parameter "onErrorNullabe" is set to 0

@standard
Scenario: Simple parameter does not exist
    Given I do not have a parameter "notaparam"
    Then I should see "{{notaparam}}" equals "{{notaparam}}"

@table
Scenario: Scenario parameter does not exist in a table
    Given I do not have a parameter "notaparam"
    Then I should see following:
      | parameter  | equals to  |
      | {{ notaparam }} | {{ notaparam }} |

@table-with-helper
Scenario: Table with helper and invalid parameter
    Given I do not have a parameter "notaparam"
    When I have parameters
        | parameter | value      |
        | param1    | Fix Helper |
        | param2    | {{ notaparam }} |
    Then I should see "{{param2}}" equals "{{ notaparam }}"

@array-invalid-name
Scenario: Array does not exist
    Given I do not have an array "notaparam"
    Then I should see "{{notaparam[9999]}}" equals "{{notaparam[9999]}}"

@array-invalid-key
Scenario: Array with invalid key
    Given I have an array "test" with values [1, two, 3.14, IV, 101]
    Then I should see "{{test[9999]}}" equals "{{test[9999]}}"

@outline
Scenario Outline: Outline example with parameter that does not exist
    Given I do not have a parameter "notaparam"
    Then I should see "<{{ notaparam }}>" equals "<{{ notaparam }}>"
    And I should see "{{<notaparam>}}" equals "{{<notaparam>}}"

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
    Then I should see "{{ config:not_a_param }}" equals "{{ config:not_a_param }}"