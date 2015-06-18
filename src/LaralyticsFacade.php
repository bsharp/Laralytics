<?php namespace Bsharp\Laralytics;

use Illuminate\Support\Facades\Facade;

/**
 * Class LaralyticsFacade
 * @package Bsharp\Laralytics
 */
class LaralyticsFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laralytics';
    }
}
