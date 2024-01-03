<?php

namespace VarDumper;

use VarDumper\Handlers\CliHandler;
use VarDumper\Handlers\WebHandler;
use VarDumper\Handlers\FileHandler;

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
        if (empty($variables)) {
            throw new \InvalidArgumentException('No variables to dump !!');
        }

        $handler = null;
        
        switch ($outputType) {
            case self::VAR_DUMPER_OUTPUT_CLI :
                $handler = new CliHandler($debugBacktrace[0]);
                break;
            case self::VAR_DUMPER_OUTPUT_WEB :
                $handler = new WebHandler($debugBacktrace[0]);
                break;
            case self::VAR_DUMPER_OUTPUT_FILE :
                $handler = new FileHandler($debugBacktrace[0], $filePath);
                break;
            default:
                throw new \InvalidArgumentException(
                    'Invalid output type !!'
                );
        }

        $handler->header();
        
        foreach ($variables as $variable) {
            $handler->process($variable);
        }
        
        $handler->flush();
    }
}