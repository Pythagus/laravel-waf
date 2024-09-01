<?php

return [

    // Determine whether IPv6 are likely to access your application. In some
    // architectures, IPv6 are not allowed and you'll only receive IPv4 connections.
    // In that case, it reduces the overload of the WAF by only focussing on IPv4.
    //
    // Disclaimer: this parameter won't block IPv6 traffic!
    'ipv6' => env('WAF_IPV6', default: false),

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

        // Determine which modules will be updated.
        'modules' => [
            'waf-rules' => env('WAF_UPDATES_RULES', default: false),
            'abuseipdb' => env('WAF_UPDATES_ABUSEIPDB', default: true),
            'geolocation' => env('WAF_UPDATES_GEOLOCATION', default: true),
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | AbuseIPDB API
    |--------------------------------------------------------------------------
    |
    | This option controls the interactions made between your
    | application and AbuseIPDB. With this array, you will be
    | able to enable automatic reporting, block all IP with a
    | score of 100%, etc.
    |
    */
    'abuseipdb' => [
        'reputation' => [
            // This will activate the automatic blacklist if the IP
            // is known by AbuseIPDB with a score of 100% of confidence.
            'enabled' => env('WAF_ABUSEIPDB_REPUTATION', default: true),

            // Path to the backup file. 
            // Set this value to null to disable the backup feature.
            //
            // First column: IP addresses
            // Second column: Insertion date
            'backup_file' => storage_path('app/waf_ip_reputation.csv'),

            // Time To Live.
            // Number of seconds an entry in the reputation database is valid.
            // 259200 = 3 * 86400 = number of seconds in 3 days
            //
            // If set to 0, the entries will never be removed. You will have to clear the
            // file by yourself.
            'ttl' => 259200,
        ],

        'report' => [
            // Determine whether the IP will be automatically reported on
            // AbuseIPDB after a specific number of exploits.
            'enabled' => env('WAF_ABUSEIPDB_REPORT', default: false),

            // Number of exploits for an IP address before being reported.
            'min_exploits' => 10,

            // Determine the time between two reports on AbuseIPDB. This
            // time is in seconds, and shouldn't be too low.
            'time_between_reports' => 600,
        ],
    ],

];
