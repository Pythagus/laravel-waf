<?php

namespace Pythagus\LaravelWaf\Support;


/**
 * This class helps managing IP addresses.
 * 
 * @author Damien MOLINA
 */
trait ManagesIp {

    /**
     * Determine whether the given IP is private.
     * 
     * @param string $ip
     * @return bool
     */
    public function isPrivateIp(string $ip) {
        return ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
            && ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE) ;
    }
}