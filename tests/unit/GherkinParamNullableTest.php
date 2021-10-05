<?php
/**
 * Tests for onErrorNull configuration parameter
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class GherkinParamNullableTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $module;

    protected function _before(): void
    {
        $moduleInstance = $this->getModule(
            'Codeception\Extension\GherkinParam'
        );
        $moduleInstance->_reconfigure(
            [
            'onErrorThrowException' => false,
            'onErrorNull' => true
            ]
        );
        $this->module = Mockery::spy($moduleInstance)
            ->shouldAllowMockingProtectedMethods();
    }

    public function testMapParametersToValuesWithExceptionOnIsArray(): void
    {
        $param = $this
            ->module
            ->mapParametersToValues(
                [0,1,2,3,4],
                [[0],[1],[2],[3],[4]],
                "test"
            );
        $this->assertNull($param);
    }

    public function testMapParametersToValuesWithExceptionOnIsSet(): void
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
