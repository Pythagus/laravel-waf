<?php

namespace Pythagus\LaravelWaf\Support;

use Pythagus\LaravelWaf\Exceptions\WafProtectionException;

/**
 * This class helps calling regexes.
 * 
 * @author Damien MOLINA
 */
trait CallsRegex {

    /**
     * Local method used to match a list of regexes on
     * the given value.
     *
     * @param array $regex
     * @param string|null $value
     * @param boolean $should_match
     * @return void
     */
    protected function _match(array $regex, string $value = null, bool $should_match = true) {
        if($value == null || $value == "") {
            return ;
        }

        $expected_value = (int) $should_match ;

        foreach($regex as $r) {
            if($this->matches($r['rule'], $value) != $expected_value) {
                throw WafProtectionException::http($r['id'], $value) ;
            }
        }
    }

    /**
     * Determine whether the given regex matches the given value.
     * 
     * @param string $regex
     * @param string $value
     * @return bool
     */
    public function matches(string $regex, string $value) {
        if($value == null || $value == "") {
            return false ;
        }

        // Decode the regex, as it is encoded in cache/storage.
        $regex = hex2bin($regex) ;
        
        return preg_match("&(" . $regex . ")&", $value) ;
    }

    /**
     * Ensure that the given value matches the regex. Otherwise,
     * an exception is raised.
     *
     * @param array $regex
     * @param string|null $value
     * @return void
     */
    public function shouldMatch(array $regex, string $value = null) {
        $this->_match($regex, $value, true) ;
    }

    /**
     * Ensure that the given value doesn't match the regex. Otherwise,
     * an exception is raised.
     *
     * @param array $regex
     * @param string|null $value
     * @return void
     */
    public function shouldntMatch(array $regex, string $value = null) {
        $this->_match($regex, $value, false) ;
    }
}