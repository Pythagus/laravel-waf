<?php

namespace Pythagus\LaravelWaf\Security;

use Illuminate\Support\Facades\Cache;
use Pythagus\LaravelWaf\Exceptions\WafConfigurationException;
use Pythagus\LaravelWaf\Security\Reputations\Feed;

/**
 * This class helps checking whether an IP is known
 * for suspicious activities.
 * 
 * @author Damien MOLINA
 */
class IpReputation {

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
        // If the cache is empty, it was probably cleared. Let's
        // fill the cache again.
        $path = config('waf.ip-reputation.storage', null) ;

        // If the path is null, then the backup plan was disabled
        // by the user. Then, return an empty array.
        if(! $path) {
            return [] ;
        }

        // Prepare the new cache data.
        $cache = [] ;

        // Open the file in read mode.
        if($handle = fopen($path, 'r')) {
            while(($line = fgets($handle)) !== false) {
                if(! isset($cache[$line])) {
                    $cache[$line] = true ;
                }
            }

            fclose($handle) ;

            return $cache ;
        }
        
        throw WafConfigurationException::storage($path) ;
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
        if(! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
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
        foreach(config('waf.ip-reputation.feeds', []) as $feed) {
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
        if($path = config('waf.reputation.storage')) {
            file_put_contents($path, implode("\n", array_keys($addresses))) ;
        }

        // Finally, save the IP in the cache.
        Cache::forget(static::CACHE_KEY) ;
        Cache::forever(static::CACHE_KEY, $addresses) ;
    }
}
