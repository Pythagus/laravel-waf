<?php

namespace Pythagus\LaravelWaf\Middleware;

use Illuminate\Http\Request;
use Pythagus\LaravelWaf\Exceptions\WafConfigurationException;
use Pythagus\LaravelWaf\Exceptions\WafProtectionException;
use Pythagus\LaravelWaf\Security\HttpRules;
use Pythagus\LaravelWaf\Security\IpReputation;

/**
 * Base WAF middleware that will detect and block
 * malicious requests coming to your application.
 *
 * @author: Damien MOLINA
 */
class WafMiddleware {

    /**
     * This is an helper class managing the rules.
     *
     * @var HttpRules
     */
    protected HttpRules $rules ;

    /**
     * Helper managing the reputation cache and interactions
     * with it.
     *
     * @var IpReputation
     */
    protected IpReputation $reputation ;

    /**
     * Build a new middleware instance, and create the
     * helpers instances.
     * 
     * @param HttpRules $rules
     * @param IpReputation $reputation
     */
    public function __construct(HttpRules $rules, IpReputation $reputation) {
        $this->rules = $rules ;
        $this->reputation = $reputation ;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, \Closure $next) {
        try {
            $this->applyBlacklists($request) ;
            $this->protectHeaderParameters($request) ;
            $this->protectAccessedUri($request->getRequestUri()) ;

        } catch(WafProtectionException $e) {
            # Officially, the HTTP code 400 is meant for requests
            # that don't respect what the server was expecting or
            # was considered unsafe. So, that's perfect!
            // TODO: add metrics for the admin
            abort(400) ;
            
        } catch(WafConfigurationException $e) {
            # Report the exception, so that the admin can manage the issues.
            report($e) ;

            // If the application is not in production, then
            // raise an error. Otherwise, we should keep serving
            // the request, because a configuration issue in the 
            // WAF shouldn't impact the traffic, legitimate or not.
            if(app()->isLocal()) {
                throw $e ;
            }
        }

        return $next($request) ;
    }

    /**
     * Apply the blacklist asked by the administrator.
     *
     * @param Request $request
     * @return void
     */
    protected function applyBlacklists(Request $request) {
        // Apply Accept-Language blacklist.
        $language = $request->server->get('HTTP_ACCEPT_LANGUAGE') ;
        if(str_contains($language, "frs")) {
            throw WafProtectionException::http("Accept-language", $language) ;
        }

        # Apply reputation blacklist.
        if($this->reputation->isKnown($ip = $request->getClientIp())) {
            throw WafProtectionException::blacklisted($ip) ;
        }

        # TODO geofencing with MaxMind : https://github.com/stevebauman/location
    }

    /**
     * Protect the HTTP headers.
     *
     * @param Request $request
     * @return void
     */
    protected function protectHeaderParameters(Request $request) {
        // X-Forwarded-For value.
        if($rule = $this->rules->getById(id: "X_FORWARDED_FOR", type: "HTTP")) {
            $this->rules->shouldMatch(
                regex: [$rule], 
                value: $request->server->get('HTTP_X_FORWARDED_FOR')
            ) ;
        }

        // Accept-Language value.
        if($rule = $this->rules->getById(id: "ACCEPT_LANGUAGE", type: "HTTP")) {
            $this->rules->shouldMatch(
                regex: [$rule], 
                value: $request->server->get('HTTP_ACCEPT_LANGUAGE')
            ) ;
        }

        // Content-Type value.
        if($rule = $this->rules->getById(id: "CONTENT_TYPE", type: "HTTP")) {
            $this->rules->shouldMatch(
                regex: [$rule], 
                value: $request->header('Content-Type')
            ) ;
        }

        // User-Agent value.
        if($rule = $this->rules->getById(id: "USER_AGENT", type: "HTTP")) {
            $this->rules->shouldntMatch(
                regex: [$rule], 
                value: $request->server->get('HTTP_USER_AGENT')
            ) ;
        }
    }

    /**
     * Protected the URL against some HTTP attacks like
     * LFI, SQL injections, RCE, etc.
     *
     * @param string $uri
     * @return void
     */
    protected function protectAccessedUri(string $uri) {
        // Clean the URL.
        $cleaned = trim(strtolower(urldecode($uri))) ;

        // If the URL is worthless matching the regexees, then do nothing.
        if($this->rules->isWorthlessUrl($cleaned)) {
            return ;
        }

        // If the URL is quite large, it might be a possible attack trying to check
        // the limitation of the HTTP server handling huge requests.
        if($this->rules->isLongUrl($cleaned)) {
            throw WafProtectionException::long_url() ;
        }

        // Test well-known malicious epxloits like XSS, LFI, RCE, etc.
        $this->rules->shouldntMatch($this->rules->getByType('LFI'), $cleaned) ;
        $this->rules->shouldntMatch($this->rules->getByType('XSS'), $cleaned) ;
        $this->rules->shouldntMatch($this->rules->getByType('SQLI'), $cleaned) ;
        $this->rules->shouldntMatch($this->rules->getByType('RCE'), $cleaned) ;
    }
}