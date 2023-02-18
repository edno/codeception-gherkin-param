<?php

declare(strict_types=1);

/**
 * Codeception extension for supporting parameter notation in Gherkin scenario.
 *
 * PHP version 7
 *
 * @category Test
 * @package  GherkinParam
 * @author   Gregory Heitz <edno@edno.io>
 * @license  https://git.io/Juy0k Apache License
 * @link     https://packagist.org/packages/edno/codeception-gherkin-param
 */

/**
 * Before step hook that provide parameter syntax notation
 * for accessing fixture data between Gherkin steps/tests
 * example:
 *  I see "{{param}}"
 *  {{param}} will be replaced by the value of Fixtures::get('param')
 */
namespace Codeception\Extension;

use \ReflectionProperty;
use \RuntimeException;
use \TypeError;
use \Behat\Gherkin\Node\TableNode;
use \Codeception\Util\Fixtures;
use \Codeception\Exception\ExtensionException;
use \Codeception\Configuration;
use \Codeception\Step;
use \Codeception\Lib\ModuleContainer;
use \Codeception\Extension\GherkinParamException;
use \Codeception\Exception\Warning;

/**
 * GherkinParam extension main class
 *
 * @category Test
 * @package  GherkinParam
 * @author   Gregory Heitz <edno@edno.io>
 * @license  https://git.io/Juy0k Apache License
 * @link     https://packagist.org/packages/edno/codeception-gherkin-param
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.CamelCaseVariableName)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */

class GherkinParam extends \Codeception\Module
{
    /**
    * List events to listen to
    *
    * @var array<string,string>
    */
    public static array $events = [
    //run before any suite
    'suite.before' => 'beforeSuite',
    //run before any steps
    'step.before' => 'beforeStep'
    ];

    /**
    * Current test suite config
    *
    * @var array<mixed>
    */
    private static $_suiteConfig;

    /**
     * Flag to enable exception (prioritized over $_nullable=true)
     *
     * @var boolean
     * false: no exception thrown if parameter invalid
     *        instead replacement value is parameter {{name}}
     * true: exception thrown if parameter invalid
     */
    private bool $_throwException = false;

    /**
     * Flag to null invalid parameter (incompatible with $_throwException=true)
     *
     * @var boolean
     * true: if parameter invalid then replacement value will be null
     * false: default behavior, ie replacement value is parameter {{name}}
     */
    private bool $_nullable = false;

    /**
     * RegExp for parsing steps
     *
     * @var array<string,string>
     */
    private static array $_regEx = [
    'match'  => '/{{\s?[A-z0-9_:-<>]+\s?}}/',
    'filter' => '/[{}]/',
    'config' => '/(?:^config)?:([A-z0-9_-]+)+(?=:|$)/',
    'array'  => '/^(?P<var>[A-z0-9_-]+)(?:\[(?P<key>.+)])$/'
    ];

    /**
     * Initialize module configuration
     *
     * @return void
     *
     * @phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
     */
    final public function _initialize(): void
    {
        if (isset($this->config['onErrorThrowException'])) {
            $this->_throwException
                = (bool) $this->config['onErrorThrowException'];
        }
        if (isset($this->config['onErrorNull'])) {
            $this->_nullable
                = (bool) $this->config['onErrorNull'];
        }
    }

    /**
     * Dynamic module reconfiguration
     *
     * @return void
     */
    final public function onReconfigure(): void
    {
        $this->_initialize();
    }

    /**
     * Parse param and replace {{.*}} by its Fixtures::get() value if exists
     *
     * @param string $param Fixture entry name
     *
     * @return mixed Returns parameter's value if exists, else parameter's name
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    final protected function getValueFromParam(string $param)
    {
        $variable = null;

        $hasParam = (bool) preg_match_all(
            self::$_regEx['match'],
            $param,
            $matches
        );

        if ($hasParam === false) {
            return $param;
        }

        try {
            $values = [];
            $matches = $matches[0]; // override for readability
            foreach ($matches as $match) {
                $variable = trim(
                    strval(
                        preg_filter(
                            self::$_regEx['filter'],
                            '',
                            "${match}"
                        )
                    )
                );

                // config case
                if (preg_match(self::$_regEx['config'], $variable)) {
                    $values[] = $this->getValueFromConfigParam($variable);
                    continue;
                }

                // array case
                if (preg_match(self::$_regEx['array'], $variable)) {
                    $values[] = $this->getValueFromArrayParam($variable);
                    continue;
                }

                // normal case
                $values[] = $this->getValueFromFixture($variable);

                // if matching value return is not found (null)
                if (is_null(end($values)) && $this->_throwException) {
                    throw new GherkinParamException();
                }
            }

            // array str_replace or strtr cannot be used
            // due to the default behavior when `search`
            // and `replace` arrays size mismatch
            $param = $this->mapParametersToValues($matches, $values, $param);

        } catch (GherkinParamException $exception) {
            // only active if _throwException setting is true
            throw new ExtensionException(
                $this,
                <<<EOT
                Incorrect parameter `${param}`
                variable `${variable}` not found,
                or not initialized"
                EOT
            );
        }

        return $param;
    }

    /**
     * Replace parameters' matches by corresponding values
     *
     * @param array<mixed> $matches Array of matches
     * @param array<mixed> $values  Array of replacement values
     * @param string       $param   Parameter name
     *
     * @return mixed Returns value if exists, else parameter {{name}}
     */
    final protected function mapParametersToValues(
        array $matches,
        array $values,
        string $param
    ) {
        $len = count($matches);
        for ($i = 0; $i < $len; $i++) {
            if (isset($values[$i]) === false || is_array($values[$i])) {
                // case of replacement is an array (case of config param)
                // ie param does not exists
                if ($this->_throwException) {
                    throw new GherkinParamException();
                }
                if ($this->_nullable) {
                    $param = null;
                }
                break;
            }

            $param = str_replace($matches[$i], strval($values[$i]), $param);
        }

        return $param;
    }

    /**
     * Retrieve param value from current suite config
     *
     * @param string $param Configuration entry name
     *
     * @return mixed Returns parameter's value if exists, else null
     */
    final protected function getValueFromConfigParam(
        string $param
    ) {
        $value = null;
        $suiteConfig = self::$_suiteConfig;

        preg_match_all(
            self::$_regEx['config'],
            $param, $args,
            PREG_PATTERN_ORDER
        );

        foreach ($args[1] as $arg) {
            if (array_key_exists($arg, $suiteConfig)) {
                $value = $suiteConfig[$arg];
                if (is_array($value) === false) {
                    return $value;
                }
                $suiteConfig = $value;
            }
        }
        return $value;
    }

    /**
     * Retrieve param value from array fixture
     *
     * @param string $param fixture array notation
     *
     * @return mixed Returns parameter's value if exists, else null
     */
    final protected function getValueFromArrayParam(string $param)
    {
        try {
            return $this->getValueFromArray($param);
        } catch (Warning | RuntimeException | TypeError $exception) {
            if ($this->_throwException) {
                throw new GherkinParamException();
            }
            if ($this->_nullable) {
                return null;
            }
        }
    }

    /**
     * Retrieve param value from fixture variable
     *
     * @param string $param fixture variable name
     *
     * @return mixed Returns parameter's value if exists, else null
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    final protected function getValueFromFixture(string $param)
    {
        try {
            return Fixtures::get($param);
        } catch (RuntimeException $exception) {
            if ($this->_throwException) {
                throw new GherkinParamException();
            }
            if ($this->_nullable) {
                return null;
            }
        }
    }

    /**
     * Retrieve param value from array in Fixtures
     *
     * @param string $param Fixture entry name
     *
     * @return mixed Returns parameter's value if exists, else null
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    final protected function getValueFromArray(string $param)
    {
        $value = null;

        preg_match_all(self::$_regEx['array'], $param, $args);
        $array = Fixtures::get($args['var'][0]);
        if (array_key_exists($args['key'][0], $array)) {
            $value = $array[$args['key'][0]];
        }

        return $value;
    }

    /**
     * Parse a table node by mapping its parameters
     *
     * @param \Behat\Gherkin\Node\TableNode<mixed> $tablenode table node
     *
     * @return \Behat\Gherkin\Node\TableNode<mixed> Returns valued table node
     */
    final protected function parseTableNode(TableNode $tableNode)
    {
        $prop = new ReflectionProperty(get_class($tableNode), 'table');
        $prop->setAccessible(true);
        $table = $prop->getValue($tableNode);
        foreach ($table as $i => $row) {
            foreach ($row as $j => $cell) {
                $val = $this->getValueFromParam($cell);
                // issue TableNode does not support `null` values in table
                $table[$i][$j] = $val;
            }
        }
        $prop->setValue($tableNode, $table);
        $prop->setAccessible(false);

        return $tableNode;
    }

    /**
     * Capture suite's config before any execution
     *
     * @param array<mixed> $settings Codeception test suite settings
     *
     * @return void
     *
     * @codeCoverageIgnore
     * @ignore             Codeception specific
     * @phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
     */
    final public function _beforeSuite($settings = []): void
    {
        self::$_suiteConfig = $settings;
    }

    /**
     * Parse scenario's step before execution
     *
     * @param \Codeception\Step $step Codeception scenario step
     *
     * @return void
     *
     * @phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
     */
    final public function _beforeStep(Step $step): void
    {
        // access to the protected property using reflection
        $refArgs = new ReflectionProperty(get_class($step), 'arguments');
        // change property accessibility to public
        $refArgs->setAccessible(true);
        // retrieve 'arguments' value
        $args = $refArgs->getValue($step);
        foreach ($args as $index => $arg) {
            switch (true) {
            case is_string($arg):
                // e.g. I see "{{param}}"
                $args[$index] = $this->getValueFromParam($arg);
                break;
            case is_a($arg, '\Behat\Gherkin\Node\TableNode'):
                // e.g. I see :
                //  | parameter |
                //  | {{param}} |
                $args[$index] = $this->parseTableNode($arg);
                break;
            case is_array($arg):
                // e.g. I see "{{param[0]}}"
                foreach ($arg as $k => $v) {
                    if (is_string($v)) {
                        $args[$index][$k] = $this->getValueFromParam($v);
                    }
                }
                break;
            default:
                // do nothing
            }
        }
        // set new arguments value
        $refArgs->setValue($step, $args);
    }
}
