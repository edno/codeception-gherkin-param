<?php

use \Behat\Gherkin\Node\TableNode;

/**
 * Happy path unit tests
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class GherkinParamTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $module;
    protected $fixture;

    protected function _before(): void
    {
        $moduleInstance = $this->getModule(
            'Codeception\Extension\GherkinParam'
        );
        $this->module = Mockery::mock($moduleInstance)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $this->module = Mockery::spy($moduleInstance)
            ->shouldAllowMockingProtectedMethods();
    }

    public function testParseTableNodeReturnsValuedTableNode(): void
    {
        $input = new TableNode([1 => ["foo", "bar", "baz"]]);

        $tableNode = $this
            ->module
            ->parseTableNode($input);

        $this->assertEquals($input, $tableNode);
    }

}