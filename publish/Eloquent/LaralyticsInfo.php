<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class LaralyticsInfo
 * @package App
 */
class LaralyticsInfo extends Model
{
    protected $table = 'laralytics_info';

    protected $fillable = ['user_id', 'version', 'session', 'browser', 'browser_width', 'browser_height',
        'device_height', 'device_width', 'created_at'];

    /**
     * Disable updated_at column as we doesn't use it to log our analytics.
     *
     * @param mixed $value
     *
     * @return null
     */
    public function setUpdatedAt($value)
    {
    }
}
