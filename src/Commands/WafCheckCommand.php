<?php

namespace Pythagus\LaravelWaf\Commands;

use Illuminate\Console\Command;
use Pythagus\LaravelWaf\Exceptions\WafConfigurationException;
use Pythagus\LaravelWaf\Security\HttpRules;
use Pythagus\LaravelWaf\Security\IpReputation;
use Pythagus\LaravelWaf\Security\Reputations\Feed;

/**
 * This command checks the WAF configurations.
 *
 * @author: Damien MOLINA
 */
class WafCheckCommand extends Command {

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'waf:check' ;

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Check the WAF configurations' ;

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
	 * Report an error to the user.
	 * 
	 * @param string $message
	 * @return void
	 */
	protected function report(string $message) {
		// Display the error to the CLI.
		$this->components->error($message) ;

		// And return an error status code.
		$this->status = Command::FAILURE ;
	} 

	/**
	 * Execute the console command.
	 *
	 * @return int
	 */
	public function handle() {
		$this->checkIpReputationConfiguration() ;
		$this->checkHttpRulesConfiguration() ;

		// Display a success message if no test failed.
		if($this->status == static::SUCCESS) {
			$this->components->success("All tests passed!") ;
		}

		return $this->status ;
	}

	/**
	 * Check whether the given configuration is a boolean.
	 * 
	 * @param string $key
	 * @return boolean
	 */
	protected function checkConfigBoolean(string $key) {
		if(filter_var($value = config("waf.$key"), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) {
			$value = is_null($value) ? "null" : "'" . json_encode($value) . "'" ;
			
			$this->report("Configuration issue: 'waf.$key' must be a boolean, $value given") ;
		}
	}

	/**
	 * Check whether the storage value is set.
	 * 
	 * @param string $module
	 * @param string|null $path
	 * @return void
	 */
	protected function checkStorage(string $module, string $path = null) {
		if(! $path || empty($path) || strlen(trim($path ?? "")) <= 0) {
			$this->report("$module: storage path is null") ;
		}
	}

	/**
	 * Check the IP reputation configurations.
	 * 
	 * @return void
	 */
	protected function checkIpReputationConfiguration() {
		// Check the config variables.
		$this->checkConfigBoolean('ip-reputation.enabled') ;
		$this->checkConfigBoolean('ip-reputation.auto-update') ;
		$this->checkStorage("IP reputation", $this->reputation->config('storage')) ;

		// Check the reputation feeds.
        foreach($this->reputation->config('feeds', default: []) as $feed) {
            try {
				// Only try to build the feeder instance. If
				// the feed is invalid, this will raise an
				// exception.
                Feed::factory($feed) ;
            } catch(WafConfigurationException) {
                $this->report("IP reputation: invalid feed '$feed'") ;
            }
        }
	}

	/**
	 * Check the HTTP rules configurations.
	 * 
	 * @return void
	 */
	protected function checkHttpRulesConfiguration() {
		// Check the config variables.
		$this->checkConfigBoolean('http-rules.blocking') ;
		$this->checkConfigBoolean('http-rules.logging') ;
		$this->checkConfigBoolean('http-rules.auto-update') ;
	}
}
