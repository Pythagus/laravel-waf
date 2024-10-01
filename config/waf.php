<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WAF updates
    |--------------------------------------------------------------------------
    |
    | This option controls the way the WAF will update its configuration 
    | files, like AbuseIPDB if you enabled it, MaxMind, the protections, etc.
    | If you don't want to automate the update, you will have to do it manually.
    |
    */
    'updates' => [
        // Determine whether the command will be scheduled 3 times
        // a day to automate the update. If this option is set to
        // false, the 'modules' array option won't be checked.
        'automatic' => env('WAF_UPDATES', default: true),

        // This allows you to override the default command to comment
        // or add new features.
        'command' => \Pythagus\LaravelWaf\Commands\WafUpdateCommand::class,

        // Cron expression scheduling the command.
        // https://crontab.guru/#5_3,13,23_*_*_*
        //'cron' => "5 3,13,23 * * *", // At minute 5 past hour 3, 13, and 23
        'cron' => "* * * * *", // At minute 5 past hour 3, 13, and 23
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP rules protection.
    |--------------------------------------------------------------------------
    |
    | This option controls the way the rules protections (regex) are blocking
    | the suspicious traffic.
    |
    */
    'http-rules' => [
        // A backup file is stored in the filesystem so that we can
        // retrieve the list if the cache is cleared.
        // If set to null, this backup system will be disabled.
        'storage' => storage_path('framework/cache/waf-rules.txt'),

        // Determine whether the matching HTTP traffic should be blocked
        // by the WAF.
        'blocking' => env('WAF_RULES_BLOCKING', default: false),

        // Determine whether the latching HTTP traffic should
        // log into the database, so that the admin has an overview
        // of the malicious traffic.
        'logging' => env('WAF_RULES_LOGGING', default: true),

        // Determine whether the rules should be automatically updated from
        // the feeds below. 
        'auto-update' => env('WAF_RULES_AUTO_UPDATE', default: false),

        // Rules feeds. 
        'feeds' => [
            "https://raw.githubusercontent.com/Pythagus/laravel-waf/refs/heads/main/rules/rules.csv"
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IP reputation databases.
    |--------------------------------------------------------------------------
    |
    | This option controls the interactions made between your
    | application and the configured IP reputation databases.
    | With this array, you can enable the blocking of all IP 
    | known for malicious activities.
    |
    */
    'ip-reputation' => [
        // This will activate the automatic blacklist if the IP
        // is known by one of the defined feeds.
        'enabled' => env('WAF_REPUTATION_ENABLED', default: true),

        // A backup file is stored in the filesystem so that we can
        // retrieve the list if the cache is cleared.
        // If set to null, this backup system will be disabled.
        'storage' => storage_path('framework/cache/waf-ip-reputation.txt'),

        // Determine whether the reputation database will be automatically updated.
        'auto-update' => env('WAF_REPUTATION_AUTO_UPDATE', default: true),

        // List of reputation feeds.
        // Allowed values:
        // - A predefined driver (see list above)
        // - an URL to a file containing IPv4 addresses
        //
        // Predefined drivers:
        // - abuseipdb
        'feeds' => [
            //'abuseipdb',
            'https://raw.githubusercontent.com/borestad/blocklist-abuseipdb/refs/heads/main/abuseipdb-s100-7d.ipv4',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IP location.
    |--------------------------------------------------------------------------
    |
    | This option controls how your application will determine
    | the geolocation associated to an IP address.
    | This package uses the InteractionDesignFoundation/laravel-geoip package
    | for resolving geolocation. Please refer to the documentation to configure
    | a geolocation provider.
    |
    | @see https://github.com/InteractionDesignFoundation/laravel-geoip
    |
    */
    'geolocation' => [
        // Determine whether the reputation database will be automatically updated.
        'enabled' => env('WAF_GEOLOCATION_ENABLED', default: true),

        // Determine whether the geolocation database will be automatically updated.
        'auto-update' => env('WAF_GEOLOCATION_AUTO_UPDATE', default: true),

        // This value allows to change the path of the database.
        // @see WafServiceProvider (method bootConfigurations).
        'override-maxmind-path' => env('WAF_GEOLOCATION_OVERRIDE_MAXMIND_PATH', default: true),

        // This array specifies the patterns that can be blocked
        // regarding the location of the IP addresses.
        'rules' => [
            // In this array, you can set the countries you want to block.
            // Reference: https://www.iban.com/country-codes
            //
            // You can set:
            // - country codes (ISO codes) (column "Alpha-2 code")
            // - country names (column "Country")
            'country' => [
                // "China", <== Country name
                // "NO", <== Country code
            ],
        ]
    ],
] ;
