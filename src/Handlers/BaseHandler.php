<?php

namespace VarDumper\Handlers;

use DateTime;

/**
 * Base Handler Abstract Class
 */
abstract class BaseHandler
{
    /**
     * @var array $output
     */
    protected $output;
    
    /**
     * @var array $headerInfo
     */
    protected $headerInfo;

    /**
     * @var int $indentation
     */
    protected $indentation;

    /**
     * Base Handler Constructor.
     */
    public function __construct($debugBacktrace)
    {
        $this->output = [];
        $this->indentation = 0;

        $this->headerInfo = [
            'logTime' => 'Time : ' . date('d-m-Y h:i:s'),
            'lineNumber' => 'Line : ' . $debugBacktrace['line'],
            'fileName' => 'File : ' . substr(
                $debugBacktrace['file'],
                -1 * (
                    strlen($debugBacktrace['file']) - 
                    strrpos($debugBacktrace['file'], '/', -1)
                    -1
                )
            )
        ]; 
    }

    /**
     * Render the final output.
     * 
     * @return void
     */
    abstract public function flush();

    /**
     * Add the log header to the output.
     * 
     * @return void
     */
    abstract public function header();

    /**
     * Dump the variable details.
     * 
     * @param mixed $variable
     * @param string $key
     * @return void
     */
    public function process($variable, $key = '')
    {
        // in case of arrays / objects we print the item's key
        // instead of the type
        $variableType = (!empty($key) || ($key === 0)) ?
            $key : gettype($variable);

        $variableValue = $variable;
        $line = '';

        switch (gettype($variable)) {
            case 'integer':
            case 'float':
            case 'double':
                $line = "{$variableType} => {$variableValue}";
                break;
            case 'NULL':
                if (!empty($key) || ($key === 0)) {
                    $line = "{$variableType} => NULL";
                } else {
                    $line = "NULL";
                }

                break;
            case 'string':                
                if (!(@preg_match($variableValue, '') === false)) {
                    $variableType = 'RegExp (' . strlen($variable) . ')';
                    $line = "{$variableType} => {$variableValue}";

                    break;
                }
                else if (filter_var($variableValue, FILTER_VALIDATE_URL)) {
                    $variableType = 'URL (' . strlen($variable) . ')';
                }
                else if (filter_var($variableValue, FILTER_VALIDATE_EMAIL)) {
                    $variableType = 'Email (' . strlen($variable) . ')';
                }
                else if (strtotime($variableValue) !== false && (
                    strpos($variableValue, '-') !== false ||
                    strpos($variableValue, '/') !== false ||
                    strpos($variableValue, ':') !== false
                )) {
                    $variableType = 'Date/Time (' . strlen($variable) . ')';
                }
                else {
                    if (empty($key) && ($key !== 0)) {
                        $variableType = 'string (' . strlen($variable) . ')';
                    }
                }

                $variableValue = $this->addQuotes($variableValue);
                $line = "{$variableType} => {$variableValue}";

                break;
            case 'boolean':
                $variableValue = $this->boolToString($variableValue);
                $line = "{$variableType} => {$variableValue}";
                break;
            case 'array':
                $this->processArray($variableValue, $key);
                break;
            case 'object':
                $this->processObject($variableValue, $key);
                break;
            case 'resource':
                $this->processResource($variableValue, $key);
                break;
            default:
                $serialize = serialize($variableValue);
                $line = "{$variableType} => {$serialize}";
        }

        if (!empty(trim($line))) {
            $this->output[] = $this->getIndentation() . $line;
        }
    }

    /**
     * Add quotes to a string value.
     * 
     * @param string $val
     * @return string
     */
    private function addQuotes($val)
    {
        return '"' . $val . '"';
    }
    
    /**
     * Convert boolean value into a string representation.
     * 
     * @param bool $val
     * @return string
     */
    private function boolToString($val)
    {
        return $val ? 'true' : 'false';
    }

    /**
     * Get line indentation.
     * 
     * @return string
     */
    private function getIndentation()
    {
        $spaces = "";

        for ($i = 0;$i < $this->indentation;$i++) {
            $spaces .= " ";
        }

        return $spaces;
    }

    /**
     * Iterate over an array and get all of its items.
     * 
     * @param array $array
     * @param string $key
     * @return void
     */
    private function processArray($array, $key = '')
    {
        $type = 'array';

        if (!empty($key) || ($key === 0)) {
            $type = "[{$key}] array";
        }

        if (count($array)) {
           $this->output[] = $this->getIndentation() .
                "{$type} (" . count($array) . ") => [";
        } else {
            $this->output[] = $this->getIndentation() .
                 "{$type} (" . count($array) . ") => []";
                
            return;
        }
        
        $this->indentation += 4;

        foreach ($array as $key => $value) {
            $this->process($value, $key);
        }

        $this->indentation -= 4;

        $this->output[] = $this->getIndentation() . "]";
    }

    /**
     * Get resource's details.
     * 
     * @param resource $resource
     * @param string $key
     * @return void
     */
    private function processResource($resource, $key = '')
    {
        $type = $resource;

        if (!empty($key) || ($key === 0)) {
            $type = "[{$key}] $resource";
        }

        $resourceDetails = stream_get_meta_data($resource);
        $resourceDetails['options'] = stream_context_get_options($resource);

        $this->output[] = $this->getIndentation() . "{$type} => {";

        $this->indentation += 4;

        foreach ($resourceDetails as $key => $value) {
            $this->process($value, $key);
        }

        $this->indentation -= 4;

        $this->output[] = $this->getIndentation() . "}";
    }

    /**
     * Get object's details.
     * 
     * @param object $object
     * @param string $key
     * @return void
     */
    private function processObject($object, $key = '')
    {
        $className = get_class($object);
        $objectId = spl_object_id($object);
        $objectDetails = (array) $object;

        // handle anonymous class
        if (preg_match("/class@anonymous/", $className)) {
            $className = "class@anonymous";
        }

        // handle closures
        if ($className === 'Closure') {
            $objectDetails = [];

            $reflectionFunction = new \ReflectionFunction($object);
            $parameters  = $reflectionFunction->getParameters();
            
            foreach ($parameters as $parameter) {
                $reflectionParameter = new \ReflectionParameter(
                    $object,
                    $parameter->name
                );

                $objectDetails[$parameter->name] = 
                    $reflectionParameter->isOptional() ? 
                        'optional' : 'required';
            }
        }

        $type = 'object';

        if (!empty($key) || ($key === 0)) {
            $type = "[{$key}] object";
        }

        $this->output[] =  $this->getIndentation() . 
            "{$type} ({$className}) " . 
            "#{$objectId} " .
            "(" . count($objectDetails) . ") " .
            "=> {";
    
        $this->indentation += 4;

        foreach ($objectDetails as $key => $value) {
            // handler private properties
            if (preg_match("/{$className}/", $key)) {
                $key = str_replace($className, '', $key) . ' (private)';
            }

            $this->process($value, $key);
        }

        $this->indentation -= 4;

        $this->output[] =  $this->getIndentation() . "}";
    }
}