<?php namespace Bsharp\Laralytics;

/**
 * Class Laralytics
 * @package Bsharp\Laralytics
 */
class Laralytics
{
    private $driver;
    private $model;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->driver = config('laralytics.driver');
        $this->model = config('laralytics.model');
    }

    /**
     * Log any type of action in laralytics database table.
     *
     * @param string $type
     * @param array $meta
     */
    public function log($type, $meta = [])
    {
        /** @var \Closure $user_id_closure */
        $user_id_closure = config('laralytics.user_id');
        $user_id = $user_id_closure();

        if ($this->driver == 'database') {
            $this->insertLogDatabase($user_id, $type, $meta);
        } else {
            $this->insertLogEloquent($user_id, $type, $meta);
        }
    }

    /**
     * Insert log in database using the Laravel query builder.
     *
     * @param int $user_id
     * @param string $type
     * @param array $meta
     */
    private function insertLogDatabase($user_id, $type, $meta = [])
    {

    }

    /**
     * Insert log in database using a Laravel Eloquent model.
     *
     * @param int $user_id
     * @param string $type
     * @param array $meta
     */
    private function insertLogEloquent($user_id, $type, $meta = [])
    {

    }
}
