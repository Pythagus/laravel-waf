<?php

namespace Pythagus\LaravelWaf\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Pythagus\LaravelWaf\Security\HttpRules;
use Pythagus\LaravelWaf\Security\IpReputation;

/**
 * This command updates all the WAF dependencies at once.
 *
 * @author: Damien MOLINA
 */
class WafUpdateCommand extends Command {

	/**
	 * Key identifying the last update date in the
	 * cache related to the geolocation database.
	 * 
	 * @var string
	 */
	public const CACHE_GEOLOCATION_LAST_UPDATE_KEY = 'waf_geolocation_last_update_key' ;

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'waf:update' ;

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Updates the WAF dependencies: IP reputation, Geolocation, WAF rules, etc.' ;

	/**
	 * Global command status.
	 * 
	 * @var int
	 */
	protected $status ;

	/**
	 * Build the command instance and inject dependencies.
	 *
	 * @param IpReputation $reputation
	 */
	public function __construct(protected IpReputation $reputation, protected HttpRules $rules) {
		parent::__construct() ;

		// Command result status.
		$this->status = null ;
	}

	/**
	 * Perform the update and check for an exception.
	 * An exception shouldn't block the whole update process.
	 * 
	 * @param string $module
	 * @param callable $callback
	 * @return void
	 */
	protected function perform(string $module, callable $callback) {
		try {
			// Should the module be automatically updated regarding
			// the configurations the user set?
			if($this->moduleAutomaticUpdateAllowed($module)) {
				// Determine whether the module needs an update (all modules
				// are not updated each time this command is called).
				if($this->moduleShouldBeUpdated($module)) {
					$this->components->info("Updating '$module'...") ;
					call_user_func($callback) ;
					$this->components->success("'$module' updated!") ;
				} else {
					$this->components->info("'$module' NOT updated (didn't need an update)") ;
				}
			} else {
				$this->components->warn("'$module' NOT updated (disabled by config)") ;
			}
		} catch(\Throwable $t) {
			// Report the error, so that the admin can do something.
			report($t) ;

			// Display the error to the CLI.
			$this->components->error("An error occured: " . $t->getMessage()) ;

			// And return an error status code.
			$this->status = Command::FAILURE ;
		}
	}

	/**
	 * Execute the console command.
	 *
	 * @return int
	 */
	public function handle() {
		$this->status = Command::SUCCESS ;

		// We update the IP reputation every time this command is called.
		$this->perform('ip-reputation', fn() => $this->updateIpReputation()) ;

		// We update the HTTP rules every time this command is called.
		$this->perform('http-rules', fn() => $this->updateHttpRules()) ;

		// For the geolocation, we don't need to update the database
		// that frequently. So, just update it once a day.
		$this->perform('geolocation', fn() => $this->updateGeolocationDatabase()) ;
		
		return $this->status ;
	}

	/**
	 * Determine whether the module is allowed by the configurations
	 * to be updated by this command.
	 *
	 * @param string $module
	 * @return boolean
	 */
	protected function moduleAutomaticUpdateAllowed(string $module) {
		$enabled = array_key_exists('enabled', config("waf.$module", [])) 
			? config("waf.$module.enabled", default: false) 
			: true ;

		return config("waf.$module.auto-update", default: false) && $enabled ;
	}

	/**
	 * Determine whether a module should be updated.
	 * 
	 * @param string $module
	 * @return bool
	 */
	protected function moduleShouldBeUpdated(string $module) {
		// Manage specific module's updates.
		if($module == 'geolocation') {
			$date = Cache::get(static::CACHE_GEOLOCATION_LAST_UPDATE_KEY) ;

			// If this is the first time it was launched, or the
			// cache was cleared.
			if(is_null($date)) {
				return true ;
			}

			// 86100 = 86400 - 300
			// 86400 is the number of seconds in 24 hours
			// 300 : regarding the number of tasks launched at the same
			//       time, a call could be delayed of some seconds or
			//       minutes from its original schedule date. That's why
			//       there is a small "overlapping" possible period.
			//       300 = 5 * 60, the number of seconds in 5 minutes.
			return (time() - $date) >= 86100 ;
		}

		// By default, all modules are updated at every call of this command.
		return true ;
	}

	/**
	 * This method updates the IP reputation cache.
	 * 
	 * @return void
	 */
	protected function updateIpReputation() {
		$this->reputation->update() ;
	}

	/**
	 * This method updates the HTTP rules cache.
	 * 
	 * @return void
	 */
	protected function updateHttpRules() {
		$this->rules->update() ;
	}

	/**
	 * Update the geolocation database.
	 *
	 * @return void
	 */
	protected function updateGeolocationDatabase() {
		// Execute the location:update command if it exists.
		$this->callSilently('location:update') ;
		
		// Set the new update date in the cache.
		Cache::forget(static::CACHE_GEOLOCATION_LAST_UPDATE_KEY) ;
		Cache::forever(static::CACHE_GEOLOCATION_LAST_UPDATE_KEY, time()) ;
	}
}
