<?php

namespace Pythagus\LaravelWaf\Support;

use ErrorException;
use Pythagus\LaravelWaf\Exceptions\WafConfigurationException;

/**
 * This class helps managing files from cache.
 * 
 * @author Damien MOLINA
 */
trait ManagesFile {

    /**
     * Read a file and apply the callback to each line.
     * 
     * @param callable $callable
     * @param string|null $path
     * @return array
     */
    protected function readFile(callable $callable, string $path = null) {
        // If the path is null, then the backup plan was disabled
        // by the user. Then, return an empty array.
        if(empty($path) || strlen($path) == 0) {
            return [] ;
        }

        // Prepare a list of results to return.
        $lines = [] ;
        $handle = null ;

        try {
            // Open the file in read mode.
            $handle = fopen($path, 'r') ;
        } catch(ErrorException) {
            // If the file cannot be opened, then it's probably that
            // it doesn't exist. Then, if the file exists, it's a permission
            // issue that needs to raise the WafConfigurationException. Otherwise,
            // it's just that the file doesn't exist!
            if(! file_exists($path)) {
                return [] ;
            }
        }

        // If the file was successfully opened.
        if($handle) {
            while(($line = fgets($handle)) !== false) {
                $output = call_user_func($callable, $line) ;

                if(! empty($output)) {
                    $lines[] = $output ;
                }
            }

            fclose($handle) ;

            return $lines ;
        }
        
        throw WafConfigurationException::storage($path) ;
    }
}