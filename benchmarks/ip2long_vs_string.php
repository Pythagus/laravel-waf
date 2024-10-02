<?php

declare(strict_types=1) ;

/**
 * This scripts aims to compare the usage of the function
 * ip2long to store IP addresses in an array.
 * 
 * Here is an overview of the output of the script.
 * 
 * Execution time results:

 * | ---------- | --------------- | --------------- | ----------- |
 * | Cache size | string          | ip2long         | Best choice |
 * | ---------- | --------------- | --------------- | ----------- |
 * | 200        | 0.021 ms        | 0.028 ms        | string      |
 * | 1000       | 0.074 ms        | 0.114 ms        | string      |
 * | 5000       | 0.481 ms        | 0.662 ms        | string      |
 * | 10000      | 1.138 ms        | 1.344 ms        | string      |
 * | 50000      | 6.19 ms         | 8.006 ms        | string      |
 * | 100000     | 13.489 ms       | 16.721 ms       | string      |
 * | ---------- | --------------- | --------------- | ----------- |
 * 
 * Memory usage results:
 * 
 * | ---------- | --------------- | --------------- | ----------- |
 * | Cache size | string          | ip2long         | Best choice |
 * | ---------- | --------------- | --------------- | ----------- |
 * | 200        | 19.87 kb        | 12.05 kb        | ip2long     |
 * | 1000       | 79.12 kb        | 40.05 kb        | ip2long     |
 * | 5000       | 515.37 kb       | 320.05 kb       | ip2long     |
 * | 10000      | 1030.68 kb      | 640.05 kb       | ip2long     |
 * | 50000      | 4512.42 kb      | 2560.08 kb      | ip2long     |
 * | 100000     | 9026.33 kb      | 5120.08 kb      | ip2long     |
 * | ---------- | --------------- | --------------- | ----------- |
 * 
 * Conclusion:
 * - "string" is the fastest way to store an IP address, but the
 *   difference with ip2Long is not huge (same order of magnitude).
 * - "ip2long" is the less memory-usage expensive solution which 
 *   almost divide by 2 the memory usage of the IP array.
 * - In this package, I'll use ip2long to store IP, because it doesn't
 *   highly impact the performances, but highly reduce the memory usage.
 * 
 * @author Damien MOLINA
 */

// Include the IP generation function.
include __DIR__ . '/benchmark.php' ;

// Different cache sizes for the benchmark.
$sizes = [
    200, 1000, 5000, 10000, 50000, 100000
] ;

/**
 * Measure the execution time of 'ip2long'.
 * 
 * @param array $cache
 * @param array $values
 * @return float
 */
function benchmark_ip2long(array $cache, array $values) {
    return measure($values, function($value) use ($cache) {
        return array_key_exists(ip2long($value), $cache) ;
    }) ;
}

/**
 * Measure the execution time with a standard string.
 * 
 * @param array $cache
 * @param array $values
 * @return float
 */
function benchmark_string(array $cache, array $values) {
    return measure($values, function($value) use ($cache) {
        return array_key_exists($value, $cache) ;
    }) ;
}

$results_exec = [
    'string' => [],
    'ip2long' => [],
] ;

$results_memory = [
    'string' => [],
    'ip2long' => [],
] ;

foreach($sizes as $size) {
    echo "[INFO] Generating size=$size\n" ;
    $cache = generate_ip_list($size) ;
    $values = add_cache_miss($cache) ;

    // Flip keys and values to use the array_key_exists function later.
    $cache = array_flip($cache) ;
    $cache = array_map(fn() => true, $cache) ;

    // Prepare ip2long cache.
    $cacheIpLong = [] ;
    foreach($cache as $key => $value) {
        $cacheIpLong[ip2long($key)] = true ;
    }

    // Add the memory usage results.
    $results_memory['string'][$size] = benchmark_memory_usage($cache) ;
    $results_memory['ip2long'][$size] = benchmark_memory_usage($cacheIpLong) ;

    // Add the execution time results.
    $results_exec['string'][$size] = benchmark_string($cache, $values) ;
    $results_exec['ip2long'][$size] = benchmark_ip2long($cacheIpLong, $values) ;
}

// Output the results.
echo "\nExecution time results:\n\n" ;
echo format_benchmark_results($results_exec, unit: 'ms') ;
echo "\n\nMemory usage results:\n\n" ;
echo format_benchmark_results($results_memory, unit: 'kb') ;