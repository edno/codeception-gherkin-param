<?php

use Codeception\Exception\ExtensionException;
use Codeception\Extension\GherkinParamException;
use Codeception\Util\Fixtures;

class GherkinParamExceptionTest extends \Codeception\Test\Unit
{
    use \Codeception\AssertThrows;

    /**
     * @var \UnitTester
     */
    protected $module;
    protected $fixture;
    
    protected function _before(): void
    {        
        $module = $this->getModule('Codeception\Extension\GherkinParam');
        $module->_reconfigure(['onErrorThrowException' => true]);
        $this->module = Mockery::mock($module)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $this->module = Mockery::spy($module)
            ->shouldAllowMockingProtectedMethods();
    }

    protected function _after(): void
    {
    }

    public function testGetValueFromParamWithExceptionFromConfig()
    {
        $this->assertThrows(
            ExtensionException::class, function () {
                $this
                    ->module
                    ->getValueFromParam('{{config:undefinedConfig}}');
            }
        );
    }

    public function testGetValueFromParamWithExceptionFromArray()
    {
        $this->assertThrows(
            ExtensionException::class, function () {
                $this
                    ->module
                    ->getValueFromParam('{{undefinedArray[4]}}');
            }
        );
    }

    public function testGetValueFromParamWithExceptionFromFixture()
    {
        $this->assertThrows(
            ExtensionException::class, function () {
                $this
                    ->module
                    ->getValueFromParam('{{undefinedValue}}');
            }
        );
    }

    public function testMapParametersToValuesWithExceptionOnIsArray()
    {
        $this->assertThrows(
            GherkinParamException::class, function () {
                $this
                    ->module
                    ->mapParametersToValues(
                        [0,1,2,3,4],
                        [[0],[1],[2],[3],[4]],
                        "test"
                    );
            }
        );
    }

    public function testMapParametersToValuesWithExceptionOnIsSet()
    {
        $this->assertThrows(
            GherkinParamException::class, function () {
                $this
                    ->module
                    ->mapParametersToValues(
                        [0,1,2,3,4],
                        [],
                        "test"
                    );
            }
        );
    }
}
