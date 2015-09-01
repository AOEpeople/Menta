<?php
/**
 * Abstract component class for components that need access to the current PHPUnit testcase
 *
 * @author Fabrizio Branca
 * @since 2011-11-24
 */
abstract class Menta_Component_AbstractTest extends Menta_Component_Abstract {

    /**
     * @var PHPUnit_Framework_TestCase
     */
    protected $test;

    /**
     * Set test object
     *
     * @param PHPUnit_Framework_TestCase $test
     * @return Menta_Component_AbstractTest
     */
    public function setTest(PHPUnit_Framework_TestCase $test) {
        $this->test = $test;
        return $this;
    }

    /**
     * Translate string
     *
     * @param $label
     * @return string
     * @throws Exception
     */
    public function __($label) {
        return $this->getTest()->__($label);
    }


    /**
     * Get test object
     *
     * @return Menta_PHPUnit_Testcase_Selenium2
     * @throws Exception if testcase is not available
     */
    public function getTest() {
        if (is_null($this->test)) {
            throw new Exception('No testcase object available, check if you are calling parent::setUp() in your test class.');
        }
        return $this->test;
    }

}
