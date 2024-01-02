<?php

namespace VarDumper;

/**
 * Var Dumper class
 */
class Dumper
{
    /**
     * @var string VarDumper output types
     */
    public const VAR_DUMPER_OUTPUT_CLI = 'cli';
    public const VAR_DUMPER_OUTPUT_WEB = 'web';
    public const VAR_DUMPER_OUTPUT_FILE = 'file';

    /**
     * Dump an array of variables and return the output
     * on the desired stream (Web / CLI / File).
     * 
     * @param string $outputType
     * @param array $variables
     * @param string $filePath
     * @return void
     */
    public static function dump($outputType, $variables, $filePath = '')
    {
        $dumper = self::getInstance();

        foreach ($variables as $variable) {
            switch ($outputType) {
                case self::VAR_DUMPER_OUTPUT_CLI :
                    $dumper->printOutputCli($variable);
                    break;
                case self::VAR_DUMPER_OUTPUT_WEB :
                    $dumper->printOutputWeb($variable);
                    break;
                case self::VAR_DUMPER_OUTPUT_FILE :
                    $dumper->printOutputFile($variable, $filePath);
                    break;
                default:
                    throw new \InvalidArgumentException(
                        'Invalid output type !!'
                    );
            }
        }
    }
    
    /**
     * Get new instance of the class.
     * 
     * @return Dumper
     */
    private static function getInstance()
    {
        return (new static);
    }

    /**
     * Print the output for web content.
     * 
     * @param mixed $variable
     * @return void
     */
    private function printOutputWeb($variable)
    {
        $logDate = date('d-m-Y h:i:s');
        $variableType = gettype($variable);

        print "[{$logDate}] <br>";
        print "{$variableType} => {$variable} <br>";

        print "<br>";
    }

    /**
     * Print the output for CLI.
     * 
     * @param mixed $variable
     * @return void
     */
    private function printOutputCli($variable)
    {
        $logDate = date('d-m-Y h:i:s');
        $variableType = gettype($variable);

        print "[{$logDate}] \n";
        print "{$variableType} => {$variable} \n";

        print "\n";
    }

    /**
     * Print the output into file.
     * 
     * @param mixed $variable
     * @param string $filePath
     * @return void
     */
    private function printOutputFile($variable, $filePath)
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException('Invalid file path to dump !!');            
        }
    }   
}