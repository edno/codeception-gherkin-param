<?php

use \Behat\Gherkin\Node\TableNode;
use \Codeception\Step;

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

        $this->module = Mockery::spy($moduleInstance);
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function _after(): void
    {
        Mockery::close();
    }

    public function testBeforeStepCallsGetValueFromParamWhenStepParamIsString(): void
    {
        $step = new Step\Action('test', ['test']);

        $this->module->_beforeStep($step);

        $this->module->shouldHaveReceived('getValueFromParam', ['test']);
    }

    public function testBeforeStepCallsParseTableNodeWhenStepParamIsTableNode(): void
    {
        $param = new TableNode([1 => ['foo', 'bar', 'baz']]);
        $step = new Step\Action('test', [$param]);

        $this->module->_beforeStep($step);

        $this->module->shouldHaveReceived('parseTableNode', [$param]);
    }

    public function testBeforeStepIteratesGetValueFromParamWhenStepParamIsArray(): void
    {
        $param = ['foo', 'bar', 'baz'];
        $step = new Step\Action('test', [$param]);

        $this->module->_beforeStep($step);

        $this->module->shouldHaveReceived('getValueFromParam', ['foo']);
        $this->module->shouldHaveReceived('getValueFromParam', ['bar']);
        $this->module->shouldHaveReceived('getValueFromParam', ['baz']);
    }

    public function testParseTableNodeReturnsValuedTableNode(): void
    {
        $input = new TableNode([1 => ['foo', 'bar', 'baz']]);

        $tableNode = $this->module
            ->parseTableNode($input);

        $this->assertEquals($input, $tableNode);
    }
}
