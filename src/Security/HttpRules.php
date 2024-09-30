<?php

namespace Pythagus\LaravelWaf\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Pythagus\LaravelWaf\Exceptions\WafConfigurationException;
use Pythagus\LaravelWaf\Support\ManagesFile;

/**
 * This class helps manipulating the rules to
 * ensure that the HTTP traffic is legitimate.
 * 
 * @author Damien MOLINA
 */
class HttpRules {

    use ManagesFile ;

    /**
     * This is the cache key associated to the array
     * of the HTTP regex.
     * 
     * @var string
     */
    public const CACHE_KEY = 'waf_http_rules' ;

    /**
     * Retrieve the known IP addresses from the
     * storage facility.
     * 
     * @return array
     */
    protected function retrieveFromStorage() {
        // If the cache is empty, it was probably cleared. Let's
        // fill the cache again.
        $path = config('waf.rules.storage') ;

        // If the path is null, then the backup plan was disabled
        // by the user. Then, return an empty array.
        if(! $path) {
            return [] ;
        }

        // Prepare the new cache data.
        $cache = [] ;

        // Open the file in read mode.
        if($handle = fopen($path, 'r')) {
            while(($line = fgetcsv($handle, 1000, ",")) !== false) {
                dd($line) ;
            }

            fclose($handle) ;

            return $cache ;
        }
        
        throw WafConfigurationException::storage($path) ;
    }

    /**
     * Retrieve the HTTP rules (regex) from the feeds.
     *
     * @return string[]
     */
    public function update() {
        $http_rules = [] ;

        // Iterate on the declared feeders.
        foreach(config('waf.rules.feeds', []) as $feed) {
            try {
                $temporary_name = tempnam(sys_get_temp_dir(), 'waf-rules') ;
                $response = Http::sink($temporary_name)->get($feed) ;

                // If the feed is valid.
                if($response->successful()) {
                    $_rules = $this->readCsvFile($temporary_name) ;

                    foreach($_rules as $key => $rule) {
                        $new_rule = array_filter($rule, function($key) {
                            return in_array($key, ['rule_type', 'rule_id', 'rule']) ;
                        }, ARRAY_FILTER_USE_KEY) ;

                        // If all required fields were found in the array.
                        if(count($new_rule) == 3) {
                            $_rules[$key] = $new_rule ;
                        } else {
                            // Otherwise, we cannot take the rule into account.
                            unset($_rules[$key]) ;
                        }
                    }

                    $http_rules = $http_rules + $_rules ;
                }
            } catch(WafConfigurationException) {
                // We don't do anything for a configuration issue.
                // The user (would) have been warned with the waf:check
                // command.
            }
        }

        // Save the rules in the storage facility if feature is enabled.
        if($path = config('waf.rules.storage')) {
            // Format the output.
            $output = array_map(fn($rule) => $rule['rule_type'] . "," . $rule['rule_id'] . "," . $rule['rule'], $http_rules) ;

            file_put_contents($path, implode("\n", $output)) ;
        }

        // Finally, save the IP in the cache.
        Cache::forget(static::CACHE_KEY) ;
        Cache::forever(static::CACHE_KEY, $http_rules) ;
    }
}
