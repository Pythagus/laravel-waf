<?php

declare(strict_types=1) ;

/**
 * Generate data to serve the test.
 * 
 * @param int $size
 * @return array
 */
function generate_ip_list(int $size): array {
    $iterator = random_int(20, 1000000) ;
    $data = [] ;

    for($i = 0 ; $i < $size ; $i++) {
        $data[] = long2ip($iterator) ;
        $iterator++ ;
    }

    // Shuffle the array.
    shuffle($data) ;
    
    return $data ;
}

/**
 * Add cache miss values in the given data array,
 * and shuffle the resulting data.
 * 
 * @return array
 */
function add_cache_miss(array $data) {
    for($i = 0 ; $i < 50 ; $i++) {
        $data[] = "255.255.255.$i" ;
    }

    // Shuffle the array.
    shuffle($data) ;

    return $data ;
}

/**
 * Measure the execution time of the given closure on
 * the data.
 * 
 * @param array $data
 * @param callable $closure
 * @return float
 */
function measure(array $data, callable $closure) {
    $start = hrtime(true) ;

    foreach($data as $key => $value) {
        call_user_func($closure, $value, $key) ;
    }

    return (hrtime(true) - $start) / 1000000 ;
}

/**
 * Calculate the memory usage of the given variable.
 * Result is returned in kilobytes.
 * 
 * @return float
 */
function benchmark_memory_usage($variable) {
    $start_memory = memory_get_usage() ;

    $tmp = unserialize(serialize($variable)) ;

    return round(abs(memory_get_usage() - $start_memory) / 1024, 2) ;
}

/**
 * Generate a "table" version of the results to be printed
 * to the user.
 * 
 * @param array $results
 * @param string $unit
 * @return string
 */
function format_benchmark_results(array $results, string $unit) {
    $output = [] ;
    $functions = array_keys($results) ;
    $longestFunction = array_map(fn($x) => strlen($x), $functions) ;
    sort($longestFunction, SORT_ASC | SORT_NUMERIC) ;
    $choiceSize = max(11, $longestFunction[0]) ;
    $sizes = array_keys($results[$functions[0]]) ;

    // Prepare the header and the delimiter.
    $delimiter = "| ----------" ;
    $header    = "| Cache size" ;

    foreach($functions as $function) {
        $header .= " | " . str_pad($function, 15) ;
        $delimiter .= " | " . str_pad("", max(15, strlen($function)), "-") ;
    }

    $header    .= " | " . str_pad("Best choice", $choiceSize) . " |" ;
    $delimiter .= " | " . str_pad("", $choiceSize, "-") . " |" ;

    // Add the header.
    $output[] = $delimiter ;
    $output[] = $header ;
    $output[] = $delimiter ;
    
    // Add the data.
    foreach($sizes as $size) {
        $line = "| " . str_pad((string) $size, 10) ;
        $best = ['', -1] ;

        foreach($functions as $function) {
            $time = round($results[$function][$size], 3) ;

            // If we found a better result.
            if($time < $best[1] || $best[1] < 0) {
                $best = [$function, $time] ;
            }

            $line .= " | " . str_pad((string) $time . " $unit", max(15, strlen($function))) ;
        }

        $line .= " | " . str_pad($best[0], 11) . " |" ;

        $output[] = $line ;
    }

    $output[] = $delimiter ;

    return implode("\n", $output) ;
}