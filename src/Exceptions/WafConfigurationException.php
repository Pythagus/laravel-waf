<?php

namespace Pythagus\LaravelWaf\Exceptions;

/**
 * Exception raised when a blacklist matched the
 * associated HTTP parameter. For example, blacklisted
 * Accept-Language or IP address.
 *
 * @author: Damien MOLINA
 */
class WafConfigurationException extends BaseWafException {

    /**
     * Exception raised if the storage file was defined
     * in the configuration but couldn't be open.
     * 
     * @param string $file
     * @return static
     */
    public static function storage(string $file) {
        return new static("WAF: storage file '$file' couldn't be open") ;
    }

    /**
     * Exception raised when an invalid value was set
     * in the IP reputation feed list.
     * 
     * @param string $feed
     * @return static
     */
    public static function reputationFeed(string $feed) {
        return new static("WAF: Undefined IP reputation feed '$feed'. Expecting an URL or a predefined feed.") ;
    }

    /**
     * Exception raised when an invalid value was set
     * in the HTTP rules feed list.
     * 
     * @return static
     */
    public static function missingHttpRulesFeed() {
        return new static("WAF: Missing HTTP rules feed URL.") ;
    }

    /**
     * Exception raised when an the HTTP rules feed didn't return
     * a valid JSON file.
     * 
     * @return static
     */
    public static function invalidHttpRulesFeed() {
        return new static("WAF: Invalid HTTP rules feed URL (expected JSON format).") ;
    }
}