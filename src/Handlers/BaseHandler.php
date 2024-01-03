<?php

namespace VarDumper\Handlers;

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
            'lineNumber' => 'Line :' . $debugBacktrace['line'],
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
     * @return void
     */
    public function process($variable)
    {        
        $variableType = gettype($variable);
        $variableValue = $variable;
        $line = '';

        switch ($variableType) {
            case 'integer':
            case 'float':
            case 'double':
                $line = "{$variableType} => {$variableValue}";
                break;
            case 'NULL':
                $line = "NULL";
                break;
            case 'array':
                $this->processArray($variableValue);
                break;
            case 'object':
                $this->processObject($variableValue);
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
                // else if (strtotime($variableValue) !== false) {
                //     $variableType = 'Date/Time (' . strlen($variable) . ')';
                // }
                else {
                    $variableType = 'string (' . strlen($variable) . ')';
                }

                $variableValue = $this->addQuotes($variableValue);
                $line = "{$variableType} => {$variableValue}";

                break;
            case 'boolean':
                $variableValue = $this->boolToString($variableValue);
                $line = "{$variableType} => {$variableValue}";
                break;
            case 'resource':
                $this->processResource($variableValue);
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
     * @return void
     */
    private function processArray($array)
    {
        if (count($array)) {
           $this->output[] = $this->getIndentation() .
                "array (" . count($array) . ") => [";
        } else {
            $this->output[] = $this->getIndentation() .
                 "array (" . count($array) . ") => []";
                
            return;
        }
        
        $this->indentation += 4;

        foreach ($array as $value) {
            $this->process($value);
        }

        $this->indentation -= 4;

        $this->output[] = $this->getIndentation() . "]";
    }

    /**
     * Get resource's details.
     * 
     * @param resource $resource
     * @return void
     */
    private function processResource($resource)
    {
        $resourceDetails = stream_get_meta_data($resource);
        $resourceDetails['options'] = stream_context_get_options($resource);

        $this->output[] = $this->getIndentation() . "{$resource} => {";

        $this->indentation += 4;

        foreach ($resourceDetails as $value) {
            $this->process($value);
        }

        $this->indentation -= 4;

        $this->output[] = $this->getIndentation() . "}";
    }

    /**
     * Get object's details.
     * 
     * @param object $object
     * @return void
     */
    private function processObject($object)
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

        $this->output[] =  $this->getIndentation() . 
            "object ({$className}) " . 
            "#{$objectId} " .
            "(" . count($objectDetails) . ") " .
            "=> {";
    
        $this->indentation += 4;

        foreach ($objectDetails as $value) {
            $this->process($value);
        }

        $this->indentation -= 4;

        $this->output[] =  $this->getIndentation() . "}";
    }
}