<?php namespace Bsharp\Laralytics;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Logger;

/**
 * Class Laralytics
 * @package Bsharp\Laralytics
 */
class Laralytics
{
    /**
     * @var string $driver
     */
    private $driver;

    /**
     * @var array $models
     */
    private $models;

    /**
     * @var array $syslog
     */
    private $syslog;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->driver = config('laralytics.driver');
        $this->models = config('laralytics.models');
        $this->syslog = config('laralytics.syslog');
    }

    /**
     * Log a visited path in database.
     *
     * @param string $host
     * @param string $path
     * @param string $method
     */
    public function url($host, $path, $method)
    {
        $path = starts_with($path, '/') ? $path : '/' . $path;

        $data = compact('host', 'path', 'method');


        switch ($this->driver) {
            case 'database':
                $this->insertDatabase('laralytics_url', $data);
                break;
            case 'eloquent':
                $this->insertEloquent($this->models['url'], $data);
                break;
            case 'file':
                $this->insertFile('url', $data);
                break;
            case 'syslog':
                $this->insertSyslog('url', $data);
                break;
            case 'syslogd':
                $this->insertSyslogd($data);
                break;
        }
    }

    /**
     * Log a page payload received from javascript.
     */
    public function payload()
    {
        // @TODO
    }

    /**
     * Insert log in database using the Laravel query builder.
     *
     * @param string $table
     * @param array $data
     */
    protected function insertDatabase($table, $data)
    {
        $data['user_id'] = $this->getUserId();
        $data['hash'] = $this->hash($data['host'], $data['path']);

        /** @var \Illuminate\Database\DatabaseManager $DB */
        $DB = app()->make('Illuminate\Database\DatabaseManager');

        $DB->table($table)->insert($data);
    }

    /**
     * Insert log in database using a Laravel Eloquent model.
     *
     * @param string $model
     * @param array $data
     */
    protected function insertEloquent($model, $data)
    {
        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = app()->make($model);

        $data['user_id'] = $this->getUserId();
        $data['hash'] = $this->hash($data['host'], $data['path']);

        foreach ($data as $key => $value) {
            $model->$key = $value;
        }

        $model->save();
    }

    /**
     * Insert log in a file using Monolog.
     *
     * @param string $type
     * @param array $data
     */
    protected function insertFile($type, $data)
    {
        $log = new Logger('laralytics');
        $stream = new StreamHandler(storage_path('/app/laralytics-' . $type . '.log'));
        $stream->setFormatter(new LineFormatter("%context%\n"));
        $log->pushHandler($stream);

        $data['user_id'] = $this->getUserId();
        $data['hash'] = $this->hash($data['host'], $data['path']);
        $data['created_at'] = date('Y-m-d H:i:s');

        $log->info('', $data);
    }

    /**
     * Insert log in syslog using Monolog.
     *
     * @param string $type
     * @param array $data
     */
    protected function insertSyslog($type, $data)
    {
        $log = new Logger('laralytics');
        $syslog = new SyslogHandler('laralytics-' . $type, $this->syslog['facility']);
        $syslog->setFormatter(new LineFormatter("%context%\n"));
        $log->pushHandler($syslog);

        $data['user_id'] = $this->getUserId();
        $data['hash'] = $this->hash($data['host'], $data['path']);
        $data['created_at'] = date('Y-m-d H:i:s');

        $log->info('', $data);
    }

    /**
     * Insert log in a remote syslog using Monolog.
     *
     * @param array $data
     */
    protected function insertSyslogd($data)
    {
        $log = new Logger('laralytics');
        $syslog = new SyslogUdpHandler(
            $this->syslog['remote']['host'],
            $this->syslog['remote']['port'],
            $this->syslog['facility']
        );
        $syslog->setFormatter(new LineFormatter("%context%\n"));
        $log->pushHandler($syslog);

        $data['user_id'] = $this->getUserId();
        $data['hash'] = $this->hash($data['host'], $data['path']);
        $data['created_at'] = date('Y-m-d H:i:s');

        $log->info('', $data);
    }

    /**
     * Return the current user id or 0.
     *
     * @return int
     */
    protected function getUserId()
    {
        /** @var \Closure $user_id_closure */
        $user_id_closure = config('laralytics.user_id');
        $user_id = $user_id_closure();

        // Sanitize value
        return $user_id  === null ? 0 : $user_id;
    }

    /**
     * Hash a path string using the md4 (fastest) hashing algorithm.
     *
     * @param $host
     * @param $path
     *
     * @return string
     */
    protected function hash($host, $path)
    {
        return hash('md4', $host . $path);
    }
}
