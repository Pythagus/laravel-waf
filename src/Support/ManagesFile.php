<?php

namespace Pythagus\LaravelWaf\Support;

use Pythagus\LaravelWaf\Exceptions\WafConfigurationException;

/**
 * This class helps managing files from cache.
 * 
 * @author Damien MOLINA
 */
trait ManagesFile {

    private function read(string $path, callable $manage_line, callable $getter) {
        // If the path is null, then the backup plan was disabled
        // by the user. Then, return an empty array.
        if(is_null($path) || strlen($path) == 0) {
            return [] ;
        }

        // Prepare a list of results to return.
        $lines = [] ;

        // Open the file in read mode.
        if($handle = fopen($path, 'r')) {
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

    protected function readFile(string $path, callable $manage_line) {
        return $this->read($path, $manage_line, fn($handler) => fgets($handler)) ;
    }

    /**
     * Read a CSV file and return a list of associative
     * arrays.
     * 
     * @param string $path
     * @return array
     */
    protected function readCsvFile(string $path) {
        $header = null ;

        return $this->read($path, function($line) use (&$header) {
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
        }, fn($handler) => fgetcsv($handler, 1000, ",")) ;
    }
}