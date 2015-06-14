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
    | Laralytics Model
    |--------------------------------------------------------------------------
    |
    | When using the "Eloquent" laralytics driver, we need to know which
    | Eloquent model should be used to retrieve your laralytics data. By
    | default it should be a "Laralytics" model imported when publishing.
    |
    */
    'model' => App\LaralyticsModel::class,

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
