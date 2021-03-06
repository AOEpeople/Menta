<?php

/**
 * Selenium1_Testcase
 * Base class for selenium tests that takes care setup up the connection to the
 * selenium server.
 * Unlike other base classes (e.g. PHPUnit_Extensions_SeleniumTestCase) this method
 * won't act as procy and passing commands to the webdriver, but focus on its main
 * purposes.
 * To interact with the selenium server use getSession() and/or getWebDriver()
 *
 * @author Fabrizio Branca
 * @since 2011-11-18
 */
abstract class Menta_PHPUnit_Testcase_Selenium2 extends PHPUnit_Framework_TestCase implements Menta_Interface_ScreenshotTestcase, Menta_Interface_TestLogTestcase {

    /**
     * @var string
     */
    protected $testId;

    /**
     * @var bool
     */
    protected $captureScreenshotOnFailure = false;

    /**
     * @var array collected screenshots
     */
    protected $screenshots = array();

    /**
     * @var array info
     */
    protected $info = array();

    /**
     * @var bool
     */
    protected $freshSessionForEachTestMethod = false;

    /**
     * @var bool
     */
    protected $cleanupPreviousSession = false;

    /**
     * @var Menta_Interface_Configuration
     */
    protected $configuration;

    /**
     * @var array
     */
    protected $loggedTestSteps = array();

    /**
     * @param  string $name
     * @param  array  $data
     * @param  string $dataName
     * @throws InvalidArgumentException
     */
    public function __construct($name = NULL, array $data = array(), $dataName = '') {
        parent::__construct($name, $data, $dataName);
        $this->testId = md5(uniqid(mt_rand(), TRUE));

        if ($this->getConfiguration()->issetKey('testing.selenium.captureScreenshotOnFailure')) {
            $this->captureScreenshotOnFailure = (bool)$this->getConfiguration()->getValue('testing.selenium.captureScreenshotOnFailure');
        }

    }

    /**
     * Get configuration
     *
     * @return Menta_Interface_Configuration
     */
    public function getConfiguration() {
        if (is_null($this->configuration)) {
            $this->configuration = Menta_ConfigurationPhpUnitVars::getInstance();
        }
        return $this->configuration;
    }

    /**
     * Setup
     *
     * @return void
     */
    public function setUp() {
        if (Menta_SessionManager::activeSessionExists()) {
            if ($this->freshSessionForEachTestMethod) {
                // Closes previous session if exists. A new session will be started on first call of Menta_SessionManager::getSession() or $this->getSession();
                Menta_SessionManager::closeSession();
            } elseif ($this->cleanupPreviousSession) {
                // Deleting all cookies to cleanup any previous application session state
                Menta_SessionManager::getSession()->deleteAllCookies();
            }
        }
        parent::setUp();

        $GLOBALS['current_testcase'] = $this;
    }

    /**
     * Get webdriver session
     *
     * @param bool $forceNew
     * @return \WebDriver\Session
     */
    public function getSession($forceNew=false) {
        try {
            return Menta_SessionManager::getSession($forceNew);
        } catch (\WebDriver\Exception $e) {
            $this->markTestSkipped($e->getMessage()); // couldn't connect to host
        }
    }

    /**
     * On not successful
     * In case of an exeception $this->tearDown() will be called before processing this method anyways
     *
     * @throws PHPUnit_Framework_SyntheticError
     * @param Exception $e
     * @return void
     */
    protected function onNotSuccessfulTest(Exception $e) {
        if (!($e instanceof PHPUnit_Framework_IncompleteTestError || $e instanceof PHPUnit_Framework_SkippedTestError) && $this->captureScreenshotOnFailure) {
            try {
                $this->takeScreenshot(
                    get_class($e),
                    PHPUnit_Framework_TestFailure::exceptionToString($e),
                    Menta_Util_Screenshot::TYPE_ERROR,
                    $e->getTrace()
                );
            } catch (Exception $screenshotException) {
                // if there's an exception while taking a screenshot because a test was not successful. That's bad luck :)
                throw new PHPUnit_Framework_SyntheticError(
                    $e->getMessage() . ' (AND: Exception while taking screenshot: '.$screenshotException->getMessage().')',
                    $e->getCode(),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getTrace()
                );
            }
        }
        parent::onNotSuccessfulTest($e);
    }


    /**
     * Add info
     *
     * @param $info
     */
    public function addInfo($info) {
        $this->info[] = $info;
    }

    /**
     * Get all information
     *
     * @return array
     */
    public function getInfo() {
        return $this->info;
    }

    /**
     * @param $label
     * @return string
     * @throws Exception
     */
    public function __($label) {
        $translationHelper = Menta_ComponentManager::get('Menta_Component_Helper_Translation'); /* @var $translationHelper Menta_Component_Helper_Translation */
        return $translationHelper->__($label);
    }

    /**
     * METHODS IMPLEMENTING INTERFACE:
     * Menta_Interface_ScreenshotTestcase
     */

    /**
     * Take a screenshot
     *
     * @param string $title
     * @param string $description
     * @param string $type
     * @param array $trace
     * @param string $id
     * @param string $variant
     * @return bool|Menta_Util_Screenshot
     */
    public function takeScreenshot($title=NULL, $description=NULL, $type=NULL, array $trace=NULL, $id=NULL, $variant=NULL) {
        // don't init a new session if there is none
        if (!Menta_SessionManager::activeSessionExists()) {
            return false;
        }

        if (Menta_ConfigurationPhpUnitVars::getInstance()->getValue('testing.selenium.skipScreenshots', false)) {
            return false;
        }

        // fire events
        $eventParamaters = array(
            'test' => $this
            // we might want to add more stuff like title, description,... here
        );
        Menta_Events::dispatchEvent('before_taking_screenshot', $eventParamaters);

        $screenshot = $this->getScreenShot();
        $screenshot->setTitle($title);
        $screenshot->setTrace(!is_null($trace) ? $trace : debug_backtrace());
        if (!is_null($id)) { $screenshot->setId($id); }
        if (!is_null($description)) { $screenshot->setDescription($description); }
        if (!is_null($type)) { $screenshot->setType($type); }
        if (!is_null($variant)) { $screenshot->setVariant($variant); }
        $this->screenshots[] = $screenshot;
        Menta_Events::dispatchEvent('after_taking_screenshot', $eventParamaters);
        return $screenshot;
    }

    /**
     * @param string $title
     *
     * @return Menta_Util_Screenshot
     */
    public function getScreenShot($title = '') {
        // don't init a new session if there is none
        if (!Menta_SessionManager::activeSessionExists()) {
            return false;
        }
        $screenshotHelper = Menta_ComponentManager::get('Menta_Component_Helper_Screenshot'); /* @var $screenshotHelper Menta_Component_Helper_Screenshot */
        $base64Image = $screenshotHelper->takeScreenshotToString();
        $time = time();
            // put data into the screenshot object
        $screenshot = new Menta_Util_Screenshot();
        $screenshot->setBase64Image($base64Image);
        $screenshot->setTime($time);

        if (!is_null($title)) { $screenshot->setTitle($title); }

        $screenshot->setLocation($this->getSession()->url());
        return $screenshot;
    }

    /**
     * Get all screenshots that were taken so far
     *
     * @return array array of Menta_Util_Screenshot
     */
    public function getScreenshots() {
        return $this->screenshots;
    }

    /**
     * Get test id
     *
     * @return string
     */
    public function getTestId() {
        return $this->testId;
    }

    /**
     * @param $message
     */
    public function logTestStep($message) {
        $this->loggedTestSteps[] = $message;
    }

    /**
     * @param Menta_Util_Screenshot $screenshot
     */
    public function logScreenshot(Menta_Util_Screenshot $screenshot) {
        $this->loggedTestSteps[] = $screenshot;
    }

    /**
     * @return array
     */
    public function getLoggedTestSteps() {
        return $this->loggedTestSteps;
    }
}
