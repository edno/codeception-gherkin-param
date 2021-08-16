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
 * @license  https://github.com/edno/codeception-gherkin-param/blob/main/LICENSE Apache Licence
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

use \Codeception\Util\Fixtures;
use \Behat\Gherkin\Node\TableNode;
use \ReflectionProperty;
use \RuntimeException;
use \Codeception\Exception\ExtensionException;
use \Codeception\Configuration;
use \Codeception\Step;
use \Codeception\Extension\GherkinParamException;

/**
 * GherkinParam extension main class
 *
 * @category Test
 * @package  GherkinParam
 * @author   Gregory Heitz <edno@edno.io>
 * @license  https://github.com/edno/codeception-gherkin-param/blob/main/LICENSE Apache Licence
 * @link     https://packagist.org/packages/edno/codeception-gherkin-param
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.CamelCaseVariableName)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class GherkinParam extends \Codeception\Module
{
 
    /**
     * Flag to enable exception (prioritized over $_nullable=true)
     *
     * @var boolean
     * false: no exception thrown if parameter invalid
     *        instead replacement value is parameter {{name}} 
     * true: exception thrown if parameter invalid
     */
    private $_throwException = false;

    /**
     * Flag to null invalid parameter (incompatible with $_throwException=true)
     *
     * @var boolean
     * true: if parameter invalid then replacement value will be null
     * false: default behaviour, ie replacement value is parameter {{name}} 
     */
    private $_nullable = false;

    protected $config = ['onErrorThrowException', 'onErrorNull'];

    protected $requiredFields = [];

    /**
     * List events to listen to
     *
     * @var array
     */
    public static $events = [
    //run before any suite
    'suite.before' => 'beforeSuite',
    //run before any steps
    'step.before' => 'beforeStep'
    ];

    /**
     * Current test suite config
     *
     * @var array 
     */
    private static $_suiteConfig;

    /**
     * RegExp for parsing steps
     *
     * @var array
     */
    private static $_regEx = [
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
    final public function _initialize() 
    {
        if (isset($this->config['onErrorThrowException'])) {
            $this->_throwException = (bool) $this->config['onErrorThrowException'];
        } 
        if (isset($this->config['onErrorNull'])) {
            $this->_nullable = (bool) $this->config['onErrorNull'];
        }
    }

    /**
     * Dynamic module reconfiguration
     *
     * @return void
     */
    final public function onReconfigure()
    {
        $this->_initialize();
    }

    /**
     * Parse param and replace {{.*}} by its Fixtures::get() value if exists
     *
     * @param string $param Fixture entry name
     *
     * @return \mixed|null Returns parameter's value if exists, else parameter's name
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    final protected function getValueFromParam(string $param)
    {
        if (preg_match_all(self::$_regEx['match'], $param, $matches)) {
            try {
                $values = [];
                $matches = $matches[0]; // override for readability
                foreach ($matches as $variable) {
                    $variable = trim(
                        preg_filter(self::$_regEx['filter'], '', $variable)
                    );
                    // config case
                    if (preg_match(self::$_regEx['config'], $variable)) {
                        $values[] = $this->getValueFromConfig($variable);
                        continue;
                    } elseif (preg_match(self::$_regEx['array'], $variable)) {
                        // array case
                        try {
                              $values[] = $this->getValueFromArray($variable);
                        } catch (RuntimeException $exception) {
                            if ($this->_throwException) { 
                                throw new GherkinParamException();
                            } 
                            if ($this->_nullable) { 
                                $values[] = null;
                            }
                        }
                        continue;
                    } 
                    // normal case
                    try {
                        $values[] = Fixtures::get($variable);
                    } catch (RuntimeException $exception) {
                        if ($this->_throwException) { 
                            throw new GherkinParamException();
                        }
                        if ($this->_nullable) { 
                            $values[] = null;
                        }
                    }
                    // if matching value return is not found (null)
                    if (is_null(end($values))) {
                        if ($this->_throwException) {
                            throw new GherkinParamException();
                        }
                    }
                }

                // array str_replace cannot be used 
                // due to the default behavior when `search` 
                // and `replace` arrays size mismatch
                $param = $this->mapParametersToValues($matches, $values, $param);

            } catch (GherkinParamException $exception) {
                // only active if _throwException setting is true
                throw new ExtensionException(
                    $this, 
                    "Incorrect parameter `${param}` variable `${variable}` not found, or not initialized"
                );
            }
    
        }

        return $param;
    }

    /**
     * Replace parameters' matches by corresponding values
     *
     * @param array  $matches Array of matches
     * @param array  $values  Array of replacement values
     * @param string $param   Parameter name
     *
     * @return \mixed|null Returns value if exists, else parameter {{name}}
     *
     * @todo pass param ref to function (&) [performance]
     */  
    final protected function mapParametersToValues(
        array $matches, 
        array $values, 
        string $param
    ) {
        $len = count($matches);
        for ($i = 0; $i < $len; $i++) {
            $search = $matches[$i];
            if (isset($values[$i])) {
                $replacement = $values[$i];
                if (is_array($replacement)) { 
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
                //TODO: replace str_replace by strtr (performance)
                $param = str_replace($search, strval($replacement), $param);
                continue;
            } 

            if ($this->_throwException) {
                throw new GherkinParamException();
            } elseif ($this->_nullable) {
                $param = null;
            }
        }
        return $param;
    }

    /**
     * Retrieve param value from current suite config
     *
     * @param string $param Configuration entry name
     *
     * @return \mixed|null Returns parameter's value if exists, else null
     *
     * @todo pass param ref to function (&) [performance]
     */
    final protected function getValueFromConfig(string $param)
    {
        $value = null;
        $config = self::$_suiteConfig;

        preg_match_all(self::$_regEx['config'], $param, $args, PREG_PATTERN_ORDER);
        foreach ($args[1] as $arg) {
            if (array_key_exists($arg, $config)) {
                $value = $config[$arg];
                if (is_array($value)) {
                    $config = $value;
                    continue;
                } 
                    return $value;
                
            }
        }
        return $value;
    }

    /**
     * Retrieve param value from array in Fixtures
     *
     * @param string $param Fixture entry name
     *
     * @return \mixed|null Returns parameter's value if exists, else null
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @todo pass param ref to function (&) [performance]
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
     * Capture suite's config before any execution
     *
     * @param array $settings Codeception test suite settings
     * 
     * @return void
     *
     * @codeCoverageIgnore
     * @ignore             Codeception specific
     * @phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
     */
    final public function _beforeSuite($settings = [])
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
    final public function _beforeStep(Step $step)
    {
        // access to the protected property using reflection
        $refArgs = new ReflectionProperty(get_class($step), 'arguments');
        // change property accessibility to public
        $refArgs->setAccessible(true);
        // retrieve 'arguments' value
        $args = $refArgs->getValue($step);
        foreach ($args as $index => $arg) {
            if (is_string($arg)) {
                // case if arg is a string
                // e.g. I see "{{param}}"
                $args[$index] = $this->getValueFromParam($arg);
            } elseif (is_a($arg, '\Behat\Gherkin\Node\TableNode')) {
                // case if arg is a table
                // e.g. I see :
                //  | parameter |
                //  | {{param}} |
                $prop = new ReflectionProperty(get_class($arg), 'table');
                $prop->setAccessible(true);
                $table = $prop->getValue($arg);
                foreach ($table as $i => $row) {
                    foreach ($row as $j => $cell) {
                        $val = $this->getValueFromParam($cell);
                        // issue TableNode does not support `null` values in table
                        $table[$i][$j] = $val ? $val : null;
                    }
                }
                $prop->setValue($arg, $table);
                $prop->setAccessible(false);
                $args[$index] = $arg;
            } elseif (is_array($arg)) {
                foreach ($arg as $k => $v) {
                    if (is_string($v)) {
                        $args[$index][$k] = $this->getValueFromParam($v);
                    }
                }
            }
        }
        // set new arguments value
        $refArgs->setValue($step, $args);
    }

}
