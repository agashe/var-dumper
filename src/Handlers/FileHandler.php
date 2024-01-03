<?php

namespace VarDumper\Handlers;

/**
 * File Handler Class
 */
class FileHandler extends BaseHandler
{
    /**
     * @var string $filePath
     */
    private $filePath;

    /**
     * File Handler Constructor.
     */
    public function __construct($debugBacktrace, $filePath)
    {
        parent::__construct($debugBacktrace);

        // save file path
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException('Invalid file path to dump !!');            
        }

        $this->filePath = $filePath;
    }

    /**
     * Dump an array of variables and return the output
     * on the desired stream (Web / CLI / File).
     * 
     * @param mixed $variable
     * @return void
     */
    public function getVariableDetails($variable)
    {
        
    }
}