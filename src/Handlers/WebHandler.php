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
        'header' => 'darkorange',
        'group' => 'gold',
        'type' => 'royalblue', 
        'value' => 'lightgreen',
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
            'overflow-x: auto',
            'white-space: pre-wrap',
            'white-space: -moz-pre-wrap',
            'white-space: -pre-wrap',
            'white-space: -o-pre-wrap',
            'word-wrap: break-word',
        ];

        // we use this variable to generate key property in order
        // to match the open/close brackets for the folding

        $blocks = [];
        $key = '';
        $parent = '';
        $displayStyle = '';

        foreach ($this->output as $i =>$line) {
            if (strpos($line, '=> {}') !== false ||
                strpos($line, '=> []') !== false
            ) {
                $line = $this->convertToHtml($line, $this->colors['group']);
            } 
            else if (strpos($line, '=> {') !== false ||
                strpos($line, '=> [') !== false
            ) {
                $parent = '';
                if (isset($blocks[count($blocks) - 1])) {
                    $parent = $blocks[count($blocks) - 1];
                }

                $key = substr(md5($line . rand(1, 1000)), 0, 7);
                $blocks[] = $key;

                $line = $this->convertToHtml($line, $this->colors['group']);

                // for folded lines we add the "display : none" style 
                // property
                $displayStyle = (count($blocks) <= 2) ? 'display: block' :
                    'display: none';

                // for the last line we add the border-bottom style
                if ($i == (count($this->output) - 1)) {
                    $lineStyle[] = 'border-bottom: 5px solid ' . 
                        $this->colors['border'];
                }

                // set cursor to pointer for the open tag
                $this->print(
                    $line,
                    array_merge($lineStyle, [
                        'cursor: pointer;', 
                        $displayStyle
                    ]),
                    $key,
                    $parent,
                    true,
                    (count($blocks) > 1)
                );

                continue;
            } 
            else if (trim($line) === '}' || trim($line) === ']') {
                $line = $this->convertToHtml($line, $this->colors['group']);
                
                $parent = '';
                if (isset($blocks[count($blocks) - 2])) {
                    $parent = $blocks[count($blocks) - 2];
                }

                if (isset($blocks[count($blocks) - 1])) {                    
                    // for folded lines we add the "display : none" style 
                    // property
                    $displayStyle = (count($blocks) <= 2) ? 'display: block' :
                        'display: none';

                    // for the last line we add the border-bottom style
                    if ($i == (count($this->output) - 1)) {
                        $lineStyle[] = 'border-bottom: 5px solid ' . 
                            $this->colors['border'];
                    }

                    $key = $blocks[count($blocks) - 1];
                    
                    $this->print(
                        $line,
                        array_merge($lineStyle, [$displayStyle]),
                        $key,
                        $parent,
                        false,
                        false,
                        true
                    );

                    unset($blocks[count($blocks) - 1]);
                    $blocks = array_values($blocks);

                    // get the last key
                    $key = isset($blocks[count($blocks) - 1]) ?
                        $blocks[count($blocks) - 1] : '';

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

            // for folded lines we add the "display : none" style property
            if (!empty($blocks)) {
                $lineStyle[] = (count($blocks) < 2) ? 'display: block' :
                    'display: none';
            }

            // for the last line we add the border-bottom style
            if ($i == (count($this->output) - 1)) {
                $lineStyle[] = 'border-bottom: 5px solid ' . 
                    $this->colors['border'];
            }

            $this->print($line, $lineStyle, $key);
        }

        // print the folding script
        print <<<JS
            <script>
                function toggleFold(el) {
                    let key = el.getAttribute("data-key");
                    let next = el.nextElementSibling;
                    let loop = true;
                    
                    while (loop) {
                        if (el.getAttribute("data-fold-to") == 'block') {
                            if (next.getAttribute("data-key") == key ||
                                next.getAttribute('data-parent-key') == key
                            ) {
                                next.style.display = 'block';
                            } else {
                                if (!next.hasAttribute("data-key")) {
                                    next.style.display = 'none';
                                } else {
                                    if (next.getAttribute('data-parent-key') == key) {
                                        next.style.display = 'block';
                                    } else {
                                        next.style.display = 'none';
                                    }
                                }
                            }
                        } else {
                            if (next.getAttribute("data-key") == key) {
                                next.style.display = 'block';
                            } else {
                                next.style.display = 'none';
                            }
                        }

                        if (next.getAttribute("data-key") == key) {
                            break;
                        }

                        next = next.nextElementSibling
                    }

                    el.setAttribute("data-fold-to", 
                        el.getAttribute("data-fold-to") == 'block' ?
                        "none" : "block"
                    );
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

                function toggleTextFold(el) {
                    let currentText = el.innerText;
                    console.log(el.getAttribute("data-text"));
                    el.innerText = el.getAttribute("data-text");
                    el.setAttribute("data-text", currentText);
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
            'margin: 0px',
            'overflow-x: auto',
            'white-space: pre-wrap',
            'white-space: -moz-pre-wrap',
            'white-space: -pre-wrap',
            'white-space: -o-pre-wrap',
            'word-wrap: break-word',
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
    private function convertToHtml($content, $color = 'black')
    {
        $text = trim($content);

        // handle long text by adding fold functionality
        if (strlen($text) > 150) {
            $tag = "<span style=\"cursor: pointer\" " . 
                'data-text=' . $text . '' .
                "onclick=\"toggleTextFold(this);\">" .
                substr($text, 0, 150) . "...\"</span>";

            $content = str_replace($text, $tag, $content);
        } else {
            $content = str_replace($text, "<span>{$text}</span>", $content);
        }

        return '<b style="color: ' . $color . ';">' . $content . '</b>';
    }

    /**
     *  Print text in a <pre> HTML tag with some inline style.
     * 
     * @param string $content
     * @param array $style
     * @param string $key
     * @param bool $control
     * @param bool $fold
     * @param bool $close
     * @return void
     */
    private function print(
        $content, 
        $style = [], 
        $key = '', 
        $parent = '', 
        $control = false,
        $fold = false,
        $close = false,
    ) {
        $inlineStyle = !empty($style) ? implode(';', $style) : '';
        $foldTo = $fold ? 'block' : 'none';

        if (!empty($key)) {
            if ($control) {
                print '<pre style="' . $inlineStyle . '" ' .
                    'data-key="' . $key . '" ' .
                    'data-parent-key="' . $parent . '" ' .
                    'data-fold-to="' . $foldTo . '"'  .
                    'onclick="toggleFold(this)" ' .
                    'onmouseover="addUnderline(this)" ' .
                    'onmouseout="removeUnderline(this)"' .
                    '>' . $content . '</pre>';
            } else {
                if ($close) {
                    print '<pre style="' . $inlineStyle . '" ' . 
                        'data-key="' . $key . '" ' .
                        'data-parent-key="' . $parent . '"' .
                        '>' . $content . '</pre>';
                } else {
                    print '<pre style="' . $inlineStyle . 
                        '" data-parent-key="' . $key . '"' . '>' . 
                        $content . '</pre>';
                }
            }
            
            return;
        }

        print '<pre style="' . $inlineStyle . '">' . $content . '</pre>';
    }
}