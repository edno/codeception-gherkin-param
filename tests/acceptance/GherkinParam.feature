Feature: Parametrize Gherkin Feature
  In order to create dynamic Gherkin scenario
  As a tester
  I need to be able to share data between scenario steps

  Scenario: Scenario using simple parameter
    Given I have a parameter "test" with value "42"
    Then I should see "{{test}}" equals "42"
    Then I should see "{{ test }}" equals "42"

  Scenario: Scenario with multiple values
    Given I have a parameter "first_param" with value "first"
    Given I have a parameter "second_param" with value "second"
    Then I should see "{{first_param}} and {{second_param}}" equals "first and second"

  Scenario: Scenario using table parameter
    Given I have a parameter "my_param" with value "This is a test"
    And I have a parameter "another_param" with value "3.14"
    Then I should see following:
      | parameter         | equals to      |
      | {{my_param}}      | This is a test |
      | {{ another_param }} | 3.14           |

  Scenario Outline: Scenario using example
    Given I have a parameter "test" with value "parameter"
    And I have a parameter "<parameter>" with value "<value>"
    Then I should see "{{<parameter>}}" equals "<value>"

    Examples:
      | parameter | value |
      | param     | 1010  |

  Scenario: Scenario using table in helper
    Given I have a parameter "test" with value "Table Node"
    And I have a parameter "another_param" with value "3.14"
    When I have parameters
        | parameter | value      |
        | param1    | Fix Helper |
        | param2    | {{test}}   |
        | param3    | {{ another_param }}   |
    Then I should see "{{param2}}" equals "{{test}}"
    Then I should see "{{param3}}" equals "{{another_param}}"

  Scenario: Scenario using JSON string
    Given I have a parameter "test" with value "{'value': 42}"
    Then I should see "{{test}}" equals "{'value': 42}"

  Scenario: Using parameter as value of associative array
    Given I have a parameter "shape" with value "triangle"
    And I have a parameter "color" with value "blue"
    And I have a parameter "shapes" with values
      | shape     | color     |
      | circle    | green     |
      | square    | yellow    |
      | {{shape}} | {{color}} |
    Then I should see "shapes" with values
      | shape    | color  |
      | circle   | green  |
      | square   | yellow |
      | triangle | blue   |
