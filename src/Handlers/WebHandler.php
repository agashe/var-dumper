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

        // we use this variable to generate key property in order
        // to match the open/close brackets for the folding

        $blocks = [];
        $key = '';

        foreach ($this->output as $i =>$line) {
            if (strpos($line, '=> {}') !== false ||
                strpos($line, '=> []') !== false
            ) {
                $line = $this->convertToHtml($line, $this->colors['group']);
            } 
            else if (strpos($line, '=> {') !== false ||
                strpos($line, '=> [') !== false
            ) {
                $key = substr(md5($line . rand(1, 1000)), 0, 7);
                $blocks[] = $key;

                $line = $this->convertToHtml($line, $this->colors['group']);

                // for the last line we add the border-bottom style
                if ($i == (count($this->output) - 1)) {
                    $lineStyle[] = 'border-bottom: 5px solid ' . 
                        $this->colors['border'];
                }

                // set cursor to pointer for the open tag
                $this->print(
                    $line,
                    array_merge($lineStyle, ['cursor: pointer;']),
                    $key,
                    true
                );

                continue;
            } 
            else if (trim($line) === '}' || trim($line) === ']') {
                $line = $this->convertToHtml($line, $this->colors['group']);
                
                if (isset($blocks[count($blocks) - 1])) {
                    // for the last line we add the border-bottom style
                    if ($i == (count($this->output) - 1)) {
                        $lineStyle[] = 'border-bottom: 5px solid ' . 
                            $this->colors['border'];
                    }

                    $key = $blocks[count($blocks) - 1];
                    $this->print($line, $lineStyle, $key);

                    unset($blocks[count($blocks) - 1]);
                    $blocks = array_values($blocks);

                    continue;
                }
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

        // print the folding script
        print <<<JS
            <script>
                function toggleFold(el) {
                    let key = el.getAttribute("data-key");
                    let next = el.nextElementSibling;

                    if (!el.getAttribute("data-fold-to")) {
                        el.setAttribute("data-fold-to", "none");
                    } 
                    else if (el.getAttribute("data-fold-to") == 'block') {
                        el.setAttribute("data-fold-to", "none");
                    } 
                    else if (el.getAttribute("data-fold-to") == 'none') {
                        el.setAttribute("data-fold-to", "block");
                    }

                    while (next.getAttribute("data-key") != key) {
                        if (next.style.display != 
                            el.getAttribute("data-fold-to")
                        ) {
                            next.style.display = 
                                el.getAttribute("data-fold-to");
                        }
                        
                        next = next.nextElementSibling
                    }
                }

                function addUnderline(el) {
                    let node = (!el.childNodes[0].childNodes[1]) ?
                        el.childNodes[0].childNodes[0] : 
                        el.childNodes[0].childNodes[1];

                    node.style.textDecoration = 'underline';
                    node.style.textDecorationSkipInk = 'none';
                }

                function removeUnderline(el) {
                    let node = (!el.childNodes[0].childNodes[1]) ?
                        el.childNodes[0].childNodes[0] : 
                        el.childNodes[0].childNodes[1];
                    
                    node.style.textDecoration = 'none';
                }
            </script>
        JS;

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
        $text = trim($content);
        $content = str_replace($text, "<span>{$text}</span>", $content);

        return '<b style="color: ' . $color . ';">' . $content . '</b>';
    }

    /**
     *  Print text in a <pre> HTML tag with some inline style.
     * 
     * @param string $content
     * @param array $style
     * @param string $key
     * @param bool $control
     * @return void
     */
    public function print($content, $style = [], $key = '', $control = false)
    {
        $inlineStyle = !empty($style) ? implode(';', $style) : '';

        if (!empty($key)) {
            if ($control) {
                print '<pre style="' . $inlineStyle . 
                    '" data-key="' . $key . '"' .
                    'onclick="toggleFold(this)" ' .
                    'onmouseover="addUnderline(this)" ' .
                    'onmouseout="removeUnderline(this)"' .
                    '>' . $content . '</pre>';
            } else {
                print '<pre style="' . $inlineStyle . 
                    '" data-key="' . $key . '"' . '>' . $content . '</pre>';
            }
            
            return;
        }

        print '<pre style="' . $inlineStyle . '">' . $content . '</pre>';
    }
}