<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class LaralyticsUrl
 * @package App
 */
class LaralyticsUrl extends Model
{
    protected $table = 'laralytics_url';

    protected $fillable = ['user_id', 'session', 'hash', 'host', 'path', 'method', 'created_at'];

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
