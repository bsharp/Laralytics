<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class LaralyticsModel
 * @package App
 */
class LaralyticsModel extends Model
{

    protected $table = 'laralytics';

    protected $fillable = ['user_id', 'type', 'meta', 'created_at'];

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
