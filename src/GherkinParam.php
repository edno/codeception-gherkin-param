<?php

declare(strict_types=1);

/**
 * Before step hook that provide parameter syntax notation
 * for accessing fixture data between Gherkin steps/tests
 * example:
 *  I see "{{param}}"
 *  {{param}} will be replaced by the value of Fixtures::get('param')
 *
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
    * Suppress CamelCaseMethodName warning
    * Camel case methods are part of the Codeception Extension API lifecyle
    * @SuppressWarnings(PHPMD.CamelCaseMethodName)
   */
class GherkinParam extends \Codeception\Module
{
 
  /**
   * @var boolean Flag to enable exception (prioritized over $nullable=true)
   * false: no exception thrown if parameter invalid, instead replacement value is parameter {{name}} 
   * true: exception thrown if parameter invalid
   */
  private $throwException = false;

  /**
   * @var boolean Flag to null invalid parameter (incompatible with $throwException=true)
   * true: if parameter invalid then replacement value will be null
   * false: default behaviour, ie replacement value is parameter {{name}} 
   */
  private $nullable = false;

  protected $config = ['onErrorThrowException', 'onErrorNull'];

  protected $requiredFields = [];

  /**
   * @var array List events to listen to
   */
  public static $events = [
    //run before any suite
    'suite.before' => 'beforeSuite',
    //run before any steps
    'step.before' => 'beforeStep'
  ];

  /**
   * @var array Current test suite config
   */
  private static $suiteConfig;

  /**
   * @var array RegExp for parsing steps
   */
  private static $regEx = [
    'match'  => '/{{\s?[A-z0-9_:-<>]+\s?}}/',
    'filter' => '/[{}]/',
    'config' => '/(?:^config)?:([A-z0-9_-]+)+(?=:|$)/',
    'array'  => '/^(?P<var>[A-z0-9_-]+)(?:\[(?P<key>.+)])$/'
  ];

  /**
   * Initialize module configuration
   */
  final public function _initialize() 
  {
    if (isset($this->config['onErrorThrowException'])) {
      $this->throwException = (bool) $this->config['onErrorThrowException'];
    }

    if (isset($this->config['onErrorNull'])) {
      $this->nullable = (bool) $this->config['onErrorNull'];
    }
  }

  /**
   * Dynamic module reconfiguration
   */
  final public function onReconfigure()
  {
    $this->_initialize();
  }

  /**
   * Parse param and replace {{.*}} by its Fixtures::get() value if exists
   *
   * @param string $param
   *
   * @return \mixed|null Returns parameter's value if exists, else parameter's name
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  final protected function getValueFromParam(string $param)
  {
    if (preg_match_all(self::$regEx['match'], $param, $matches)) {
      try {
        $values = [];
        $matches = $matches[0]; // override for readability
        foreach ($matches as $variable) {
          $variable = trim(preg_filter(self::$regEx['filter'], '', $variable));
          // config case
          if (preg_match(self::$regEx['config'], $variable)) {
            $values[] = $this->getValueFromConfig($variable);
            break;
          } 
          // array case
          elseif (preg_match(self::$regEx['array'], $variable)) {
            try {
              $values[] = $this->getValueFromArray($variable);
            } catch (RuntimeException $e) {
              if ($this->throwException) throw new GherkinParamException();
              if ($this->nullable) $values[] = null;
            }
            break;
          } 
          // normal case
          try {
            $values[] = Fixtures::get($variable);
          } catch (RuntimeException $e) {
            if ($this->throwException) throw new GherkinParamException();
            if ($this->nullable) $values[] = null;
          }
          // if matching value return is not found (null)
          if (is_null(end($values))) {
            if ($this->throwException) throw new GherkinParamException();
          }
        }

        // array str_replace cannot be used 
        // due to the default behavior when `search` and `replace` arrays size mismatch
        $param = $this->mapParametersToValues($matches, $values, $param);

      } catch (GherkinParamException $e) {
        // only active if throwException setting is true
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
   * @param array $matches
   * @param array $values
   * @param string $param
   *
   * @return \mixed|null Returns parameter's value if exists, else parameter {{name}}
   */  
  //TODO: pass param ref to function (&) [performance]
  private function mapParametersToValues(array $matches, array $values, string $param)
  {
    $len = count($matches);
    for ($i = 0; $i < $len; $i++) {
      $search = $matches[$i];
      if (isset($values[$i])) {
        $replacement = $values[$i];
        if (is_array($replacement)) { 
          // case of replacement is an array (case of config param), ie param does not exists
          if ($this->throwException) {
            throw new GherkinParamException();
          }
          if ($this->nullable) {
            return null;
          }
          break;
        }
        //TODO: replace str_replace by strtr (performance)
        return str_replace($search, strval($replacement), $param);
      }

      if ($this->throwException) {
        throw new GherkinParamException();
      }
      if ($this->nullable) {
        return null;
      }

    }
    return $param;
  }

  /**
   * Retrieve param value from current suite config
   *
   * @param string $param
   *
   * @return \mixed|null Returns parameter's value if exists, else null
   */
  //TODO: pass param ref to function (&) [performance]
  final protected function getValueFromConfig(string $param)
  {
    $value = null;
    $config = self::$suiteConfig;

    preg_match_all(self::$regEx['config'], $param, $args, PREG_PATTERN_ORDER);
    foreach ($args[1] as $arg) {
      if (array_key_exists($arg, $config)) {
        $value = $config[$arg];
        if (is_array($value)) {
          $config = $value;
        }
        return $value;
      }
    }
    return $value;
  }

  /**
   * Retrieve param value from array in Fixtures
   *
   * @param string $param
   *
   * @return \mixed|null Returns parameter's value if exists, else null
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  //TODO: pass param ref to function (&) [performance]
  final protected function getValueFromArray(string $param)
  {
    $value = null;

    preg_match_all(self::$regEx['array'], $param, $args);
    $array = Fixtures::get($args['var'][0]);
    if (array_key_exists($args['key'][0], $array)) {
      $value = $array[$args['key'][0]];
    }
    return $value;
  }

  /**
   * Capture suite's config before any execution
   *
   * @param array $settings
   * @return void
   *
   * @codeCoverageIgnore
   * @ignore Codeception specific
   */
  final public function _beforeSuite($settings = [])
  {
    self::$suiteConfig = $settings;
  }

  /**
   * Parse scenario's step before execution
   *
   * @param \Codeception\Step $step
   * @return void
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
            $table[$i][$j] = $val ? $val : null; // issue TableNode does not support `null` values in table
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
