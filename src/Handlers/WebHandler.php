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
        'arrow' => 'darkcyan',
        'border' => 'darkslategray',
        'background' => 'black'
    ];

    /**
     * Render the final output.
     * 
     * @return void
     */
    public function flush()
    {
        $lineStyle = [
            'background-color: ' . $this->colors['background'],
            'border-right: 5px solid ' . $this->colors['border'],
            'border-left: 5px solid ' . $this->colors['border'],
            'padding: 3px 10px',
            'margin: 0px',
        ];

        foreach ($this->output as $i =>$line) {
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

            // for the last line we add the border-bottom style
            if ($i == (count($this->output) - 1)) {
                $lineStyle[] = 'border-bottom: 5px solid ' . 
                    $this->colors['border'];
            }

            $this->print($line, $lineStyle);
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
            "{$this->headerInfo['lineNumber']} ]", 
            $this->colors['header']
        );

        $headerStyle = [
            'background-color: ' . $this->colors['border'],
            'padding: 3px 10px',
            'margin: 0px'
        ];

        $this->print($header, $headerStyle);
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
     *  Print text in a <pre> HTML tag with some inline style.
     * 
     * @param string $content
     * @param array $style
     * @return void
     */
    public function print($content, $style = [])
    {
        $inlineStyle = !empty($style) ? implode(';', $style) : '';
        print '<pre style="' . $inlineStyle . '">' . $content . '</pre>';
    }
}