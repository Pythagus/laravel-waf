<?php

namespace Pythagus\LaravelWaf\Security\Reputations;

use AbuseIPDB\Facades\AbuseIPDB;

/**
 * Retrieve a list of IP to block from AbuseIPDB API.
 * 
 * @author Damien MOLINA
 */
class FeedAbuseIPDB extends Feed {

    /**
     * Determine whether the AbuseIPDB API
     * is well configured.
     * 
     * @return bool
     */
    protected function isConfigured() {
        return config('abuseipdb.api_key') ;
    }

    /**
     * Update the feeder.
     * 
     * @return static
     */
    public function update() {
        if($this->isConfigured()) {
            $response = AbuseIPDB::blacklist(
                confidenceMinimum: 100,
                limit: 100000,
                ipVersion: 4,
            ) ;

            $data = $response->json('data', []) ;

            // Only keep the IP address field from the API.
            foreach($data as $row) {
                $this->add($row['ipAddress']) ;
            }
        }

        return $this ;
    }
}
