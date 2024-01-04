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
     * Append content to file.
     * 
     * @param string $content
     * @return void
     */
    private function write($content)
    {
        file_put_contents($this->filePath, $content, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Render the final output.
     * 
     * @return void
     */
    public function flush()
    {
        foreach ($this->output as $line) {
            $this->write("$line \n");
        }

        $this->write("\n");
    }

    /**
     *  Add the log header to the output.
     * 
     * @return void
     */
    public function header()
    {
        $this->write("[ {$this->headerInfo['logTime']} / " .
            "{$this->headerInfo['fileName']} / " .
            "{$this->headerInfo['lineNumber']} ] \n\n");
    }
}