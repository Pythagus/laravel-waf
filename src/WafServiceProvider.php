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
            $this->bootMigrations() ;
        }
    }

    /**
     * Set the migration files to publish.
     * 
     * @return void
     */
    protected function bootMigrations() {
        $this->publishesMigrations([
            __DIR__.'/../migrations' => database_path('migrations'),
        ], "waf-migrations") ;
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

        // This overrides the default stevebauman/location location path for
        // MaxMind, because it storres the database in the /database/maxmind 
        // folder which is not "compliant" with what we do in this package.
        if(config('waf.geolocation.override-maxmind-path', default: true)) {
            config([
                'location.maxmind.local.path' => storage_path('framework/cache/maxmind.mmdb'),
            ]) ;
        }
        
        // If the application is running in the console, allow the user
        // to publish the configs.
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
        // Let the waf commands be available on console.
        $this->commands([
            \Pythagus\LaravelWaf\Commands\WafUpdateCommand::class,
            \Pythagus\LaravelWaf\Commands\WafCheckCommand::class,
        ]) ;

        // If the automatic updates are enabled, then schedule
        // the update command regarding the crontab value.
        if(config('waf.updates.automatic', default: false)) {
            $this->app->booted(function() {
                /** @var Schedule $schedule */
                $schedule = $this->app->make(Schedule::class) ;
                $schedule->command('waf:update')->cron(config('waf.updates.cron')) ;
            }) ;
        }

        // Add some context about the package if the AboutCommand exists.
        if(class_exists("\Illuminate\Foundation\Console\AboutCommand")) {
            \Illuminate\Foundation\Console\AboutCommand::add('WAF', $this->getDetailsOnApp()) ;
        }
    }

    /**
     * Get details on the application for the about command.
     * 
     * @return array
     */
    protected function getDetailsOnApp(): array {
        $details = [] ;
        
        // Some status functions.
        $enabledStatus = fn($enabled) => $enabled ? '<fg=green;options=bold>ENABLED</>' : 'DISABLED' ;
        
        // Add configuration details.
        $details['HTTP rules'] = $enabledStatus(config('waf.http-rules.blocking')) ;
        $details['IP reputation'] = $enabledStatus(config('waf.ip-reputation.enabled')) ;
        $details['Geolocation'] = $enabledStatus(config('waf.geolocation.enabled')) ;

        return $details ;
    }
}