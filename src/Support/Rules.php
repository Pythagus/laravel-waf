<?php

namespace Pythagus\LaravelWaf\Support;

use Pythagus\LaravelWaf\Exceptions\WafProtectionException;

/**
 * Rules helper.
 * 
 * @author Damien MOLINA
 */
class Rules {

    /**
     * These are the keys of the rules in
     * the storage.
     * 
     * @var string
     */
    public const X_FORWARDED_FOR = "xff" ;
    public const USER_AGENT = "useragent" ;
    public const ACCEPT_LANGUAGE = "language" ;
    public const XSS = "XSS" ;
    public const LFI = "LFI" ;
    public const RCE = "RCE" ;
    public const SQLI = "SQLI" ;

    /**
     * Rules list.
     *
     * @var array
     */
    protected array $rules = [] ;

    /**
     * Build a new rule helper and load the rules
     * into it when it is constructed.
     */
    public function __construct() {
        $this->loadFromFile(__DIR__ . "/../patterns.csv", function($data) {
            // id, type, regex, active.
            if($data[3] == 1) {
                if(! array_key_exists($data[1], $this->rules)) {
                    $this->rules[$data[1]] = [] ;
                }

                $this->rules[$data[1]][] = "%(" . $data[2] . ")%" ;
            }
        }) ;

        $this->loadFromFile(__DIR__ . "/../rules.csv", function($data) {
            // id, regex
            $this->rules[$data[0]] = "%(" . $data[1] . ")%" ;
        }) ;
    }

    private function loadFromFile(string $file, callable $manage) {
        $row = 0 ;

        if(($handle = fopen($file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row++ ;

                // Avoid the CSV header.
                if($row == 1) {
                    continue ;
                }

                $manage($data) ;

                $row++ ;
            }

            fclose($handle);
        }
    }

    /**
     * Get the rule identified by the given key.
     *
     * @param string $key
     * @return string|string[]
     */
    public function get(string $key) {
        return $this->rules[$key] ;
    }

    /**
     * Get the rules linked to the URL.
     *
     * @return array
     */
    public function getUrlRules() {
        return array_merge(
            $this->get(static::LFI),
            $this->get(static::XSS),
            $this->get(static::RCE),
            $this->get(static::SQLI),
        ) ;
    }

    /**
     * Determine whether the URL is worthless analyzing.
     *
     * @param string|null $cleaned
     * @return boolean
     */
    public function isWorthlessUrl(string $cleaned = null) {
        return is_null($cleaned) || in_array($cleaned, ["", "/"]) || strlen($cleaned) <= 5 ;
    }

    /**
     * If the URL is quite large, it might be a possible attack trying to check
     * the limitation of the HTTP server handling huge requests.
     *
     * @param string $url
     * @return void
     */
    public function checkLongUrl(string $url) {
        if(strlen($url) > 256) {
            // Mode = 1 means that all the characters with count=0
            // won't be returned by the function. 
            $chars = array_filter(count_chars($url, mode: 1), fn($v) => $v > 256) ; 

            // If there is a character that is highly present in the
            // url (more than 256 times the same one), then it's probably
            // an attack for server vulnerabilities.
            if(count($chars) > 0) {
                throw WafProtectionException::long_url() ;
            }
        }
    }
}