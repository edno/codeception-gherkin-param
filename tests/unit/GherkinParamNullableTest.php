<?php

class GherkinParamNullableTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $module;
    
    protected function _before(): void
    {        
        $module = $this->getModule('Codeception\Extension\GherkinParam');
        $module->_reconfigure(
            [
            'onErrorThrowException' => false, 
            'onErrorNull' => true
            ]
        );
        $this->module = Mockery::spy($module)
            ->shouldAllowMockingProtectedMethods();
    }

    protected function _after(): void
    {
    }

    public function testMapParametersToValuesWithExceptionOnIsArray()
    {

        
        $param = $this
            ->module
            ->mapParametersToValues(
                [0,1,2,3,4],
                [],
                "test"
            );

        $this->assertNull($param);
    }
}
