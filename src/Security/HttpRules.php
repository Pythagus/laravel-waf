<?php

namespace Pythagus\LaravelWaf\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Pythagus\LaravelWaf\Exceptions\WafConfigurationException;
use Pythagus\LaravelWaf\Models\WafRule;
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
        if(is_null($data) || ! array_key_exists('rules', $data ?? [])) {
            throw WafConfigurationException::invalidHttpRulesFeed() ;
        }

        // Save the rules in the database.
        $this->updateDatabaseFromArray(data_get($data, 'rules')) ;

        // Finally, clear the cache to renew it.
        $this->clearCache() ;
        $this->getCachedData() ;
    }

    /**
     * Update the rules stored in database.
     * 
     * @param array $rules
     * @return void
     */
    protected function updateDatabaseFromArray(array $rules) {
        $db_rules = [] ;
        
        // Get the rules in an associative array.
        WafRule::all()->each(function(WafRule $rule) use (&$db_rules) {
            $db_rules[$rule->id] = $rule ;
        }) ;

        foreach($rules as $rule) {
            /** @var WafRule|null $db_rule */
            $db_rule = data_get($db_rules, $id = data_get($rule, 'rule_id'), default: null) ;

            // If the rule already existed, then we have to update
            // the database record.
            if($db_rule) {
                // We only update the rule if it is configured to be
                // automatically updated.
                if($db_rule->isAutoUpdated()) {
                    $db_rule->type = data_get($rule, 'rule_type') ?? $db_rule->type ;
                    $db_rule->rule = data_get($rule, 'rule') ?? $db_rule->rule ;
                    $db_rule->save() ;
                }

                // Remove the rule from the already-treated ones.
                unset($db_rules[$id]) ;
            } else {
                // Otherwise, the rule didn't exist before, so we have
                // to create it.
                $new_rule = new WafRule([
                    'id' => $id,
                    'type' => data_get($rule, 'rule_type'),
                    'rule' => data_get($rule, 'rule'),
                    'status' => 'ACTIVE',
                    'auto_update' => true,
                ]) ;
                $new_rule->save() ;
            }
        }

        // If there are remaining rules, it means that they
        // were removed from the feed. So, let's remove them
        // from the database also!
        foreach($db_rules as $rule) {
            $rule->delete() ;
        }
    }

    /**
     * Invalid the cache, so that it is renewed next time
     * it will be accessed.
     * 
     * @return void
     */
    public function clearCache() {
        Cache::forget(static::CACHE_KEY) ;
    }

    /**
     * Get the cached data.
     * 
     * @return array
     */
    protected function getCachedData() {
        return Cache::rememberForever(static::CACHE_KEY, function() {
            return WafRule::query()->where('status', 'ACTIVE')->get()->map(function(WafRule $rule) {
                return $rule->only('id', 'type', 'rule') ;
            })->toArray() ;
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
            return $el['type'] == $type ;
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
            $same_type = is_null($type) ? true : $el['type'] == $type ;

            return $el['id'] == $id && $same_type ;
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
