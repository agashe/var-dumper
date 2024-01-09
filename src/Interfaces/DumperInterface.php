<?php

namespace VarDumper\Interfaces;

/**
 * Var Dumper Interface
 */
interface DumperInterface
{
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
    );
}