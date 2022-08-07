# Gherkin Param

[![Packagist](https://img.shields.io/packagist/dt/edno/codeception-gherkin-param.svg?style=flat-square)](https://packagist.org/packages/edno/codeception-gherkin-param)
[![Latest Version](https://img.shields.io/packagist/v/edno/codeception-gherkin-param.svg?style=flat-square)](https://packagist.org/packages/edno/codeception-gherkin-param)
[![Build Status](https://img.shields.io/travis/com/edno/codeception-gherkin-param.svg?style=flat-square)](https://app.travis-ci.com/github/edno/codeception-gherkin-param)
[![Coverage Status](https://img.shields.io/coveralls/edno/codeception-gherkin-param.svg?style=flat-square)](https://coveralls.io/github/edno/codeception-gherkin-param?branch=main)
[![Mutation Score](https://img.shields.io/endpoint?label=mutation%20score&style=flat-square&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fedno%2Fcodeception-gherkin-param%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/edno/codeception-gherkin-param/main)
[![License](https://img.shields.io/badge/license-Apache%202-blue.svg?style=flat-square)](https://raw.githubusercontent.com/edno/codeception-gherkin-param/main/LICENSE)
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Fedno%2Fcodeception-gherkin-param.svg?type=shield)](https://app.fossa.io/projects/git%2Bgithub.com%2Fedno%2Fcodeception-gherkin-param)

---

The [Codeception](http://codeception.com/) module for supporting parameter notation
in [Gherkin](https://codeception.com/docs/07-BDD)
scenario.

## Minimum Requirements

- Codeception 3.x, 4.x, 5.x
- PHP 7.4 - 8.2 (use release 2.0.6 for older PHP versions)

## Installation

The module can be installed using [Composer](https://getcomposer.org)

```bash
composer require edno/codeception-gherkin-param --dev
```

Be sure to enable the module in `codeception.yml` as shown in
[configuration](#configuration) below.

## Setup

Enabling **Gherkin Param** is done in `codeception.yml`.

```yaml
modules:
    enabled:
        - Codeception\Extension\GherkinParam
```

## Configuration

> By default **GherkinParam**  behavior is to keep the parameter string unchanged when the replacement value for a parameter cannot be found, i.e., the parameter does not exist or is not accessible.

### `onErrorThrowException`

If `true`, then GherkinParam will throw an exception `GherkinParam` at runtime when a replacement value cannot be found for a parameter:

```yaml
modules:
    enabled:
        - Codeception\Extension\GherkinParam:
            onErrorThrowException: true
```

> If `onErrorThrowException` is set then it will override `onErrorNullable`.

### `onErrorNullable`

If `true`, then GherkinParam will set to `null` parameters for which a replacement value cannot be found:

```yaml
modules:
    enabled:
        - Codeception\Extension\GherkinParam:
            onErrorNullable: true
```

## Usage

Once installed, you will be able to access variables stored using
[Fixtures](https://codeception.com/docs/reference/Fixtures.html).

### Simple parameters

In scenario steps, the variables can be accessed using the syntax `{{param}}`.
While executing your features, the variables will be automatically replaced by their value.

### Array parameters

You can refer to an element in an array using the syntax `{{param[key]}}`.

### Test Suite Config parameters

You can refer to a test suite configuration parameter using the syntax `{{config:param}}`.
Note that the keyword **config:** is mandatory.

## Example

```gherkin
Feature: Parametrize Gherkin Feature
  In order to create a dynamic Gherkin scenario
  As a tester
  I need to be able to share data between scenario steps

  Scenario: Scenario using simple parameter
    Given I have a parameter "test" with value "42"
    Then I should see "{{test}}" equals "42"

  Scenario: Scenario using array parameter
    Given I have an array "test" with values [1, two, 3.14, IV, 101]
    Then I should see "{{test[1]}}" equals "two"

  Scenario: Scenario using config parameter
    Given I have a configuration file "acceptance.suite.yml" containing
      """
      theResponse: 42
      """
    When I execute a scenario calling the parameter 'theResponse'
    Then I should see "{{config:theResponse}}" equals "42"
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

 You can find more examples in the [test folder](https://github.com/edno/codeception-gherkin-param/tree/main/tests/acceptance).

## Contributions

Contributions, issues, and feature requests are very welcome. If you use this package and have fixed a bug for yourself, please consider submitting a PR!

<a href="https://github.com/edno/codeception-gherkin-param/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=edno/codeception-gherkin-param" />
</a>

Made with [contributors-img](https://contrib.rocks).
