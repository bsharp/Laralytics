<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class LaralyticsClick
 * @package App
 */
class LaralyticsClick extends Model
{
    protected $table = 'laralytics_click';

    protected $fillable = ['user_id', 'hash', 'url', 'version', 'x', 'y', 'created_at'];

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
