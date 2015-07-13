<?php

Route::post('laralytics', function (Bsharp\Laralytics\Laralytics $laralytics, Illuminate\Http\Request $request) {

    $trackerCookie = null;

    // Define payload
    $payload = $request->only('info', 'click', 'custom');

    // Check for tracking cookie if we have info in the payload
    if (!empty($payload['info'])) {
        $trackerCookie = $laralytics->checkCookie($request);
    }

    // Insert payload
    $laralytics->payload($request, $payload, !is_null($trackerCookie));

    if (!is_null($trackerCookie)) {
        return response()->json()->withCookie($trackerCookie);
    }

});
