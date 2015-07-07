<?php

Route::post('laralytics', function (\Bsharp\Laralytics\Laralytics $laralytics) {

    dd(Input::all());
    $laralytics->payload();

    return response()->json($_POST);
});
