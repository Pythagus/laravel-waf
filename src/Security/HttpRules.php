<?php

namespace Pythagus\LaravelWaf\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Pythagus\LaravelWaf\Exceptions\WafConfigurationException;
use Pythagus\LaravelWaf\Support\CallsRegex;
use Pythagus\LaravelWaf\Support\ManagesFile;
use Pythagus\LaravelWaf\Support\ManagesUrl;

/**
 * This class helps manipulating the rules to
 * ensure that the HTTP traffic is legitimate.
 * 
 * @author Damien MOLINA
 */
class HttpRules {

    use ManagesFile ;
    use ManagesUrl ;
    use CallsRegex ;

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
        return $this->readCsvFile(config('waf.http-rules.storage')) ;
    }

    /**
     * Retrieve the HTTP rules (regex) from the feeds.
     *
     * @return string[]
     */
    public function update() {
        $http_rules = [] ;

        // Iterate on the declared feeders.
        foreach(config('waf.http-rules.feeds', []) as $feed) {
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
                            $new_rule['rule'] = $new_rule['rule'] ;

                            // Set the new value.
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
        if($path = config('waf.http-rules.storage', null)) {
            // Format the output.
            $output = array_map(fn($rule) => $rule['rule_type'] . "," . $rule['rule_id'] . "," . $rule['rule'], $http_rules) ;

            // Add the header.
            array_unshift($output, implode(",", ['rule_type', 'rule_id', 'rule'])) ;

            file_put_contents($path, implode("\n", $output)) ;
        }

        // Finally, save the IP in the cache.
        Cache::forget(static::CACHE_KEY) ;
        Cache::forever(static::CACHE_KEY, $http_rules) ;
    }

    /**
     * Get the cached data.
     * 
     * @return array
     */
    protected function getCachedData() {
        return Cache::rememberForever(static::CACHE_KEY, function() {
            return $this->retrieveFromStorage() ;
        }) ;
    }

    /**
     * Get all the rules matching the given type.
     * 
     * @param string $type
     * @return array
     */
    public function getByType(string $type) {
        return array_values(array_filter($this->getCachedData(), function($el) use ($type) {
            return $el['rule_type'] == $type ;
        })) ;
    }

    /**
     * Get the unique rule matching the given id.
     * 
     * @param string $id
     * @return array
     */
    public function getById(string $id, string $type = null) {
        $values = array_filter($this->getCachedData(), function($el) use ($id, $type) {
            // Add an optional check on the type
            $same_type = is_null($type) ? true : $el['rule_type'] == $type ;

            return $el['rule_id'] == $id && $same_type ;
        }) ;

        return count($values) > 0 ? array_values($values)[0] : null ;
    }

    /**
     * Determine whether the URL is not worth analyzing.
     * 
     * @param string $cleaned
     * @return bool
     */
    public function isWorthlessUrl(string $cleaned) {
        return $this->isShortUrl($cleaned) || $this->isDefinedRoute($cleaned) || $this->isAssetUrl($cleaned) ;
    }

    /**
     * Determine whether the given value matches the "asset URL"
     * regex.
     * 
     * @param string $cleaned
     * @return bool
     */
    public function isAssetUrl(string $cleaned) {
        $rule = $this->getById(id: 'ASSET_URL', type: 'HTTP') ;

        return array_key_exists('rule', $rule) 
            ? $this->matches($rule['rule'], $cleaned) 
            : false ;
    }
}
