<?php namespace Bsharp\Laralytics;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Logger;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Cookie;

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
     * @var string $timezone
     */
    private $timezone;

    /**
     * @var string $pageUuid
     */
    private $pageUuid;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cookie = config('laralytics.cookie');
        $this->cookie['session'] = config('session.cookie');

        $this->driver = config('laralytics.driver');
        $this->models = config('laralytics.models');
        $this->syslog = config('laralytics.syslog');

        $this->timezone = config('app.timezone');
    }

    /**
     * Log a visited path in database.
     *
     * @param Request $request
     *
     * @param \Illuminate\Http\Response $response
     *
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    public function url(Request $request, $response)
    {
        $host = $request->getHttpHost();
        $method = $request->method();

        $path = $request->path();
        $path = starts_with($path, '/') ? $path : '/' . $path;

        $data = compact('host', 'path', 'method');

        $data['user_id'] = $this->getUserId();

        // If we don't have a user ID we replace it with a session token
        if ($data['user_id'] === null) {
            $data['session'] = $request->cookie($this->cookie['session']);
        }

        $data['hash'] = $this->hash($data['host'], $data['path']);
        $data['created_at'] = date('Y-m-d H:i:s');

        $data['uuid'] = Uuid::uuid1()->toString();

        $this->generic_insert('url', $data);

        // Set uuid to cookie
        return $this->addCookieToResponse($data['uuid'], $response);
    }

    /**
     * Parse and log a payload.
     *
     * @param Request $request
     * @param $payload
     * @param bool $insertUserInfo
     */
    public function payload(Request $request, array $payload, $insertUserInfo = false)
    {
        // Insert user info if needed
        if ($insertUserInfo) {
            $this->payloadInfo($request, $payload['info']);
        }

        $click = $payload['click'];
        $custom = $payload['custom'];

        // Format click & custom javascript time
        $this->addPayloadMetaData($click);
        $this->addPayloadMetaData($custom);

        // Insert payload
        $this->generic_insert('click', $click);
        $this->generic_insert('custom', $custom);
    }

    /**
     * Add generic data to a Laralytics event.
     *
     * @param $array
     */
    private function addPayloadMetaData(&$array)
    {
        foreach ($array as $key => $value) {
            // Add current uuid
            $array[$key]['uuid'] = $this->pageUuid;

            // Override javascript timestamp
            $jsTime = Carbon::createFromTimestamp($array[$key]['datetime']);
            $time = Carbon::now($this->timezone);

            $time->minute = $jsTime->minute;
            $time->second = $jsTime->second;

            $array[$key]['created_at'] = $time->toDateTimeString();
            unset($array[$key]['datetime']);
        }
    }

    /**
     * Check if the current user already has a Laralytics tracking cookie.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Cookie|null
     */
    public function checkGlobalCookie(Request $request)
    {
        $cookie = app()->make('Illuminate\Contracts\Cookie\Factory');

        // if the user don't have the cookie we create it
        if (!$request->cookie($this->cookie['global']['name'])) {
            return $cookie->make($this->cookie['global']['name'], md5(rand()), $this->cookie['global']['duration']);
        }

        return null;
    }

    /**
     * Check if the current user already has a Laralytics page cookie.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Cookie|null
     */
    public function checkPageCookie(Request $request)
    {
        // if the user don't have the cookie we stop the request
        if (!$request->cookie($this->cookie['page']['name'])) {
            return false;
        }

        $this->pageUuid = $request->cookie($this->cookie['page']['name']);

        return true;
    }

    /**
     * Add the Laralytics page tracker cookie to the response cookies.
     *
     * @param string $uuid
     * @param \Illuminate\Http\Response $response
     *
     * @return \Illuminate\Http\Response
     */
    protected function addCookieToResponse($uuid, $response)
    {
        $config = config('session');

        $response->headers->setCookie(
            new Cookie(
                $this->cookie['page']['name'], $uuid, time() + $this->cookie['page']['duration'],
                $config['path'], $config['domain'], false, false
            )
        );

        return $response;
    }

    /**
     * Format and insert a payload info.
     *
     * @param Request $request
     * @param array $userInfo
     */
    private function payloadInfo(Request $request, array $userInfo)
    {
        $data = [];

        foreach ($userInfo as $key => $value) {
            $data[snake_case($key)] = $value;
        }

        $data['user_id'] = $this->getUserId();

        // If we don't have a user ID we replace it with a session token
        if ($data['user_id'] === null) {
            $data['session'] = $request->cookie($this->cookie['session']);
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $data['session'] = $request->cookie($this->cookie['session']);

        $this->generic_insert('info', $data);
    }

    /**
     * Call a specific insert by laralytics driver.
     *
     * @param $type
     * @param $data
     */
    private function generic_insert($type, $data)
    {
        if (empty($data)) {
            return;
        }

        switch ($this->driver) {
            case 'database':
                $this->insertDatabase($type, $data);
                break;
            case 'eloquent':
                $this->insertEloquent($type, $data);
                break;
            case 'file':
                $this->insertFile($type, $data);
                break;
            case 'syslog':
                $this->insertSyslog($type, $data);
                break;
            case 'syslogd':
                $this->insertSyslogd($type, $data);
                break;
        }
    }

    /**
     * Insert log in database using the Laravel query builder.
     *
     * @param string $type
     * @param array $data
     */
    protected function insertDatabase($type, $data)
    {
        /** @var \Illuminate\Database\DatabaseManager $DB */
        $DB = app()->make('Illuminate\Database\DatabaseManager');

        $DB->table('laralytics_' . $type)->insert($data);
    }

    /**
     * Insert log in database using a Laravel Eloquent model.
     *
     * @param string $type
     * @param array $data
     */
    protected function insertEloquent($type, $data)
    {
        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = app()->make($this->models[$type]);

        if (isset($data[0]) and is_array($data[0])) {
            $model->insert($data);
        } else {
            foreach ($data as $key => $value) {
                $model->$key = $value;
            }
            $model->save();
        }
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

        if (isset($data[0]) and is_array($data[0])) {
            foreach ($data as $line) {
                $log->info('', $line);
            }
        } else {
            $log->info('', $data);
        }
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

        if (isset($data[0]) and is_array($data[0])) {
            foreach ($data as $line) {
                $log->info('', $line);
            }
        } else {
            $log->info('', $data);
        }
    }

    /**
     * Insert log in a remote syslog using Monolog.
     *
     * @param string $type
     * @param array $data
     */
    protected function insertSyslogd($type, $data)
    {
        $log = new Logger('laralytics');
        $syslog = new SyslogUdpHandler(
            $this->syslog['remote']['host'],
            $this->syslog['remote']['port'],
            $this->syslog['facility']
        );
        $syslog->setFormatter(new LineFormatter('laralytics-' . $type . " %context%\n"));
        $log->pushHandler($syslog);

        if (isset($data[0]) and is_array($data[0])) {
            foreach ($data as $line) {
                $log->info('', $line);
            }
        } else {
            $log->info('', $data);
        }
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
        return $user_id  == 0 ? null : $user_id;
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
