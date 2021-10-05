<?php
namespace Helper;

class ExtHelper extends \Codeception\Module
{
    /**
     * @When /I have a configuration file(?:.*)/
     */
    public function iHaveConfigDoNothing(): void
    {
        // do nothing
    }

    /**
     * @When /I execute a scenario(?:.*)/
     */
    public function iExecuteScenarioDoNothing(): void
    {
        // do nothing
    }
}
