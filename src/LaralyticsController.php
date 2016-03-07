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

        $response = response();

        $payload = $request->only('info', 'click', 'custom');
        $pageCookie = $laralytics->checkPageCookie($request);

        if ($pageCookie) {
            $laralytics->payload($request, $response, $payload, $pageCookie);
        }

        return $response->json();
    }
}
