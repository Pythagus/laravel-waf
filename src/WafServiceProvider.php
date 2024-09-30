<?php

namespace Pythagus\LaravelWaf;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Pythagus\LaravelWaf\Middleware\WafMiddleware;

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
    public function register() {}

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        $this->bootConfigurations() ;
        $this->bootMiddlewares() ;

        if($this->app->runningInConsole()) {
            $this->bootCommands() ;
        }
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

    /**
     * Boot the configurations.
     *
     * @return void
     */
    protected function bootConfigurations() {
        $this->mergeConfigFrom($config = __DIR__.'/../config/waf.php', 'waf') ;

        if($this->app->runningInConsole()) {
            $this->publishes([
                $config => config_path('waf.php')
            ], "waf-config") ;
        }
    }

    /**
     * Boot the commands.
     *
     * @return void
     */
    protected function bootCommands() {
        $command = config('waf.updates.command') ;

        // Let the waf:update command be available on console.
        $this->commands($command) ;

        // If the automatic updates are disabled, then do not
        // schedule the update command.
        if(! config('waf.updates.automatic', default: false)) {
            return ;
        }

        $this->app->booted(function() use ($command) {
            /** @var Schedule $schedule */
            $schedule = $this->app->make(Schedule::class) ;
            $schedule->command($command)->cron(config('waf.updates.cron')) ;
        }) ;
    }
}