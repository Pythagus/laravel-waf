<?php

namespace Pythagus\LaravelWaf\Support;

use Pythagus\LaravelWaf\Exceptions\WafProtectionException;

/**
 * This class helps managing regex and test
 * them against given strings or array of strings.
 * 
 * @author Damien MOLINA
 */
class RegexMatcher {

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

        $expected_value = (int) ! $should_match ;

        foreach($regex as $key => $r) {
            if(preg_match($r, $value) == $expected_value) {
                throw WafProtectionException::http($key, $value) ;
            }
        }
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