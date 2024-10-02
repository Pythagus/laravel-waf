<?php

declare(strict_types=1) ;

/**
 * This scripts aims to compare the usage of in_array and
 * array_key_exists functions in order to determine whether
 * a value exists in the cache.
 * 
 * Here is an overview of the output of the script:
 * 
 * | ---------- | --------------- | ---------------- | ---------------- |
 * | Cache size | in_array        | array_key_exists | Best choice      |
 * | ---------- | --------------- | ---------------- | ---------------- |
 * | 200        | 1.43 ms         | 0.02 ms          | array_key_exists |
 * | 1000       | 28.33 ms        | 0.07 ms          | array_key_exists |
 * | 5000       | 655.34 ms       | 0.36 ms          | array_key_exists |
 * | 10000      | 2801.27 ms      | 0.83 ms          | array_key_exists |
 * | 50000      | 66823.91 ms     | 3.4 ms           | array_key_exists |
 * | ---------- | --------------- | ---------------- | ---------------- |
 * 
 * Conclusion: array_key_exists is the best choice.
 * 
 * @author Damien MOLINA
 */

// Include the IP generation function.
include __DIR__ . '/benchmark.php' ;

// Different cache sizes for the benchmark.
$sizes = [
    200, 1000, 5000, 10000, 50000
] ;

/**
 * Measure the execution time of 'in_array'.
 * 
 * @param array $cache
 * @param array $values
 * @return float
 */
function benchmark_inarray(array $cache, array $values) {
    return measure($values, function($value) use ($cache) {
        return in_array($value, $cache) ;
    }) ;
}

/**
 * Measure the execution time of 'array_key_exists'.
 * 
 * @param array $cache
 * @param array $values
 * @return float
 */
function benchmark_arraykeyexists(array $cache, array $values) {
    return measure($values, function($value, $key) use ($cache) {
        return array_key_exists($key, $cache) ;
    }) ;
}

$results = [
    'in_array' => [],
    'array_key_exists' => [],
] ;

foreach($sizes as $size) {
    echo "[INFO] Generating size=$size\n" ;
    $cache = generate_ip_list($size) ;
    $values = add_cache_miss($cache) ;

    $cacheKeys = array_flip($cache) ;
    $cacheKeys = array_map(fn() => true, $cacheKeys) ;

    $results['in_array'][$size] = benchmark_inarray($cache, $values) ;
    $results['array_key_exists'][$size] = benchmark_arraykeyexists($cache, $values) ;
}

// Output the results.
echo format_benchmark_results($results, unit: 'ms') ;