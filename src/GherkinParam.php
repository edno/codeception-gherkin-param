<?php
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

class GherkinParam extends \Codeception\Platform\Extension
{
  // list events to listen to
  public static $events = array(
    //run before any suite
    'suite.before' => 'beforeSuite',
    //run before any steps
    'step.before' => 'beforeStep'
  );

  private static $suite_config;

  private static $regEx = Array(
                          'match'  => '/^{{[A-z0-9_:-]+}}$/',
                          'filter' => '/[{}]/',
                          'config' => '/(?:^config)?:([A-z0-9_-]+)+(?=:|$)/',
                          'array'  => '/^(?P<var>[A-z0-9_-]+)(?:\[(?P<key>.+)])$/'
                        );

  // parse param and replace {{.*}} by its Fixtures::get() value if exists
  protected function getValueFromParam($param)
  {
    if (preg_match(static::$regEx['match'], $param)) {
      $arg = preg_filter(static::$regEx['filter'], '', $param);
      if (preg_match(static::$regEx['config'], $arg)) {
        return $this->getValueFromConfig($arg);
      } elseif (preg_match(static::$regEx['array'], $arg)) {
        return $this->getValueFromArray($arg);
      }else {
        return Fixtures::get($arg);
      }
    } else {
      return $param;
    }
  }

  protected function getValueFromConfig($param)
  {
    $value = null;
    $config = static::$suite_config;

    preg_match_all(static::$regEx['config'], $param, $args, PREG_PATTERN_ORDER);
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

  protected function getValueFromArray($param)
  {
    $value = null;

    preg_match_all(static::$regEx['array'], $param, $args);
    $array = Fixtures::get($args['var'][0]);
    if (array_key_exists($args['key'][0], $array)) {
        $value = $array[$args['key'][0]];
    } else {
      return null;
    }
    return $value;
  }

  /**
   * @codeCoverageIgnore
   */
  public function beforeSuite(\Codeception\Event\SuiteEvent $e)
  {
    static::$suite_config = $e->getSettings();
  }

  public function beforeStep(\Codeception\Event\StepEvent $e)
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
        $table = Array();
        foreach ($arg->getRows() as $i => $row) {
          foreach ($row as $j => $cell) {
              $table[$i][$j] = $this->getValueFromParam($cell);
          }
        }
        $args[$index] = new TableNode($table);
      }
    }
    // set new arguments value
    $refArgs->setValue($step, $args);
  }

}
