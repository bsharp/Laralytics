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
use Session;


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

        $this->driver = config('laralytics.driver');
        $this->models = config('laralytics.models');
        $this->syslog = config('laralytics.syslog');

        $this->timezone = config('app.timezone');

        $this->session = config('session');
    }

    public function common(Request $request, &$response) {
        $host = $request->getHttpHost();
        $method = $request->method();

        $path = $request->path();
        $path = starts_with($path, '/') ? $path : '/' . $path;

        $data = compact('host', 'path', 'method');

        $data['created_at'] = date('Y-m-d H:i:s');

        $data['user_id'] = $this->getUserId();
        $data['global_tracker'] = $this->getGlobalCookieValue($request, $response);

        $data['session'] = Session::getId();

        return $data;
    }

    public function url(Request $request, &$response)
    {
        $data = $this->common($request, $response);
        $data['page_tracker'] = $this->setPageCookieValue($request, $response);

        $this->generic_insert('url', $data);

        return $response;
    }

    public function payload(Request $request, &$response, array $payload)
    {
        $insertUserInfo = !Session::get('laralytics_known_visitor');
        $data = $this->common($request, $response);

        // Insert user info if needed
        if ($insertUserInfo) {
            $this->payloadInfo($request, $response, $payload['info'], $data);
        }

        $all_data = $payload['click'] + $payload['custom'];
        $all_data = $this->addPayloadMetaData($all_data, $data);

        // Insert payload
        $this->generic_insert('click', $all_data);
    }

    /**
     * Add generic data to a Laralytics event.
     *
     * @param $array
     */
    private function addPayloadMetaData($array, $data)
    {
        $out = [];
        foreach ($array as $value) {
            // Add current page_tracker uuid
            $value['page_tracker'] = $this->pageUuid;

            // Override javascript timestamp
            $jsTime = Carbon::createFromTimestamp($value['datetime']);
            $time = Carbon::now($this->timezone);

            $time->minute = $jsTime->minute;
            $time->second = $jsTime->second;

            $value['created_at'] = $time->toDateTimeString();
            unset($value['datetime']);

            $out[] = array_merge($data, $value);
        }

        return $out;
    }

    private function setPageCookieValue(Request $request, &$response) {
        $value = Uuid::uuid4()->toString();
        Session::put($this->cookie['page']['name'], $value);

        $cookie = new Cookie($this->cookie['page']['name'], $value, 0, $this->session['domain'], $this->session['domain'], ((env('APP_URL_PREFIX', 'http://') == 'https://')?true:false), false);
        $response->headers->setCookie($cookie);

        return $value;
    }

    public function getGlobalCookieValue(Request $request, &$response) {
        if (!($value = $request->cookie($this->cookie['global']['name']))) {
            $value = Uuid::uuid4()->toString();

            $cookie = new Cookie($this->cookie['global']['name'], $value, time() + $this->cookie['global']['duration'], '/', $this->session['domain'], ((env('APP_URL_PREFIX', 'http://') == 'https://')?true:false), false);
            $response->headers->setCookie($cookie);

            Session::set('laralytics_known_visitor', false);
        } else {
            Session::set('laralytics_known_visitor', true);
        }

        return $value;
    }

    public function checkPageCookie(Request $request)
    {
        // page cookie is defined for each page view when page is loaded, so should be here when we check
        // if the user doesn't have the cookie with the proper value we stop the request
        if (
                    !Session::has($this->cookie['page']['name'])
                ||  !($value = $request->cookie($this->cookie['page']['name']))
                ||  ($value != Session::get($this->cookie['page']['name']))
            ) {
            return false;
        }

        $this->pageUuid = $value;

        return true;
    }

    private function payloadInfo(Request $request, &$response, array $userInfo, $data)
    {
        foreach ($userInfo as $key => $value) {
            $data[snake_case($key)] = $value;
        }

        $this->generic_insert('info', $data);
    }

    // handle storage
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
        $user_id = \Auth::user() === null ? null : \Auth::user()->id;

        // Sanitize value
        return $user_id  == 0 ? null : $user_id;
    }
}
