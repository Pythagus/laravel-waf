<?php

namespace Pythagus\LaravelWaf\Exceptions;

/**
 * Exception raised when a blacklist matched the
 * associated HTTP parameter. For example, blacklisted
 * Accept-Language or IP address.
 *
 * @author: Damien MOLINA
 */
class WafProtectionException extends BaseWafException {

    /**
     * Exception raised when a blacklist matched the
     * associated HTTP parameter. For example, blacklisted
     * Accept-Language or IP address.
     * 
     * @param string $property
     * @param string $value
     * @return static
     */
    public static function http(string $property, string $value) {
        return new static("Blacklisted HTTP property: $property => $value") ;
    }

    /**
     * Exception raised when the IP was found in the IP
     * reputation database.
     * 
     * @param string $ip
     * @return static
     */
    public static function blacklisted(string $ip) {
        return new static("Blacklisted IP address: $ip") ;
    }

    /**
     * Exception raised when an abnormally
     * long URL was received.
     * 
     * @return static
     */
    public static function long_url() {
        return new static("Long URL received") ;
    }
}