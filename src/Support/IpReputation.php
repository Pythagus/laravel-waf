<?php

namespace Pythagus\LaravelWaf\Support;

use AbuseIPDB\Facades\AbuseIPDB;
use Illuminate\Support\Facades\Cache;

/**
 * This class helps checking whether an IP is known
 * for suspicious activities.
 * 
 * @author Damien MOLINA
 */
class IpReputation {

    /**
     * This is the cache key associated to the array
     * of the IP reported as suspicious.
     * 
     * @var string
     */
    public const CACHE_KEY = 'ip_reputation_database' ;

    /**
     * Time-to-live value of a cached entry.
     *
     * @var int
     */
    protected $ttl ;

    /**
     * Path to the backup file.
     *
     * @var string|null
     */
    protected $backup_path ;

    /**
     * Build the reputation helper and set the default properties.
     * 
     * @return void
     */
    public function __construct() {
        $this->ttl = intval(config('waf.abuseipdb.reputation.ttl', 0)) ;
        $this->backup_path = config('waf.abuseipdb.reputation.backup_file') ;
    }

    /**
     * Determine whether a given date is still valid.
     *
     * @param int $date
     * @return boolean
     */
    protected function isValidEntry(int $date) {
        return $this->ttl == 0 || ($date + $this->ttl) > time() ;
    }

    /**
     * Reload the reputation database from the API.
     *
     * @return void
     */
    public function updateFromApi() {
        $ipAddresses = $this->retrieveFromApi() ;
        $new_cache = [] ;

        $this->iterateBackupFile(function($ip, $date) use (&$ipAddresses, &$new_cache) {
            // If an IP present in the backup file is also present
            // if the API response.
            if(in_array($ip, $ipAddresses)) {
                $new_cache[$ip] = time() ;

                // Remove the IP from the array.
                unset($ipAddresses[array_search($ip, $ipAddresses)]) ;

            // Else, if the entry is not too old yet.
            } elseif($this->isValidEntry($date)) {
                $new_cache[$ip] = $date ;
            }
        }) ;

        // At the end, add all left entries in the new cache array.
        foreach($ipAddresses as $ip) {
            $new_cache[$ip] = time() ;
        }

        // Write the new known IP addresses into the backup file.
        if(! empty($this->backup_path)) {
            $fd = fopen($this->backup_path, 'w') ;

            if($fd) {
                foreach($new_cache as $ip => $date) {
                    fwrite($fd, $ip . "," . $date . PHP_EOL) ;
                }

                fclose($fd) ;
            } else {
                throw new \RuntimeException("Failed to open WAF reputation file in write mode: {$this->backup_path}") ; 
            }

        }
        
        // We reload the cache with the new values.
        $this->reloadCacheFromBackupFile($new_cache) ;
    }

    /**
     * Retrieve suspicious IP addresses from the API.
     *
     * @return string[]
     */
    protected function retrieveFromApi() {
        $response = AbuseIPDB::blacklist(
			confidenceMinimum: 100,
			limit: 10000,
			ipVersion: config('waf.ipv6', default: false) ? null : 4, // Null means "all IPv4 and IPv6"
		) ;

		$data = $response->json()['data'] ;

        // Only keep the IP address field from the API.
        $addresses = [] ;
		foreach($data as $row) {
			$addresses[] = $row['ipAddress'] ;
		}

        return $addresses ;
    }

    /**
     * This method iterates over each line of the
     * backup file and call the $manage function.
     * 
     * If this method takes time, it might be related to
     * the number of times the $manage function is called
     * (changing the OS context). It might be good to put
     * the 'while' in the $manage function to call the $manage
     * function once and for all.
     *
     * @param callable $manage
     * @return boolean
     */
    protected function iterateBackupFile(callable $manage) {
        // If the path is null, then the user disabled the
        // backup feature. So, do nothing.
        if(empty($this->backup_path)) {
            return false ;
        }

        $handle = fopen($this->backup_path, 'r') ;

        if($handle) {
            while(($line = fgets($handle)) !== false) {
                [$ip, $date] = explode(",",  $line) ;
                
                call_user_func($manage, $ip, intval($date)) ;
            }

            fclose($handle) ;

            return true ;
        } else {
            throw new \RuntimeException("Failed to open WAF reputation file in read mode: {$this->backup_path}") ; 
        }

        return false ;
    }

    /**
     * Reload the cached data from the backup file.
     *
     * @param array|null $data
     * @return void
     */
    protected function reloadCacheFromBackupFile(array $data = null) {
        if(is_null($data)) {
            $new_cache = [] ;

            // Iterate over the backup file and add the valid
            // entries into the new cache array.
            $success = $this->iterateBackupFile(function($ip, $date) use (&$new_cache) {
                if($this->isValidEntry($date)) {
                    $new_cache[$ip] = $date ;
                }
            }) ;

            // If the file wasn't successfully iterated on,
            // set the cache to null, so that we clear the cache.
            if(! $success) {
                $new_cache = null ;
            }
        } else {
            $new_cache = $data ;
        }

        // First, clean the cache.
        Cache::forget(static::CACHE_KEY) ;

        // If a new cache was formed.
        if(! is_null($new_cache)) {
            Cache::forever(static::CACHE_KEY, $new_cache) ;
        }
    }

    /**
     * Determine whether the IP is known to be suspicious.
     *
     * @param string $ip
     * @return boolean
     */
    public function isSuspicious(string $ip, bool $reload = true) {
        // If the IP is private, we don't want to check whether it is
        // known for suspicious activities ; it's time consuming.
        if(! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE |  FILTER_FLAG_NO_RES_RANGE)) {
            return false ;
        }

        $cache = Cache::get(static::CACHE_KEY) ;

        // If there is something in the cache.
        if($cache) {
            // If the IP is not in the cache.
            if(! array_key_exists($ip, $cache ?? [])) {
                return false ;
            }

            // Check whether the cache entry is not too old.
            return $this->isValidEntry($cache[$ip]) ;
        }

        // If the reload was already done, then the IP is not known.
        if(! $reload) {
            return false ;
        }

        // If this line is executed, then the cache is null.
        // It is probably because the command cache:clear was called.
        $this->reloadCacheFromBackupFile() ;

        // Call the same method again, but avoid an endless loop by
        // telling the method to not load from backup file again.
        return $this->isSuspicious($ip, reload: false) ;
    }
}