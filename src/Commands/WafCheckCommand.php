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

		// Display a success message if no test failed.
		if($this->status == static::SUCCESS) {
			$this->components->success("All tests passed!") ;
		}

		return $this->status ;
	}

	protected function checkStorage(string $module, string $path = null) {
		if(! $path) {
			$this->components->warn("$module: storage path is null. Did you disable storage feature?") ;
		}
	}

	/**
	 * Check the IP reputation configurations.
	 * 
	 * @return void
	 */
	protected function checkIpReputationConfiguration() {
		// Check the storage variable.
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
}
