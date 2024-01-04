<?php

namespace VarDumper\Handlers;

/**
 * Web Handler Class
 */
class WebHandler extends BaseHandler
{
    /**
     * ANSI Color Codes.
     * 
     * Source : https://en.wikipedia.org/wiki/ANSI_escape_code#Colors
     */
    private $colors = [
        'header' => 'orange',
        'group' => 'yellow',
        'type' => 'blue', 
        'value' => 'lime',
        'arrow' => 'darkcyan'
    ];

    /**
     * Render the final output.
     * 
     * @return void
     */
    public function flush()
    {
        foreach ($this->output as $line) {
            if (strpos($line, '=> {') !== false ||
                strpos($line, '=> [') !== false ||
                $line === '}' ||
                $line === ']'
            ) {
                $line = $this->convertToHtml($line, $this->colors['group']);
            } 
            else if (strpos($line, '=>') !== false) {
                $parts = explode('=>', $line);
    
                $line = $this->convertToHtml($parts[0], $this->colors['type']);
                $line .= $this->convertToHtml("=>", $this->colors['arrow']);
                $line .= $this->convertToHtml(
                    $parts[1], $this->colors['value']
                );
            }
            else {
                $line = $this->convertToHtml($line, $this->colors['value']);
            }

            $this->print($line);
        }

        print '<br>';
    }

    /**
     *  Add the log header to the output.
     * 
     * @return void
     */
    public function header()
    {
        $header = $this->convertToHtml("[ {$this->headerInfo['logTime']} / " .
            "{$this->headerInfo['fileName']} / " .
            "{$this->headerInfo['lineNumber']} ]", $this->colors['header']);

        $this->print($header);
    }

    /**
     *  Wrap a text in <span> HTML tag with some color style.
     * 
     * @param string $content
     * @param string $color
     * @return string
     */
    public function convertToHtml($content, $color = 'black')
    {
        return '<b style="color: ' . $color . ';">' . $content . '</b>';
    }

    /**
     *  Print text in a <pre> HTML tag with some style.
     * 
     * @param string $content
     * @return void
     */
    public function print($content)
    {
        print '<pre ' . 
            'style="background-color: black;padding: 3px 10px;margin: 0px;">' . 
                $content . 
            '</pre>';
    }
}