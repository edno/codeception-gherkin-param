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
     * @Then /^I should see "([^"]+)" equals (?:to )?(?:")?([^"]+)(?:")?$/i
     */
     public function iSeeEqual($arg1, $arg2)
     {
       $this->assertEquals($arg2, $arg1);
     }

     /**
      * @Then I should see following:
      */
      public function iSeeTableEqual(TableNode $table)
      {
        foreach ($table->getRows() as $idx => $row) {
          if ($idx == 0) continue;
          $this->assertEquals($row[1], $row[0]);
        }
      }

      /**
       * @Then /^I should see "(.+)" is null$/i
       */
       public function iSeeNull($arg1)
       {
         $this->assertNull($arg1);
       }

    /**
     * @Given I have a parameter :param with values
     * @param string $param
     * @param TableNode $values
     */
    public function iHaveParameterWithValues(string $param, TableNode $values)
    {
        Fixtures::add($param, $values->getHash());
    }

    /**
     * @Then I should see :param with values
     * @param string $param
     * @param TableNode $table
     */
    public function iSeeParamEquals(string $param, TableNode $table)
    {
        $hash = $table->getHash();
        foreach (Fixtures::get($param) as $key => $values) {
            $this->assertEquals($hash[$key], $values);
        }
    }
}
