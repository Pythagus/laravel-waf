<?php

namespace Pythagus\LaravelWaf\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Pythagus\LaravelWaf\Support\IpReputation;

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
	protected $description = 'Updates the WAF dependencies: AbuseIPDB, Geolocation, WAF rules, etc.' ;

	/**
	 * Build the command instance and inject dependencies.
	 *
	 * @param IpReputation $reputation
	 */
	public function __construct(protected IpReputation $reputation) {
		parent::__construct() ;
	}

	/**
	 * Report the exception, but continue the execution.
	 *
	 * @param \Throwable $t
	 * @return void
	 */
	protected function reportError(\Throwable $t) {
		report($t) ;
		$this->components->error("An error occured: " . $t->getMessage()) ;
	}

	/**
	 * Execute the console command.
	 *
	 * @return int
	 */
	public function handle() {
		$status = Command::SUCCESS ;

		try {
			// We update AbuseIPDB every time this command is called.
			if($this->moduleUpdateAllowed('abuseipdb') && config('waf.abuseipdb.reputation.enabled', default: false)) {
				$this->updateAbuseIPDB() ;
				$this->components->info("AbuseIPDB : reputation database updated") ;
			} else {
				$this->components->warn("AbuseIPDB : reputation database NOT updated (disabled by config)") ;
			}
		} catch(\Throwable $t) {
			$this->reportError($t) ;
			$status = Command::FAILURE ;
		}

		try {
			// For the geolocation, we don't need to update the database
			// that frequently. So, just update it once a day.
			if($this->moduleUpdateAllowed('geolocation')) {
				if($this->shouldUpdateGeolocation()) {
					$this->updateGeolocationDatabase() ;
					$this->components->info("Geolocation : database updated") ;
				} else {
					$this->components->warn("Geolocation : database not updated (didn't need an update)") ;
				}
			} else {
				$this->components->warn("Geolocation : database NOT updated (disabled by config)") ;
			}
		} catch(\Throwable $t) {
			$this->reportError($t) ;
			$status = Command::FAILURE ;
		}
		
		return $status ;
	}

	/**
	 * Determine whether the module is allowed by the configurations
	 * to be updated by this command.
	 *
	 * @param string $module
	 * @return boolean
	 */
	protected function moduleUpdateAllowed(string $module) {
		return boolval(config("waf.updates.modules.$module", default: false)) ;
	}

	/**
	 * This method update the AbuseIPDb reputation cache
	 * we kept.
	 * 
	 * @return void
	 */
	protected function updateAbuseIPDB() {
		$this->reputation->updateFromApi() ;
	}

	/**
	 * Determine whether the geolocation database
	 * should be updated regarding the cache data.
	 *
	 * @return boolean
	 */
	protected function shouldUpdateGeolocation() {
		// If the configs say that we shouldn't update the geolocation.
		if(! config('waf.updates.modules.geolocation', default: false)) {
			return false ;
		}

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

	/**
	 * Update the geolocation database.
	 *
	 * @return void
	 */
	protected function updateGeolocationDatabase() {
		// TODO

		// Set the new update date in the cache.
		Cache::forget(static::CACHE_GEOLOCATION_LAST_UPDATE_KEY) ;
		Cache::forever(static::CACHE_GEOLOCATION_LAST_UPDATE_KEY, time()) ;
	}
}
