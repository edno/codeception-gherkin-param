Feature: Parametrize Gherkin Feature
  In order to create dynamic Gherkin scenario
  As a tester
  I need to be able to retrieve config parameter

  Scenario: Global parameters from codeception.yml
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
        my_param:
          - user: 'mylogin'
          - password: 'mypassword'

      extensions:
          enabled:
              - Codeception\Extension\GherkinParam
      """
    When I execute a scenario calling the parameter 'my_param:user'
    Then I should see "{{config:my_param:user}}" equals "mylogin"

  Scenario: Suite parameters from acceptance.suite.yml
    Given I have a configuration file "acceptance.suite.yml"
      """
      class_name: AcceptanceTester

      modules:
        enabled:
          - Asserts
          - Helper\Acceptance

      some_param: 42
      """
    When I execute a scenario calling the parameter 'some_param'
    Then I should see "{{config:some_param}}" equals "42"
