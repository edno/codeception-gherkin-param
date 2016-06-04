# Gherkin Param

[![Latest Version](https://img.shields.io/packagist/v/edno/codeception-gherkin-param.svg?style=flat-square)](https://packagist.org/packages/edno/codeception-gherkin-param)
[![Build Status](https://img.shields.io/travis/edno/codeception-gherkin-param.svg?style=flat-square)](https://travis-ci.org/edno/codeception-gherkin-param)
[![SensioLabs Insight](https://img.shields.io/sensiolabs/i/ae1cb40a-a1de-4a31-a572-3bc85e2f2d99.svg?style=flat-square)](https://insight.sensiolabs.com/projects/ae1cb40a-a1de-4a31-a572-3bc85e2f2d99)
[![GitHub license](https://img.shields.io/badge/license-Apache%202-blue.svg?style=flat-square)](https://raw.githubusercontent.com/edno/codeception-gherkin-param/master/LICENSE)

The [Codeception](http://codeception.com/) extension for supporting parameter notation
in [Gherkin](https://github.com/Codeception/Codeception/blob/master/docs/07-BDD.md)
scenario.

## Minimum Requirements

- Codeception 2.2
- PHP 5.4

## Installation 
The extension can be installed using [Composer](https://getcomposer.org)

```bash
$ composer require edno/codeception-gherkin-param
```

Be sure to enable the extension in `codeception.yml` as shown in
[configuration](#configuration) below.
## Configuration
Enabling **Gherkin Param** is done in `codeception.yml`.

```yaml
extensions:
    enabled:
        - Codeception\Extension\GherkinParam
```

## Usage
Once installed you will be able to access variables stored using 
[Fixtures](http://codeception.com/docs/reference/Fixtures).  
In scenario steps, the variables can be accessed using the syntax `{{param}}`.  
While executing your features the variables will be automatically replaced by their value.

### Example
```gherkin
Feature: Parametrize Gherkin Feature
  In order to create dynamic Gherkin scenario
  As a tester
  I need to be able to share data between scenario steps

  Scenario: Scenario using simple parameter
    Given I have a parameter "test" with value "42"
    Then I should see "{{test}}" equals "42"
```
The steps definition in `AcceptanceTester.php` do not require any change
```php
/**
 * @Given I have a parameter :param with value :value
 */
 public function iHaveAParameterWithValue($param, $value)
 {
   Fixtures::add($param, $value);
 }

/**
 * @Then I should see :arg1 equals :arg2
 */
 public function iSeeEqual($arg1, $arg2)
 {
   $this->assertEquals($arg1, $arg2);
 }
```
 You can find more examples in the [test folder](https://github.com/edno/codeception-gherkin-param/tree/master/tests/acceptance).
