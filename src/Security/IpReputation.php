<?php

namespace Pythagus\LaravelWaf\Security;

use Illuminate\Support\Facades\Cache;
use Pythagus\LaravelWaf\Exceptions\WafConfigurationException;
use Pythagus\LaravelWaf\Security\Reputations\Feed;
use Pythagus\LaravelWaf\Support\ManagesFile;
use Pythagus\LaravelWaf\Support\ManagesIp;

/**
 * This class helps checking whether an IP is known
 * for suspicious activities.
 * 
 * @author Damien MOLINA
 */
class IpReputation extends BaseService {

    use ManagesFile, ManagesIp ;

    /**
     * The base config array key to retrieve
     * the configurations of the service.
     * 
     * @var string
     */
    protected $config = 'ip-reputation' ;

    /**
     * This is the cache key associated to the array
     * of the IP reported as suspicious.
     * 
     * @var string
     */
    public const CACHE_KEY = 'ip_reputation_database' ;

    /**
     * Retrieve the known IP addresses from the
     * storage facility.
     * 
     * @return array
     */
    protected function retrieveFromStorage() {
        $cache = [] ;

        $this->readFile(function($line) use (&$cache) {
            $cache[$line] = true ;
        }, $this->config('storage')) ;

        return $cache ;
    }

    /**
     * Determine whether the IP is known to be suspicious.
     *
     * @param string $ip
     * @return boolean
     */
    public function isKnown(string $ip) {
        // If the IP is private, we don't want to check whether it is
        // known for suspicious activities ; it's time consuming.
        if($this->isPrivateIp($ip)) {
            return false ;
        }

        // Get the value from the cache.
        $cache = Cache::rememberForever(static::CACHE_KEY, function() {
            return $this->retrieveFromStorage() ;
        }) ;

        return isset($cache[ip2long($ip)]) ;
    }

    /**
     * Retrieve suspicious IP addresses from the API.
     *
     * @return string[]
     */
    public function update() {
        $addresses = [] ;

        // Iterate on the declared feeders.
        foreach($this->config('feeds', default: []) as $feed) {
            try {
                $feeder = Feed::factory($feed)->update() ;

                // Merge the IP addresses with the one we
                // already found.
                $addresses = $addresses + $feeder->toArray() ;
            } catch(WafConfigurationException) {
                // We don't do anything for a configuration issue.
                // The user (would) have been warned with the waf:check
                // command.
            }
        }

        // Save the IP addresses in the storage facility if feature is enabled.
        if($path = $this->config('storage', default: null)) {
            file_put_contents($path, implode("\n", array_keys($addresses))) ;
        }

        // Finally, save the IP in the cache.
        Cache::forget(static::CACHE_KEY) ;
        Cache::forever(static::CACHE_KEY, $addresses) ;
    }
}
