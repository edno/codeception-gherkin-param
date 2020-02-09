<?php
namespace Helper;

use Behat\Gherkin\Node\TableNode;
use Codeception\Util\Fixtures;

class Acceptance extends \Codeception\Module
{
    /**
     * @When /I have parameters/
     */
     public function iHaveParams(TableNode $table)
     {
         foreach ($table->getRows() as $idx => $row) {
           if ($idx == 0) continue;
           Fixtures::add($row[0], $row[1]);
         }
     }

     /**
      * @When /^I have an array "(\w+)" with values \[(.+)]$/i
      */
      public function iHaveArray(string $var, string $values)
      {
          $array = preg_split('/,\s?/', $values);
          Fixtures::add($var, $array);
      }

      /**
      * @When /^I do not have an array "(\w+)"/i
      */
      public function iDoNotHaveArray(string $var)
      {
          // Do nothing with $var
      }
}
