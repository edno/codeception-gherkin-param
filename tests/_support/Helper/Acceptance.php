<?php
namespace Helper;

use Behat\Gherkin\Node\TableNode;
use Codeception\Util\Fixtures;

class Acceptance extends \Codeception\Module
{
    /**
     * @When /I have (parameters)?/
     */
     public function iHaveParams(TableNode $table)
     {
         foreach ($table->getRows() as $idx => $row) {
           if ($idx == 0) continue;
           Fixtures::add($row[0], $row[1]);
         }
     }
}
