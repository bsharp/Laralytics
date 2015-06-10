<?php namespace Nicolasbeauvais\Laralytics;

use Illuminate\Translation\TranslationServiceProvider;

/**
 * Class LaralyticsServiceProvider
 * @package Nicolasbeauvais\Laralytics
 */
class LaralyticsServiceProvider extends TranslationServiceProvider
{
    protected $defer = false;

    public function boot()
    {

    }
}
