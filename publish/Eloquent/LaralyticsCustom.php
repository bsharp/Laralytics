<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class LaralyticsCustom
 * @package App
 */
class LaralyticsCustom extends Model
{

    protected $table = 'laralytics_custom';

    protected $fillable = ['user_id', 'hash', 'url', 'version', 'event', 'data', 'created_at'];

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
