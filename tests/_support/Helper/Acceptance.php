<?php
namespace Helper;

use Behat\Gherkin\Node\TableNode;
use Codeception\Util\Fixtures;

/**
 * Helper for acceptance tests
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class Acceptance extends \Codeception\Module
{
    /**
     * @When /I have parameters/
     */
    public function iHaveParams(TableNode $table): void
    {
        foreach ($table->getRows() as $idx => $row) {
            if ($idx == 0) {
                continue;
            }
            Fixtures::add($row[0], $row[1]);
        }
    }

    /**
     * @When /^I have an array "(\w+)" with values \[(.+)]$/i
     */
    public function iHaveArray(string $var, string $values): void
    {
        $array = preg_split('/,\s?/', $values);
        Fixtures::add($var, $array);
    }

    /**
     * @When /^I do not have an array "(\w+)"/i
     */
    public function iDoNotHaveArray(string $var): void
    {
        // Do nothing with $var
    }
}
