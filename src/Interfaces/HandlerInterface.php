<?php

namespace VarDumper\Interfaces;

/**
 * Handler Interface
 */
interface HandlerInterface
{
    /**
     * Render the final output.
     * 
     * @return void
     */
    public function flush();

    /**
     * Add the log header to the output.
     * 
     * @return void
     */
    public function header();
}