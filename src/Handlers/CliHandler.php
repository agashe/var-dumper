<?php

namespace VarDumper\Handlers;

/**
 * Cli Handler Class
 */
class CliHandler extends BaseHandler
{
    /**
     * Render the final output.
     * 
     * @return void
     */
    public function flush()
    {
        foreach ($this->output as $line) {
            print "$line \n";
        }

        print "\n";
    }

    /**
     *  Add the log header to the output.
     * 
     * @return void
     */
    public function header()
    {
        print "[ {$this->headerInfo['logTime']} / " .
            "{$this->headerInfo['fileName']} / " .
            "{$this->headerInfo['lineNumber']} ] \n\n";
    }
}