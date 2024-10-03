<?php

namespace Pythagus\LaravelWaf\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Pythagus\LaravelWaf\Exceptions\WafConfigurationException;
use Pythagus\LaravelWaf\Support\CallsRegex;
use Pythagus\LaravelWaf\Support\ManagesUrl;

/**
 * This class helps manipulating the rules to
 * ensure that the HTTP traffic is legitimate.
 * 
 * @author Damien MOLINA
 */
class HttpRules extends BaseService {

    use ManagesUrl, CallsRegex ;

    /**
     * The base config array key to retrieve
     * the configurations of the service.
     * 
     * @var string
     */
    protected $config = 'http-rules' ;

    /**
     * This is the cache key associated to the array
     * of the HTTP regex.
     * 
     * @var string
     */
    public const CACHE_KEY = 'waf_http_rules' ;

    /**
     * Retrieve the HTTP rules (regex) from the feeds.
     *
     * @return string[]
     */
    public function update() {
        $url = $this->config('feed', default: null) ;

        // If the URL is missing but the package needed to be
        // updated by configuration, then raise an exception.
        if(! $url) {
            throw WafConfigurationException::missingHttpRulesFeed() ;
        }

        // Request the feed.
        $temporary_name = tempnam(sys_get_temp_dir(), 'waf-rules') ;
        $response = Http::sink($temporary_name)->get($url) ;

        // Ensure that the request was successful, or throw
        // an exception otherwise.
        $response->throw() ;

        // Try to get the data as a JSON.
        $data = $response->json() ;

        // But if nothing was found, then it's probably that
        // the response was not a valid JSON.
        if(is_null($data)) {
            throw WafConfigurationException::invalidHttpRulesFeed() ;
        }

        // Save the rules in the storage facility if feature is enabled.
        if($path = $this->config('storage', default: null)) {
            // Move the file to its new location.
            rename($temporary_name, $path) ;
        }

        // Finally, save the IP in the cache.
        Cache::forget(static::CACHE_KEY) ;
        Cache::forever(static::CACHE_KEY, $response->json('rules')) ;
    }

    /**
     * Get the cached data.
     * 
     * @return array
     */
    protected function getCachedData() {
        return Cache::rememberForever(static::CACHE_KEY, function() {
            if($path = $this->config('storage', default: null)) {
                $json = json_decode(file_get_contents($path), true) ;

                return data_get($json, 'rules', default: []) ;
            }

            return [] ;
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
