<?php

Route::post('laralytics', function (\Bsharp\Laralytics\Laralytics $laralytics, Illuminate\Http\Request $request) {

    $payload = $request->only('infos', 'click', 'custom');

    $laralytics->payload($payload);

    return response()->json($_POST);
});
