<?php

namespace Pythagus\LaravelWaf\Security;

use Pythagus\LaravelWaf\Support\ManagesIp;
use Stevebauman\Location\Facades\Location as GeoIP;
use Stevebauman\Location\Position as Location;

/**
 * This class helps gathering details on IP
 * address current location.
 * 
 * @author Damien MOLINA
 */
class Geolocation extends BaseService {

    use ManagesIp ;

    /**
     * The base config array key to retrieve
     * the configurations of the service.
     * 
     * @var string
     */
    protected $config = 'geolocation' ;

    /**
     * Get the location of the IP address. If it is
     * the default location, null will be returned instead
     * of a default location which doesn't have any sens.
     * 
     * @param string $ip
     * @return Location
     */
    public function locate(string $ip) {
        // If this is a private IP, we don't want to call
        // the geolocation, as the location of a private IP
        // doesn't have any sense.
        if(! $this->isPrivateIp($ip)) {
            try {
                $location = GeoIP::get($ip) ;

                // The geolocation driver returns "false" if no location
                // was found.
                if($location) {
                    return $location ;
                }
            } catch(\Throwable) {}
        }

        // Finally, if something unwanted happened,
        // simply return null.
        return null ;
    }

    /**
     * Determine whether the given IP is blocked by configuration.
     * 
     * @param string $ip
     * @return array|null
     */
    public function isBlocked(string $ip) {
        // Get the IP location.
        $location = $this->locate($ip) ;

        // If the location was not found, then do nothing.
        if(empty($location)) {
            return null ;
        }

        // If the country code is blocked.
        if($this->isCountryBlocked($location->countryCode)) {
            return ['country' => $location->countryCode] ;
        }

        // If the country name is blocked.
        if($this->isCountryBlocked($location->countryName)) {
            return ['country' => $location->countryName] ;
        }
    }

    /**
     * Determine whether the country is blocked by
     * the configurations.
     * 
     * @param string $country
     * @return bool
     */
    protected function isCountryBlocked(string $country) {
        return in_array($country, $this->config('rules.country', default: [])) ;
    }
}
