<?php

namespace Pythagus\LaravelWaf\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Pythagus\LaravelWaf\Exceptions\BaseWafProtectionException;
use Pythagus\LaravelWaf\Exceptions\BlacklistedBehaviorException;
use Pythagus\LaravelWaf\Support\RegexMatcher;
use Pythagus\LaravelWaf\Support\Rules;

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
     * @var Rules
     */
    protected Rules $rules ;

    /**
     * Class helping to protect the header by testing regex
     * against strings or array of strings.
     *
     * @var RegexMatcher
     */
    protected RegexMatcher $matcher ;

    /**
     * Build a new middleware instance, and create the
     * helpers instances.
     * 
     * @param Rules $rules
     * @param RegexMatcher $matcher
     */
    public function __construct(Rules $rules, RegexMatcher $matcher) {
        $this->rules = $rules ;
        $this->matcher = $matcher ;
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

            return $next($request) ;
        } catch(BaseWafProtectionException $e) {
            #return response(status: 418) ;
            return response()->json([
                "Pas gentil",
                $e->getMessage(),
                $e::class,
            ], status: 418) ;
        }
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
            throw new BlacklistedBehaviorException("Accept-language: " . $language) ;
        }
    }

    /**
     * Protect the HTTP headers.
     *
     * @param Request $request
     * @return void
     */
    protected function protectHeaderParameters(Request $request) {
        // X-Forwarded-For value.
        $this->matcher->shouldMatch(
            regex: [Rules::X_FORWARDED_FOR => $this->rules->get(Rules::X_FORWARDED_FOR)],
            value: $request->server->get('HTTP_X_FORWARDED_FOR')
        ) ;

        // Accept-Language value.
        $this->matcher->shouldMatch(
            regex: [Rules::ACCEPT_LANGUAGE => $this->rules->get(Rules::ACCEPT_LANGUAGE)],
            value: $request->server->get('HTTP_ACCEPT_LANGUAGE')
        ) ;

        // User-Agent value.
        $this->matcher->shouldntMatch(
            regex: [Rules::USER_AGENT => $this->rules->get(Rules::USER_AGENT)],
            value: $request->server->get('HTTP_USER_AGENT'),
        ) ;
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
        $cleaned = urldecode(strtolower($uri)) ;

        // If the URL is worthless matching the regexees, then do nothing.
        if($this->rules->isWorthlessUrl($cleaned)) {
            return ;
        }

        // If the URL is quite large, it might be a possible attack trying to check
        // the limitation of the HTTP server handling huge requests.
        $this->rules->checkLongUrl($cleaned) ;

        // XSS, LFI, RCE, etc.
        $this->matcher->shouldntMatch(
            value: $cleaned, 
            regex: $this->rules->getUrlRules(),
        ) ;
    }
}