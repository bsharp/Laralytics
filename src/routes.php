<?php

Route::post('laralytics', function (Bsharp\Laralytics\Laralytics $laralytics,
                                    Illuminate\Http\Request $request,
                                    Illuminate\Contracts\Cookie\Factory $cookie) {

    $trackerCookie = null;

    // Define payload
    $payload = $request->only('info', 'click', 'custom');

    // Check for tracking cookie if we have info in the payload
    if (!empty($payload['info'])) {
        $trackerCookie = $laralytics->checkCookie($request, $cookie);
    }

    // Insert payload
    $laralytics->payload($request, $payload, !is_null($trackerCookie));

    if (is_null($trackerCookie)) {
        return response()->json($_POST);
    } else {
        return response()->json($_POST)->withCookie($trackerCookie);
    }
});
