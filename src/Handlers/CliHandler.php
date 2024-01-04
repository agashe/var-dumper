<?php

namespace VarDumper\Handlers;

/**
 * Cli Handler Class
 */
class CliHandler extends BaseHandler
{
    /**
     * ANSI Color Codes.
     * 
     * Source : https://en.wikipedia.org/wiki/ANSI_escape_code#Colors
     */
    private $colors = [
        'header' => 91, // Bright Red
        'group' => 93, // Bright Yellow
        'type' => 34, // Blue
        'value' => 32, // Green
        'arrow' => 36, // Cyan
    ];

    /**
     * Render the final output.
     * 
     * @return void
     */
    public function flush()
    {
        foreach ($this->output as $line) {
            if (strpos($line, 'object') !== false ||
                strpos($line, 'array') !== false ||
                strpos($line, 'Resource') !== false ||
                $line === '}' ||
                $line === ']'
            ) {
                $this->print($line . "\n", $this->colors['group']);
            } 
            else if (strpos($line, '=>') !== false) {
                $parts = explode('=>', $line);
    
                $this->print($parts[0], $this->colors['type']);
                $this->print("=>", $this->colors['arrow']);
                $this->print($parts[1] . "\n", $this->colors['value']);
            }
            else {
                $this->print($line . "\n", $this->colors['value']);
            }
        }

        $this->print("\n");
    }

    /**
     *  Add the log header to the output.
     * 
     * @return void
     */
    public function header()
    {
        $this->print(
            "[ {$this->headerInfo['logTime']} / " .
            "{$this->headerInfo['fileName']} / " .
            "{$this->headerInfo['lineNumber']} ] \n\n",
            $this->colors['header']
        );
    }

    /**
     *  Print colorful text if the terminal supports colors.
     * 
     * @param string $content
     * @param string $color
     * @return void
     */
    public function print($content, $color = '')
    {
        // detect terminal colors !!!
        if (!stream_isatty(STDOUT) || 
            isset($_SERVER['NO_COLOR']) || 
            empty($color)
        ) { 
            print $content;
        } else {
            print "\033[{$color}m{$content}\033[0m";
        }
    }
}