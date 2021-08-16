<?php

use \Codeception\Exception\ExtensionException;

use \Codeception\Util\Fixtures;

class GherkinParamExceptionTest extends \Codeception\Test\Unit
{
    use \Codeception\AssertThrows;
    use \Codeception\Specify;

    /**
     * @var \UnitTester
     */
    protected $module;
    protected $fixture;
    
    protected function _before(): void
    {        
        $module = $this->getModule('Codeception\Extension\GherkinParam');
        $module->_reconfigure(array('onErrorThrowException' => true));
        $this->module = Mockery::mock($module)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $this->fixture = Mockery::mock(Fixtures::class)
            ->makePartial();
    }

    protected function _after(): void
    {
    }

    public function testGetValueFromParamWithExceptionFromConfig()
    {
        $this->specify(
            'getValueFromConfig', function () {
                $this->assertThrows(
                    ExtensionException::class, function () {
                        $this
                            ->module
                            ->getValueFromParam('{{config:undefinedConfig}}');
                    }
                );
            }
        );
    }

    public function testGetValueFromParamWithExceptionFromArray()
    {
        $this->specify(
            'getValueFromArray', function () {
                $this->assertThrows(
                    ExtensionException::class, function () {
                        $this
                            ->module
                            ->getValueFromParam('{{undefinedArray[4]}}');
                    }
                );
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
}