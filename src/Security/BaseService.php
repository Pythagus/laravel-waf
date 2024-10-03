<?php

namespace Pythagus\LaravelWaf\Security;

/**
 * This class helps managing security services and
 * their configurations.
 * 
 * @author Damien MOLINA
 */
abstract class BaseService {

    /**
     * The base config array key to retrieve
     * the configurations of the service.
     * 
     * @var string|null
     */
    protected $config = null ;

    /**
     * Get a configuration for the current service.
     * 
     * @param string $key
     * @param mixed $default
     * @param string|null $parent : overriding the $config property.
     * @return mixed
     */
    public function config(string $key, $default = null, string $parent = null) {
        $base = $parent ?? $this->config ;

        return config('waf.' . ($base ? $base . "." : "") . $key, default: $default) ;
    }
}
