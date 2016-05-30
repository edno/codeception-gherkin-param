<?php
use Behat\Gherkin\Node\TableNode;
use Codeception\Util\Fixtures;


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

   /**
    * @Given I have a parameter :param with value :value
    */
    public function iHaveAParameterWithValue($param, $value)
    {
      Fixtures::add($param, $value);
    }

    /**
     * @Then I should see :arg1 equals :arg2
     */
     public function iSeeEqual($arg1, $arg2)
     {
       $this->assertEquals($arg1, $arg2);
     }

     /**
      * @Then I should see following:
      */
      public function iSeeTableEqual(TableNode $table)
      {
        foreach ($table->getRows() as $idx => $row) {
          if ($idx == 0) continue;
          $this->assertEquals($row[0], $row[1]);
        }
      }
}
