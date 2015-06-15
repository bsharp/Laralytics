<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Laralytics Database Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the laralytics driver that will be utilized.
    | This driver manages the storage and retrieval of laralytics'
    | data in your database.
    |
    | Supported: "eloquent", "database", "file", "syslog", "syslogd"
    |
    */
    'driver' => 'eloquent',

    /*
    |--------------------------------------------------------------------------
    | Syslog configuration
    |--------------------------------------------------------------------------
    |
    | When using the "syslog" or "syslogd" driver you may want to specify
    | the facility that Laralytics can use, as well as a remote server
    | host and port to use with the "syslogd" driver.
    |
    | Supported values for facility: "LOG_ALERT", "LOG_CRIT", "LOG_ERR",
    | "LOG_WARNING", "LOG_NOTICE", "LOG_INFO", "LOG_DEBUG", "LOG_KERN",
    | "LOG_USER", "LOG_MAIL", "LOG_DAEMON", "LOG_AUTH", "LOG_SYSLOG",
    | "LOG_LPR", "LOG_NEWS", "LOG_UUCP", "LOG_CRON", "LOG_AUTHPRIV",
    | "LOG_LOCAL0", "LOG_LOCAL1", "LOG_LOCAL2", "LOG_LOCAL3", "LOG_LOCAL4",
    | "LOG_LOCAL5", "LOG_LOCAL6", "LOG_LOCAL7"
    */
    'syslog' => [
        'facility' => LOG_LOCAL0,
        'remote' => [
            'host' => 'localhost',
            'port' => 514
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Laralytics Models
    |--------------------------------------------------------------------------
    |
    | When using the "eloquent" laralytics driver, we need to know which
    | Eloquent models should be used to retrieve your laralytics data.
    | By default Laravel models are in the app directory.
    |
    */
    'models' => [
        'url' => App\LaralyticsUrl::class,
        'click' => App\LaralyticsClick::class,
        'custom' => App\LaralyticsCustom::class
    ],

    /*
    |--------------------------------------------------------------------------
    | User id's retrieving method
    |--------------------------------------------------------------------------
    |
    | This callback is called to get the current user_id if a user is
    | authenticated. It use the Laravel Auth facade by default but you
    | can change it to whatever method that return an user_id as a int.
    |
    */
    'user_id' => function () {
        return \Auth::user();
    }
];
