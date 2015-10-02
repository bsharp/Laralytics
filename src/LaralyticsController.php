<?php

namespace Bsharp\Laralytics;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Class LaralyticsController
 * @package Bsharp\Laralytics
 */
class LaralyticsController extends Controller
{
    /**
     * @param Laralytics $laralytics
     * @param Request    $request
     *
     * @return $this|\Illuminate\Http\JsonResponse
     */
    public function payload(Laralytics $laralytics, Request $request) {

        $trackerCookie = null;

        // Define payload
        $payload = $request->only('info', 'click', 'custom');

        // Check for tracking cookie if we have info in the payload
        if (!empty($payload['info'])) {
            $trackerCookie = $laralytics->checkGlobalCookie($request);
        }

        // Check for page cookie if we have info in the payload
        if (!empty($payload['info'])) {

            $hasPageCookie = $laralytics->checkPageCookie($request);

            // Page cookie with request uuid
            if ($hasPageCookie) {
                // Insert payload
                $laralytics->payload($request, $payload, !is_null($trackerCookie));
            }
        } else {
            $laralytics->checkPageCookie($request);
            $laralytics->payload($request, $payload, !is_null($trackerCookie));
        }

        if (!is_null($trackerCookie)) {
            return response()->json()->withCookie($trackerCookie);
        }

        return response()->json();
    }
}
