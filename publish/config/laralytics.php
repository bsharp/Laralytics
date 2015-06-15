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
    | Supported: "database", "eloquent"
    |
    */
    'driver' => 'eloquent',

    /*
    |--------------------------------------------------------------------------
    | Laralytics Models
    |--------------------------------------------------------------------------
    |
    | When using the "Eloquent" laralytics driver, we need to know which
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
