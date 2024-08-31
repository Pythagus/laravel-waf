<?php

namespace Pythagus\LaravelWaf;

use Illuminate\Support\ServiceProvider;
use Pythagus\LaravelWaf\Http\Middleware\WafMiddleware;

/**
 * Laravel base service provider.
 * 
 * @author Damien MOLINA
 */
class WafServiceProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        $this->bootMiddlewares() ;
    }

    /**
     * Boot the middlewares.
     * 
     * @return void
     */
    protected function bootMiddlewares() {
        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app['router'] ;

        $router->pushMiddlewareToGroup('web', WafMiddleware::class) ;
    }
}