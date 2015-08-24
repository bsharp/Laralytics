<?php

namespace App\Http\Middleware;

use Closure;
use Laralytics;
use Illuminate\Contracts\Auth\Guard;

/**
 * Class LaralyticsMiddleware
 * @package App\Http\Middleware
 */
class LaralyticsMiddleware
{
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new filter instance.
     *
     * @param Guard $auth
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return Laralytics::url($request, $next($request));
    }
}
