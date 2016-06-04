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
		//run before any steps
		'step.before' => 'beforeStep'
  );

  public function __construct($config, $options)
  {
    parent::__construct($config, $options);
  }

  // parse param and replace {{.*}} by its Fixtures::get() value if exists
  protected function getValueFromParam($param)
  {
    // set regexp
    $arRegEx = Array('match' => '/^{{\w+}}$/', 'filter' => '/[{}]/');
    if (preg_match($arRegEx['match'], $param)) {
      $arg = preg_filter($arRegEx['filter'], '', $param);
      return Fixtures::get($arg);
    } else {
      return $param;
    }
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
