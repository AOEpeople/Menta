<?php

/**
 * Screenshot
 */
class Menta_Util_Screenshot {

    const TYPE_INFO = 'info';
    const TYPE_ERROR = 'error';

    protected $time;

    protected $trace;

    protected $type = Menta_Util_Screenshot::TYPE_INFO;

    protected $title;

    protected $description;

    protected $location;

    protected $base64Image;

    protected $id;

    protected $variant;

    protected $processingInstructions = array();

    /**
     * Set id
     *
     * @param string
     */
    public function setId($id) {
        $id = preg_replace("/[^a-zA-Z0-9-_]+/", '_', $id);
        $id = strtolower($id);
        $this->id = $id;
    }

    /**
     * Get id
     *
     * @return string
     */
    public function getId() {
        if (is_null($this->id)) {
            // auto-generate id from title or from random string:
            if ($this->getTitle()) {
                $id = $this->getTitle();
                if (!is_null($this->variant)) {
                    $id .= '.' . $this->variant;
                }
            } else {
                $id = md5(uniqid(rand(), TRUE));
            }
            $this->setId($id);
        }
        return $this->id;
    }

    /**
     * Adds an instruction to the processing instructinos
     *
     * @param Menta_Util_Screenshot_ProcessorInterface $instruction
     */
    public function addProcessingInstruction(Menta_Util_Screenshot_ProcessorInterface $instruction)
    {
        $this->processingInstructions[] = $instruction;
    }

    /**
     * @param string|\WebDriver\Element $element
     * @param string $color
     *
     * @author Manish Jain <manish.jain@aoe.com>
     * @throws Exception
     */
    public function paintElement($element, $color='#0000ff') {
        $commonHelper = Menta_ComponentManager::get('Menta_Component_Helper_Common'); /* @var $commonHelper Menta_Component_Helper_Common */
        $this->addProcessingInstruction(new Menta_Util_Screenshot_RectangleProcessor($commonHelper->getElement($element), null, null, null, $color));
    }

    /**
     * Processes all processing instructions
     *
     */
    public function process($filename)
    {
        foreach ($this->processingInstructions as $instruction) { /* @var $instruction Menta_Util_Screenshot_ProcessorInterface */
            $instruction->setImageFile($filename);
            $instruction->process();
        }
    }

    /**
     * Write image to disk
     *
     * @param string $filename
     * @throws Exception
     */
    public function writeToDisk($filename) {
        if (empty($this->base64Image)) {
            throw new Exception('No base64Image available');
        }
        if (empty($filename)) {
            throw new Exception('No filename set');
        }
        $res = file_put_contents($filename, base64_decode($this->base64Image));
        if ($res === false) {
            throw new Exception("File '$filename' could not be written");
        }

        $this->process($filename);
    }

    /**
     * Clean trace
     *
     * @param array $trace
     * @return array cleaned array
     */
    public function cleanTrace(array $trace) {
        $path = array();
        foreach ($trace as $dat) {
            $tmp = '';
            if (isset($dat['class'])) $tmp .= $dat['class'];
            if (isset($dat['type'])) $tmp .= $dat['type'];
            if (isset($dat['function'])) $tmp .= $dat['function'];
            $tmp .= '#';
            if (isset($dat['line'])) $tmp .= $dat['line'];
            $path[] = $tmp;
        }
        return $path;
    }

    public function setBase64Image($base64Image) {
        $this->base64Image = $base64Image;
    }

    public function getBase64Image() {
        return $this->base64Image;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setLocation($location) {
        $this->location = $location;
    }

    public function getLocation() {
        return $this->location;
    }

    public function setTime($time) {
        $this->time = $time;
    }

    public function getTime() {
        return $this->time;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setTrace($trace) {
        $this->trace = $this->cleanTrace($trace);
    }

    public function getTrace() {
        return $this->trace;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function getType() {
        return $this->type;
    }

    public function setVariant($variant) {
        $this->variant = $variant;
    }

    public function getVariant() {
        return $this->variant;
    }

}