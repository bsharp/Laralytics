<?php namespace Bsharp\Laralytics;

use DB;

/**
 * Class Laralytics
 * @package Bsharp\Laralytics
 */
class Laralytics
{
    private $driver;
    private $models;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->driver = config('laralytics.driver');
        $this->models = config('laralytics.models');
    }

    /**
     * Log a visited url in database.
     *
     * @param string $url
     * @param string $method
     */
    public function url($url, $method)
    {
        $url = starts_with($url, '/') ? $url : '/' . $url;

        $data = compact('url', 'method');

        if ($this->driverIsDatabase()) {
            $this->insertDatabase('laralytics_url', $data);
        } else {
            $this->insertEloquent($this->models['url'], $data);
        }
    }

    /**
     * Insert log in database using the Laravel query builder.
     *
     * @param string $table
     * @param array $data
     */
    private function insertDatabase($table, $data)
    {
        $data['user_id'] = $this->getUserId();
        $data['hash'] = $this->hash($data['url']);

        DB::table($table)->insert($data);
    }

    /**
     * Insert log in database using a Laravel Eloquent model.
     *
     * @param string $model
     * @param array $data
     */
    private function insertEloquent($model, $data)
    {
        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = app()->make($model);

        $data['user_id'] = $this->getUserId();
        $data['hash'] = $this->hash($data['url']);

        foreach ($data as $key => $value) {
            $model->$key = $value;
        }

        $model->save();
    }

    /**
     * Return the current user id or 0.
     *
     * @return int
     */
    private function getUserId()
    {
        /** @var \Closure $user_id_closure */
        $user_id_closure = config('laralytics.user_id');
        $user_id = $user_id_closure();

        // Sanitize value
        return $user_id  === null ? 0 : $user_id;
    }

    /**
     * Return true if the current Laralytics driver is set to "database".
     *
     * @return bool
     */
    private function driverIsDatabase()
    {
        return $this->driver === 'database';
    }

    /**
     * Hash a url string using the md4 (fastest) hashing algorithm.
     *
     * @param $string
     *
     * @return string
     */
    private function hash($string)
    {
        return hash('md4', $string);
    }
}
