<?php

namespace Pythagus\LaravelWaf\Security\Reputations;

use Pythagus\LaravelWaf\Exceptions\WafConfigurationException;

/**
 * Base class of all predefined IP reputation feeds.
 * 
 * @author Damien MOLINA
 */
abstract class Feed {

    /**
     * List of predefined feeders.
     * 
     * @property string[]
     */
    public static $predefined_feeders = [
        'abuseipdb' => FeedAbuseIPDB::class,
    ] ;

    /**
     * Addresses retrieved from the feeder.
     * 
     * @property array[]
     */
    protected $addresses = [] ;

    /**
     * Update the feeder.
     * 
     * @return static
     */
    abstract public function update() ;

    /**
     * Build an instance of the appropriate feed regarding
     * the given value.
     * 
     * @param string $feed
     * @return static
     */
    public static function factory(string $feed) {
        if(array_key_exists($feed, static::$predefined_feeders)) {
            return new static::$predefined_feeders[$feed] ;
        }

        // If the feed is an URL.
        if(str_starts_with($feed, "http")) {
            return new FeedCustomUrl($feed) ;
        }

        // If no handler was found, then raise an exception.
        throw WafConfigurationException::reputationFeed($feed) ;
    }

    /**
     * Add an IP to the local "cache" of the feeder
     * instance.
     * 
     * @param string $ip
     * @return self
     */
    public function add(string $ip) {
        $this->addresses[ip2long($ip)] = true ;

        return $this ;
    }

    /**
     * Get the addresses added from the feeder.
     * 
     * @return array
     */
    public function toArray() {
        return $this->addresses ;
    }
}
