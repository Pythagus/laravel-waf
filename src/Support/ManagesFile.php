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
     * @param callable $manage_line
     * @param callable $getter
     * @param string|null $path
     * @return array
     */
    private function read(callable $manage_line, callable $getter, string $path = null) {
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
        } catch(ErrorException $e) {
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
            while(($line = call_user_func($getter, $handle)) !== false) {
                $output = call_user_func($manage_line, $line) ;

                if(! empty($output)) {
                    $lines[] = $output ;
                }
            }

            fclose($handle) ;

            return $lines ;
        }
        
        throw WafConfigurationException::storage($path) ;
    }

    /**
     * Read a file and apply the callback to each line.
     * 
     * @param callable $callable
     * @param string|null $path
     * @return array
     */
    protected function readFile(callable $callable, string $path = null) {
        return $this->read($callable, fn($handler) => fgets($handler), $path) ;
    }

    /**
     * Read a CSV file and return a list of associative
     * arrays.
     * 
     * @param string $path
     * @return array
     */
    protected function readCsvFile(string $path = null) {
        $header = null ;

        return $this->read(function($line) use (&$header) {
            // Skip if this is most likely a comment line.
            if(count($line) == 0 || str_starts_with($line[0], "#")) {
                return ;
            }

            // If we didn't find the header yet, then here it is!
            if(is_null($header)) {
                $header = $line ;
                return ;
            }

            // This final part transforms the read array into
            // an associative array regarding the header.
            $output = [] ;
            foreach($line as $key => $value) {
                $output[$header[$key]] = $value ;
            }

            return $output ;
        }, fn($handler) => fgetcsv($handler, 1000, ","), $path) ;
    }
}