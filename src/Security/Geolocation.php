<?php

namespace Pythagus\LaravelWaf\Security;

use Stevebauman\Location\Facades\Location as GeoIP;
use Stevebauman\Location\Position as Location;

/**
 * This class helps gathering details on IP
 * address current location.
 * 
 * @author Damien MOLINA
 */
class Geolocation {

    /**
     * Determine whether the given IP is private.
     * 
     * @param string $ip
     * @return bool
     */
    public static function isPrivateIp(string $ip) {
        return ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
            && ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE) ;
    }

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
        if(! Geolocation::isPrivateIp($ip)) {
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
        return in_array($country, config('waf.geolocation.rules.country', [])) ;
    }
}
