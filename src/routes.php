<?php

Route::post('laralytics', function (Bsharp\Laralytics\Laralytics $laralytics,
                                    Illuminate\Http\Request $request,
                                    Illuminate\Contracts\Cookie\Factory $cookie) {

    $payload = $request->only('info', 'click', 'custom');

    $laralytics->payload($request, $cookie, $payload);

    return response()->json($_POST);
});
