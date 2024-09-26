<?php

namespace Pythagus\LaravelWaf\Security\Reputations;

use Illuminate\Support\Facades\Http;

/**
 * Retrieve a list of IP to block from a custom URL.
 * 
 * @author Damien MOLINA
 */
class FeedCustomUrl extends Feed {

    /**
     * The URL to fetch data from.
     * 
     * @property string
     */
    protected string $url ;

    /**
     * Build the custom URL feeder instance.
     * 
     * @param string $url
     */
    public function __construct(string $url) {
        $this->url = $url ;
    }

    /**
     * Update the feeder.
     * 
     * @return static
     */
    public function update() {
        $response = Http::get($this->url) ;

        // If the response is not successful, then stop
        // this function here and do nothing more.
        // TODO warn the admin about the issue
        if(! $response->successful()) {
            return $this ;
        }

        // Convert each line of the response to a row in an array.
        $content = explode(PHP_EOL, $response->body()) ;

        foreach($content as $line) {
            $cleaned = trim($line) ;

            // If the line is a valid IP address.
            if(filter_var($cleaned, FILTER_VALIDATE_IP)) {
                $this->add($cleaned) ;
            }
        }

        return $this ;
    }
}
