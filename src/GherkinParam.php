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

use Codeception\Util\Fixtures;
use Behat\Gherkin\Node\TableNode;
use ReflectionProperty;

class GherkinParam extends \Codeception\Extension
{
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
    'match'  => '/{{\s?[A-z0-9_:-]+\s?}}/',
    'filter' => '/[{}]/',
    'config' => '/(?:^config)?:([A-z0-9_-]+)+(?=:|$)/',
    'array'  => '/^(?P<var>[A-z0-9_-]+)(?:\[(?P<key>.+)])$/'
  ];

  /**
   * Parse param and replace {{.*}} by its Fixtures::get() value if exists
   *
   * @param string $param
   *
   * @return \mixed|null Returns parameter's value if exists, else parameter's name
   */
  final protected function getValueFromParam(string $param)
  {
    if (preg_match_all(self::$regEx['match'], $param, $variables)){
      $values = [];
      foreach ($variables[0] as $variable)
      {
        $variableName = trim(preg_filter(self::$regEx['filter'], '', $variable));
        if (preg_match(self::$regEx['config'], $variableName)) {
          $values[] = $this->getValueFromConfig($variableName);
        } elseif (preg_match(self::$regEx['array'], $variableName)) {
          $values[] = $this->getValueFromArray($variableName);
        } else {
          $values[] = Fixtures::get($variableName);
        }
      }
      $param = str_replace($variables[0], $values, $param);
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
        } else {
          break;
        }
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
   */
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
   * @param \Codeception\Event\SuiteEvent $e
   * @return void
   *
   * @codeCoverageIgnore
   * @ignore Codeception specific
   */
  final public function beforeSuite(\Codeception\Event\SuiteEvent $e)
  {
    self::$suiteConfig = $e->getSettings();
  }

  /**
   * Parse scenario's step before execution
   *
   * @param \Codeception\Event\StepEvent $e
   * @return void
   */
  final public function beforeStep(\Codeception\Event\StepEvent $e)
  {
    $step = $e->getStep();
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
      //  | paramater |
      //  | {{param}} |
        $table = [];
        foreach ($arg->getRows() as $i => $row) {
          foreach ($row as $j => $cell) {
              $table[$i][$j] = $this->getValueFromParam($cell);
          }
        }
        $args[$index] = new TableNode($table);
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
