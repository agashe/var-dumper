<?php

if (!function_exists('d')) {
    /**
     * Print variable/s information.
     * 
     * @param array ...$vars
     * @return void
     */
    function d(...$vars) {
        $outputType = \VarDumper\Dumper::VAR_DUMPER_OUTPUT_WEB;
        
        if (php_sapi_name() === 'cli') {
            $outputType = \VarDumper\Dumper::VAR_DUMPER_OUTPUT_CLI;
        }

        \VarDumper\Dumper::dump($outputType, $vars, debug_backtrace());
    }
}

if (!function_exists('dd')) {
    /**
     * Print variable/s information , then terminate.
     * 
     * @param array ...$vars
     * @return void
     */
    function dd(...$vars) {
        $outputType = \VarDumper\Dumper::VAR_DUMPER_OUTPUT_CLI;
        
        if (php_sapi_name() !== 'cli') {
            $outputType = \VarDumper\Dumper::VAR_DUMPER_OUTPUT_WEB;
        
            // return HTTP 500 server error
            if (!headers_sent()) {
                header('HTTP/1.1 500 Internal Server Error');
            }
        }

        \VarDumper\Dumper::dump($outputType, $vars, debug_backtrace());

        // terminate execution 
        exit(0);
    }
}

if (!function_exists('dump')) {
    /**
     * Print variable/s information.
     * 
     * @param array ...$vars
     * @return void
     */
    function dump(...$vars) {
        $outputType = \VarDumper\Dumper::VAR_DUMPER_OUTPUT_WEB;
        
        if (php_sapi_name() === 'cli') {
            $outputType = \VarDumper\Dumper::VAR_DUMPER_OUTPUT_CLI;
        }

        \VarDumper\Dumper::dump($outputType, $vars, debug_backtrace());
    }
}

if (!function_exists('dump_to_file')) {
    /**
     * Print variable/s information to a file.
     * 
     * @param string $path
     * @param array ...$vars
     * @return void
     */
    function dump_to_file($path, ...$vars) {
        \VarDumper\Dumper::dump(
            \VarDumper\Dumper::VAR_DUMPER_OUTPUT_FILE,
            $vars,
            debug_backtrace(),
            $path
        );
    }
}