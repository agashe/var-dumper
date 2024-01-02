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
     * @param array $debugBacktrace
     * @param string $filePath
     * @return void
     */
    public static function dump(
        $outputType,
        $variables, 
        $debugBacktrace, 
        $filePath = ''
    ) {
        $dumper = self::getInstance();
        $dumper->printHeaderInfo($outputType, $debugBacktrace[0]);
        
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
     * @param string $outputType
     * @param array $debugBacktrace
     * @return void
     */
    private function printHeaderInfo($outputType, $debugBacktrace)
    {
        switch ($outputType) {
            case self::VAR_DUMPER_OUTPUT_CLI :
                $logTime = 'Time : ' . date('d-m-Y h:i:s');
        
                $fileName = 'File : ' . substr(
                    $debugBacktrace['file'],
                    -1 * (
                        strlen($debugBacktrace['file']) - 
                        strrpos($debugBacktrace['file'], '/', -1)
                        -1
                    )
                );

                $lineNumber = 'Line :' . $debugBacktrace['line'];

                print "[{$logTime} / {$fileName} / {$lineNumber}] \n\n"; 
                return;
            case self::VAR_DUMPER_OUTPUT_WEB :
                //
                return;
            case self::VAR_DUMPER_OUTPUT_FILE :
                //
                return;
            default:
                throw new \InvalidArgumentException(
                    'Invalid output type !!'
                );
        }
    }

    /**
     * Print the output for web content.
     * 
     * @param mixed $variable
     * @return void
     */
    private function printOutputWeb($variable)
    {
        $logTime = date('d-m-Y h:i:s');
        $variableType = gettype($variable);

        print "[{$logTime}] <br>";
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
        // print variable details 
        $variableType = gettype($variable);        
        $variableValue = $variable;

        switch ($variableType) {
            case 'NULL':
                $variableValue = 'NULL';
                print $variableValue . "\n\n";
                return;
            case 'string':
                if (!(@preg_match($variableValue, '') === false)) {
                    $variableType = 'RegExp(' . strlen($variable) . ')';
                    $variableValue = $variableValue;
                }
                else if (filter_var($variableValue, FILTER_VALIDATE_URL)) {
                    $variableType = 'URL(' . strlen($variable) . ')';
                    $variableValue = '"' . $variableValue . '"';
                }
                else if (filter_var($variableValue, FILTER_VALIDATE_EMAIL)) {
                    $variableType = 'email(' . strlen($variable) . ')';
                    $variableValue = '"' . $variableValue . '"';
                }
                else {
                    $variableType = 'string(' . strlen($variable) . ')';
                    $variableValue = '"' . $variableValue . '"';
                }

                break;
            case 'boolean':
                $variableValue = $variableValue ? 'True' : 'False';
                break;
            case 'resource':
                print "{$variableType} => {$variableValue} \n";
                
                foreach (stream_get_meta_data($variable) as $key => $val) {
                    $val = (string) $val;
                    print "\t {$key} => {$val} \n";
                }
                
                print "\t options => \n";
                
                foreach (stream_context_get_options($variable) as $key => $val) {
                    $val = (string) $val;
                    print "\t\t {$key} => {$val} \n";
                }

                print "\n\n";
                return;
        }

        print "{$variableType} => {$variableValue} \n\n";
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