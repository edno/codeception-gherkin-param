<?php

use Codeception\Exception\ExtensionException;
use Codeception\Extension\GherkinParamException;
use Codeception\Util\Fixtures;

/**
 * Tests for onErrorThrowException configuration parameter
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
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
        $moduleInstance = $this->getModule(
            'Codeception\Extension\GherkinParam'
        );
        $moduleInstance->_reconfigure(['onErrorThrowException' => true]);
        $this->module = Mockery::mock($moduleInstance)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
    }

    public function testGetValueFromParamWithExceptionFromConfig(): void
    {
        $this->assertThrows(
            ExtensionException::class, function () {
                $this
                    ->module
                    ->getValueFromParam('{{config:undefinedConfig}}');
            }
        );
    }

    public function testGetValueFromParamWithExceptionFromArray(): void
    {
        $this->assertThrows(
            ExtensionException::class, function () {
                $this
                    ->module
                    ->getValueFromParam('{{undefinedArray[4]}}');
            }
        );
    }

    public function testGetValueFromParamWithExceptionFromFixture(): void
    {
        $this->assertThrows(
            ExtensionException::class, function () {
                $this
                    ->module
                    ->getValueFromParam('{{undefinedValue}}');
            }
        );
    }

    public function testMapParametersToValuesWithExceptionOnIsArray(): void
    {
        $this->assertThrows(
            GherkinParamException::class, function () {
                $this
                    ->module
                    ->mapParametersToValues(
                        [0,1,2,3,4],
                        [[0],[1],[2],[3],[4]],
                        'test'
                    );
            }
        );
    }

    public function testMapParametersToValuesWithExceptionOnIsSet(): void
    {
        $this->assertThrows(
            GherkinParamException::class, function () {
                $this
                    ->module
                    ->mapParametersToValues(
                        [0,1,2,3,4],
                        [],
                        'test'
                    );
            }
        );
    }   

    public function testGetValueFromFixtureWithExceptionOnIsSet(): void
    {
        $this->assertThrows(
            GherkinParamException::class, function () {
                $this
                    ->module
                    ->getValueFromFixture('{{test}}');
            }
        );
    }

    public function testGetValueFromArrayParamWithExceptionOnIsSet(): void
    {
        $this->assertThrows(
            GherkinParamException::class, function () {
                $this
                    ->module
                    ->getValueFromArrayParam('{{test[1]}}');
            }
        );
    }
}
