<?php

namespace Pythagus\LaravelWaf\Support;

use Illuminate\Support\Facades\Route;

/**
 * This class helps managing URL and all checks.
 * 
 * @author Damien MOLINA
 */
trait ManagesUrl {

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
     * Determine whether the currently visited URL is
     * defined in the routes/web.php (for example).
     * 
     * @param string $cleaned
     * @return bool
     */
    public function isDefinedRoute(string $cleaned) {
        $cleaned = trim($cleaned, "/") ;
        $routes = Route::getRoutes() ;

        foreach($routes as $route) {
            $uri = trim($route->uri(), "/") ;

            if(! str_contains($uri, '{') && ($uri == $cleaned)) {
                return true ;
            }
        }
        
        return false ;
    }

    /**
     * If the URL is quite large, it might be a possible attack trying to check
     * the limitation of the HTTP server handling huge requests.
     *
     * @param string $url
     * @return bool
     */
    public function isLongUrl(string $url) {
        if(strlen($url) > 256) {
            // Mode = 1 means that all the characters with count=0
            // won't be returned by the function. 
            $chars = array_filter(count_chars($url, mode: 1), fn($v) => $v > 256) ; 

            // If there is a character that is highly present in the
            // url (more than 256 times the same one), then it's probably
            // an attack for server vulnerabilities.
            return count($chars) > 0 ;
        }

        return false ;
    }
}