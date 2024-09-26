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

        // Determine which modules will be updated.
        'modules' => [
            'waf-rules' => env('WAF_UPDATES_RULES', default: false),
            'ip-reputation' => env('WAF_UPDATES_IPREPUTATION', default: true),
            'geolocation' => env('WAF_UPDATES_GEOLOCATION', default: true),
        ]
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
    'reputation' => [
        // This will activate the automatic blacklist if the IP
        // is known by one of the defined feeds.
        'enabled' => env('WAF_REPUTATION', default: true),

        // A backup file is stored in the filesystem so that we can
        // retrieve the list if the cache is cleared.
        // If set to null, this backup system will be disabled.
        'storage' => storage_path('framework/cache/ip-reputation.txt'),

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
];
