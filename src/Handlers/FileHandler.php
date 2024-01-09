<?php

namespace VarDumper\Handlers;

/**
 * File Handler Class
 */
class FileHandler extends BaseHandler
{
    /**
     * @var string $filePath
     */
    private $filePath;

    /**
     * File Handler Constructor.
     */
    public function __construct($debugBacktrace, $filePath)
    {
        parent::__construct($debugBacktrace);

        // save file path
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException('Invalid file path to dump !!');            
        }

        $this->filePath = $filePath;
    }

    
    /**
     * Append content to file.
     * 
     * @param string $content
     * @return void
     */
    private function write($content)
    {
        file_put_contents($this->filePath, $content, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Render the final output.
     * 
     * @return void
     */
    public function flush()
    {
        // handle json files
        if (strpos($this->filePath, '.json') !== false) {
            $this->writeToJson();
            return;
        }
        
        foreach ($this->output as $line) {
            $this->write("$line \n");
        }

        $this->write("\n");
    }

    /**
     * Add the log header to the output.
     * 
     * @return void
     */
    public function header()
    {
        // handle json files
        if (strpos($this->filePath, '.json') !== false) {
            return;
        }

        $this->write("[ {$this->headerInfo['logTime']} / " .
            "{$this->headerInfo['fileName']} / " .
            "{$this->headerInfo['lineNumber']} ] \n\n");
    }

    /**
     * Save the output to a JSON file.
     * 
     * @return void
     */
    public function writeToJson()
    {
        $newContent = [];
        $oldContent = json_decode(file_get_contents($this->filePath), true);

        // add log header
        $timestamp = [
            'time' => trim(explode('Time :', $this->headerInfo['logTime'])[1]),
            'file' => trim(explode(':', $this->headerInfo['fileName'])[1]),
            'line' => (int) explode(':', $this->headerInfo['lineNumber'])[1],
        ];

        $data = [];
        $blocks = [];
        $blocksContent = [];

        foreach ($this->output as $i => $line) {
            if (strpos($line, '=>') !== false) {
                $parts = explode('=>', $line, 2);
                $key = strval(trim($parts[0]));
                $value =  $this->castValue($parts[1]);
            }

            if (strpos($line, '=> {}') !== false ||
                strpos($line, '=> []') !== false
            ) {
                if (isset($blocks[count($blocks) - 1])) {
                    $blocksContent[$blocks[count($blocks) - 1]][$key] = [];
                } else {
                    $data[$key] = [];
                }
            }
            else if (strpos($line, '=> {') !== false ||
                strpos($line, '=> [') !== false
            ) {
                $blocks[] = $key;
            } 
            else if (trim($line) === '}' || trim($line) === ']') {
                if (isset($blocks[count($blocks) - 2])) {
                    $blocksContent[$blocks[count($blocks) - 2]]
                        [$blocks[count($blocks) - 1]] = 
                            $blocksContent[$blocks[count($blocks) - 1]];
                }
                else if (isset($blocks[count($blocks) - 1])) {
                    $data[$blocks[count($blocks) - 1]] = 
                        $blocksContent[$blocks[count($blocks) - 1]];
                }

                unset($blocksContent[count($blocks) - 1]);
                unset($blocks[count($blocks) - 1]);
                $blocks = array_values($blocks);
            }
            else if (strpos($line, '=>') !== false) {
                if (isset($blocks[count($blocks) - 1])) {
                    $blocksContent[$blocks[count($blocks) - 1]][strval($key)] = $value;
                } else {
                    $data[$key] = $value;
                }
            }
            else {
                $value = $this->castValue($line);

                if (isset($blocks[count($blocks) - 1])) {
                    $block[$blocks[count($blocks) - 1]]['value'] = $value;
                } else {
                    $data['value'] = $value;
                }
            }
        }

        // generate unique key for the dump
        $key = substr(md5(serialize($timestamp) . rand(1, 1000)), 0, 7);

        $newContent['dump_' . $key] = [
            'timestamp' => $timestamp,
            'data' => $data,
        ];

        file_put_contents(
            $this->filePath,
            json_encode(
                array_merge(($oldContent ?? []), $newContent),
                JSON_PRETTY_PRINT|JSON_FORCE_OBJECT
            )
        );
    }

    /**
     * Convert string value to the proper type.
     * 
     * @param string $val
     * @return mixed
     */
    private function castValue($val)
    {
        $result = null;

        if (is_numeric($val)) {
            $result = (strpos($val, '.')) ? floatval($val) : intval($val);
        }
        else if (($val == 'false') || ($val == 'true')) {
            $result = ($val == 'true');
        }
        else if ($val == 'NULL') {
            $result = null;
        }
        else {
            $result = trim($this->removeQuotes($val));
        }

        return $result;
    }

    /**
     * Remove quotes from string value.
     * 
     * @param string $val
     * @return string
     */
    private function removeQuotes($val)
    {
        return str_replace(['"', "'"], '', $val);
    }
}