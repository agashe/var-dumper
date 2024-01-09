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
            // for the last line we add the border-bottom style
            if ($i == (count($this->output) - 1)) {
                $lineStyle[] = 'border-bottom: 5px solid ' . 
                    $this->colors['border'];
            }

            if (strpos($line, '=> {}') !== false ||
                strpos($line, '=> []') !== false
            ) {
                $line = $this->convertToHtml($line, $this->colors['group']);
            } 
            else if (strpos($line, '=> {') !== false ||
                strpos($line, '=> [') !== false
            ) {
                $line = $this->convertToHtml($line, $this->colors['group']);
                $displayStyle = $lineStyle;

                // we set the parent key to make the folding easier , we
                // make like grouping for the items , so when we iterate we
                // check for the parent key , to decide either we fold or not
                $parent = '';
                if (isset($blocks[count($blocks) - 1])) {
                    $parent = $blocks[count($blocks) - 1];
                }

                // generate unique eky for the block
                $key = substr(md5($line . rand(1, 1000)), 0, 7);
                $blocks[] = $key;

                // for folded lines we add the "display : none" style property
                $displayStyle[] = (count($blocks) <= 2) ? 
                    'display: block' : 'display: none';

                // set cursor to pointer for the open tag
                $displayStyle[] = 'cursor: pointer;';

                $this->print(
                    $line, // the html tag
                    $displayStyle, // the final style with all adjustments
                    $key, // the current block key
                    $parent, // the parent block key (if exists!)
                    true, // add control function for the start tag
                    (count($blocks) > 1) // default value for the fold
                );

                continue;
            } 
            else if (trim($line) === '}' || trim($line) === ']') {
                $line = $this->convertToHtml($line, $this->colors['group']);
                $displayStyle = $lineStyle;

                // we take the key before the current one as 
                // parent for the closing bracket 
                $parent = '';
                if (isset($blocks[count($blocks) - 2])) {
                    $parent = $blocks[count($blocks) - 2];
                }

                if (isset($blocks[count($blocks) - 1])) {                    
                    // for folded lines we add the "display : none" 
                    // style property
                    $displayStyle[] = (count($blocks) <= 2) ?
                        'display: block' : 'display: none';

                    $key = $blocks[count($blocks) - 1];
                    
                    $this->print(
                        $line, // the html tag
                        $displayStyle, // the final style with all adjustments
                        $key, // the current block key
                        $parent, // the parent block key (if exists!)
                        false, // no control function for the close tag
                        false, // since no control , so no folding mechanism
                        true // mark the line as closing bracket for the group
                    );

                    // remove the current key , since the block is over
                    unset($blocks[count($blocks) - 1]);
                    $blocks = array_values($blocks);

                    // get the last key , since we might be still  
                    // looping in a parent block
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

            $this->print($line, $lineStyle, $key);
        }

        $this->addControlScripts();

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
                'data-text=' . $text . ' ' .
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

    /**
     * Print the JS script for folding objects , arrays 
     * and long text .
     * 
     * @return void
     */
    private function addControlScripts() {
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
                                    if (next.getAttribute('data-parent-key') 
                                        == key
                                    ) {
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
                                if (next.hasAttribute("data-key")) {
                                    next.setAttribute('data-fold-to', 'block');
                                }

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
                    
                    el.innerText = el.getAttribute("data-text");
                    el.setAttribute("data-text", currentText);
                }
            </script>
        JS;
    }
}