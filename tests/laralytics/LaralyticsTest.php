<?php

use App\LaralyticsInfo;
use App\LaralyticsUrl;
use Bsharp\Laralytics\Laralytics;

/**
 * Class LaralyticsTest
 */
class LaralyticsTest extends \Orchestra\Testbench\TestCase
{
    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();

        // Require Eloquent models for autoload
        require_once __DIR__ . '/../../publish/Eloquent/LaralyticsClick.php';
        require_once __DIR__ . '/../../publish/Eloquent/LaralyticsCustom.php';
        require_once __DIR__ . '/../../publish/Eloquent/LaralyticsUrl.php';

        // Make migrations
        $this->artisan('migrate', [
            '--database' => 'test',
            '--realpath' => realpath(__DIR__ . '/../../publish/database/migrations'),
        ]);
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'test');
        $app['config']->set('database.migrations', 'migrations');
        $app['config']->set('database.connections.test', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('laralytics', require __DIR__ . '/../../publish/config/laralytics.php');
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            'Bsharp\Laralytics\LaralyticsServiceProvider',
        ];
    }

    /**
     * verify if the class is instantiated with the proper configuration parameters.
     */
    public function testInitialisation()
    {
        $instance = new Laralytics();

        $this->assertAttributeEquals('eloquent', 'driver', $instance);
        $this->assertAttributeNotEmpty('cookie', $instance);
        $this->assertAttributeNotEmpty('syslog', $instance);
        $this->assertAttributeNotEmpty('models', $instance);
    }

    /**
     * Test the url method with the eloquent driver.
     */
    public function testUrlInsertEloquent()
    {
        // set eloquent driver
        app('config')->set('laralytics.driver', 'eloquent');

        $instance = new Laralytics();
        $uri = str_random(10);

        /** @var \Illuminate\Http\Request $request */
        $request = app()->make('Illuminate\Http\Request');
        $request = $request::create($uri, 'GET');

        $instance->url($request);

        $row = LaralyticsUrl::orderBy('id', 'DESC')->first();

        $this->assertEquals('localhost', $row->host);
        $this->assertEquals('/' . $uri, $row->path);
    }

    /**
     * Test the url method with the database driver.
     */
    public function testUrlInsertDatabase()
    {
        // set database driver
        app('config')->set('laralytics.driver', 'database');

        $instance = new Laralytics();
        $uri = str_random(10);

        /** @var \Illuminate\Http\Request $request */
        $request = app()->make('Illuminate\Http\Request');
        $request = $request::create($uri, 'GET');

        $instance->url($request);

        $row = DB::table('laralytics_url')->orderBy('id', 'DESC')->first();

        $this->assertEquals('localhost', $row->host);
        $this->assertEquals('/' . $uri, $row->path);
    }

    /**
     * Test the url method with the file driver.
     */
    public function testUrlInsertFile()
    {
        // set file driver
        app('config')->set('laralytics.driver', 'file');

        // Storage file
        $storageFile = storage_path('app/laralytics-url.log');

        // Delete already existing log file
        if (file_exists($storageFile)) {
            unlink($storageFile);
        }

        $instance = new Laralytics();
        $uri = [];
        $uri[0] = str_random(10);
        $uri[1] = str_random(10);

        /**
         * @var \Illuminate\Http\Request $request
         * @var \Illuminate\Http\Request $request_one
         * @var \Illuminate\Http\Request $request_two
         */
        $request = app()->make('Illuminate\Http\Request');
        $request_one = $request::create($uri[0], 'GET');
        $request_two = $request::create($uri[1], 'GET');

        $instance->url($request_one);
        $instance->url($request_two);

        $file = file($storageFile);

        $this->assertEquals(2, count($file));

        foreach ($file as $key => $line) {
            $lineArray = json_decode($line, true);

            $this->assertEquals('localhost', $lineArray['host']);
            $this->assertEquals('/' . $uri[$key], $lineArray['path']);
        }
    }

    /**
     * Test the url method with the syslog driver.
     */
    public function testUrlInsertSyslog()
    {
        // set syslog driver
        app('config')->set('laralytics.driver', 'syslog');
        app('config')->set('laralytics.syslog.facility', LOG_LOCAL0);

        $instance = new Laralytics();
        $uri = [];
        $uri[0] = str_random(10);
        $uri[1] = str_random(10);

        /**
         * @var \Illuminate\Http\Request $request
         * @var \Illuminate\Http\Request $request_one
         * @var \Illuminate\Http\Request $request_two
         */
        $request = app()->make('Illuminate\Http\Request');
        $request_one = $request::create($uri[0], 'GET');
        $request_two = $request::create($uri[1], 'GET');

        $instance->url($request_one);
        $instance->url($request_two);

        $file = file('/var/log/syslog');
        $file = array_reverse($file);
        $lines = [];

        foreach ($file as $line) {

            if (count($lines) === 2) {
                break;
            }

            if (strpos($line, 'laralytics-url') > -1) {
                $lines[] = json_decode(substr($line, strpos($line, '{')), true);
            }
        }

        $this->assertEquals('localhost', $lines[0]['host']);
        $this->assertEquals('/' . $uri[1], $lines[0]['path']);

        $this->assertEquals('localhost', $lines[1]['host']);
        $this->assertEquals('/' . $uri[0], $lines[1]['path']);
    }

    /**
     * Test the url method with the syslogd driver.
     */
    public function testUrlInsertSyslogd()
    {
        // set syslogd driver
        app('config')->set('laralytics.driver', 'syslogd');
        app('config')->set('laralytics.syslog.facility', LOG_LOCAL0);
        app('config')->set('laralytics.syslog.remote', [
            'host' => '127.0.0.1',
            'port' => 514
        ]);

        $instance = new Laralytics();
        $uri = [];
        $uri[0] = str_random(10);
        $uri[1] = str_random(10);

        /**
         * @var \Illuminate\Http\Request $request
         * @var \Illuminate\Http\Request $request_one
         * @var \Illuminate\Http\Request $request_two
         */
        $request = app()->make('Illuminate\Http\Request');
        $request_one = $request::create($uri[0], 'GET');
        $request_two = $request::create($uri[1], 'GET');

        $instance->url($request_one);
        $instance->url($request_two);

        $file = file('/var/log/syslog');
        $file = array_reverse($file);
        $lines = [];

        foreach ($file as $line) {

            if (count($lines) === 2) {
                break;
            }

            if (strpos($line, 'laralytics-url') > -1) {
                $lines[] = json_decode(substr($line, strpos($line, '{')), true);
            }
        }

        $this->assertEquals('localhost', $lines[0]['host']);
        $this->assertEquals('/' . $uri[1], $lines[0]['path']);

        $this->assertEquals('localhost', $lines[1]['host']);
        $this->assertEquals('/' . $uri[0], $lines[1]['path']);
    }

    /**
     * Generate a random payload to test insertion.
     *
     * @return array
     */
    private function getSamplePayload()
    {
        $clicks = [];
        $customs = [];

        $nb_clicks = rand(2, 10);
        $nb_customs = rand(2, 10);

        for ($i = 0; $i < $nb_clicks; $i++) {
            $clicks[] = [
                'x' => rand(800, 1920),
                'y' => rand(600, 1080),
                'datetime' => time(),
                'element' => str_random(10)
            ];
        }

        for ($i = 0; $i < $nb_customs; $i++) {
            $customs[] = [
                'event' => str_random(5),
                'x' => rand(800, 1920),
                'y' => rand(600, 1080),
                'datetime' => time(),
                'element' => str_random(10)
            ];
        }

        return [
            'info' => [
                'version' => str_random(10),
                'browser' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML,
                like Gecko) Chrome/43.0.2357.130 Safari/537.36',
                'browserWidth' => rand(800, 1920),
                'browserHeight' => rand(600, 1080),
                'deviceWidth' => rand(800, 1920),
                'deviceHeight' => rand(600, 1080),
            ],
            'click' => $clicks,
            'custom' => $customs
        ];
    }

    /**
     * Test payload method with the eloquent driver.
     */
    public function testPayloadInsertEloquent()
    {
        // set eloquent driver
        app('config')->set('laralytics.driver', 'eloquent');

        $instance = new Laralytics();
        $payload = $this->getSamplePayload();

        /** @var \Illuminate\Http\Request $request */
        $request = app()->make('Illuminate\Http\Request');
        $request = $request::create(str_random(5), 'GET');

        $instance->payload($request, $payload, true);

        $rowInfo = LaralyticsInfo::orderBy('id', 'DESC')->first();

        $this->assertEquals($payload['info']['version'], $rowInfo->version);
        $this->assertEquals($payload['info']['browserWidth'], $rowInfo->browser_width);
        $this->assertEquals($payload['info']['browserHeight'], $rowInfo->browser_height);
        $this->assertEquals($payload['info']['deviceWidth'], $rowInfo->device_width);
        $this->assertEquals($payload['info']['deviceHeight'], $rowInfo->device_height);

        /** @var \Illuminate\Support\Collection $rowClick */
        $rowClick = \App\LaralyticsClick::orderBy('id', 'DESC')->take(count($payload['click']))->get();
        $rowClick = $rowClick->reverse();

        foreach ($rowClick as $key => $click) {
            $this->assertEquals($payload['click'][$key]['x'], $click->x);
            $this->assertEquals($payload['click'][$key]['y'], $click->y);
            $this->assertEquals($payload['click'][$key]['element'], $click->element);
        }

        /** @var \Illuminate\Support\Collection $rowCustom */
        $rowCustom = \App\LaralyticsCustom::orderBy('id', 'DESC')->take(count($payload['custom']))->get();
        $rowCustom = $rowCustom->reverse();

        foreach ($rowCustom as $key => $custom) {
            $this->assertEquals($payload['custom'][$key]['x'], $custom->x);
            $this->assertEquals($payload['custom'][$key]['y'], $custom->y);
            $this->assertEquals($payload['custom'][$key]['element'], $custom->element);
        }
    }

    /**
     * Test payload method with the database driver.
     */
    public function testPayloadInsertDatabase()
    {
        // set eloquent driver
        app('config')->set('laralytics.driver', 'database');

        $instance = new Laralytics();
        $payload = $this->getSamplePayload();

        /** @var \Illuminate\Http\Request $request */
        $request = app()->make('Illuminate\Http\Request');
        $request = $request::create(str_random(5), 'GET');

        $instance->payload($request, $payload, true);

        $rowInfo = DB::table('laralytics_info')->orderBy('id', 'DESC')->first();

        $this->assertEquals($payload['info']['version'], $rowInfo->version);
        $this->assertEquals($payload['info']['browserWidth'], $rowInfo->browser_width);
        $this->assertEquals($payload['info']['browserHeight'], $rowInfo->browser_height);
        $this->assertEquals($payload['info']['deviceWidth'], $rowInfo->device_width);
        $this->assertEquals($payload['info']['deviceHeight'], $rowInfo->device_height);

        $rowClick = DB::table('laralytics_click')->orderBy('id', 'DESC')->take(count($payload['click']))->get();
        $rowClick = array_reverse($rowClick);

        foreach ($rowClick as $key => $click) {
            $this->assertEquals($payload['click'][$key]['x'], $click->x);
            $this->assertEquals($payload['click'][$key]['y'], $click->y);
            $this->assertEquals($payload['click'][$key]['element'], $click->element);
        }

        $rowCustom = DB::table('laralytics_custom')->orderBy('id', 'DESC')->take(count($payload['custom']))->get();
        $rowCustom = array_reverse($rowCustom);

        foreach ($rowCustom as $key => $custom) {
            $this->assertEquals($payload['custom'][$key]['x'], $custom->x);
            $this->assertEquals($payload['custom'][$key]['y'], $custom->y);
            $this->assertEquals($payload['custom'][$key]['element'], $custom->element);
        }
    }

    /**
     * Test the url method with the file driver.
     */
    public function testPayloadInsertFile()
    {
        // set file driver
        app('config')->set('laralytics.driver', 'file');

        // Storage file
        $infoStorageFile = storage_path('app/laralytics-info.log');
        $clickStorageFile = storage_path('app/laralytics-click.log');
        $customStorageFile = storage_path('app/laralytics-custom.log');

        // Delete already existing log file
        if (file_exists($infoStorageFile)) {
            unlink($infoStorageFile);
        }

        if (file_exists($clickStorageFile)) {
            unlink($clickStorageFile);
        }

        if (file_exists($customStorageFile)) {
            unlink($customStorageFile);
        }

        $instance = new Laralytics();
        $payload = $this->getSamplePayload();

        /** @var \Illuminate\Http\Request $request */
        $request = app()->make('Illuminate\Http\Request');
        $request = $request::create(str_random(5), 'GET');

        $instance->payload($request, $payload, true);

        $infoData = file($infoStorageFile);
        $this->assertEquals(count($infoData), 1);
        $info = json_decode(array_shift($infoData), true);

        $this->assertEquals($payload['info']['version'], $info['version']);
        $this->assertEquals($payload['info']['browserWidth'], $info['browser_width']);
        $this->assertEquals($payload['info']['browserHeight'], $info['browser_height']);
        $this->assertEquals($payload['info']['deviceWidth'], $info['device_width']);
        $this->assertEquals($payload['info']['deviceHeight'], $info['device_height']);

        $clickData = file($clickStorageFile);
        $this->assertEquals(count($payload['click']), count($clickData));

        foreach ($clickData as $key => $click) {
            $click = json_decode($click, true);

            $this->assertEquals($payload['click'][$key]['x'], $click['x']);
            $this->assertEquals($payload['click'][$key]['y'], $click['y']);
            $this->assertEquals($payload['click'][$key]['element'], $click['element']);
        }

        $customData = file($customStorageFile);
        $this->assertEquals(count($payload['custom']), count($customData));

        foreach ($customData as $key => $custom) {
            $custom = json_decode($custom, true);

            $this->assertEquals($payload['custom'][$key]['x'], $custom['x']);
            $this->assertEquals($payload['custom'][$key]['y'], $custom['y']);
            $this->assertEquals($payload['custom'][$key]['element'], $custom['element']);
        }
    }

    /**
     * Test the payload method with the syslog driver.
     */
    public function testPayloadInsertSyslog()
    {
        // set syslog driver
        app('config')->set('laralytics.driver', 'syslog');
        app('config')->set('laralytics.syslog.facility', LOG_LOCAL0);

        $instance = new Laralytics();
        $payload = $this->getSamplePayload();

        /** @var \Illuminate\Http\Request $request */
        $request = app()->make('Illuminate\Http\Request');
        $request = $request::create(str_random(5), 'GET');

        $instance->payload($request, $payload, true);

        $file = file('/var/log/syslog');
        $file = array_reverse($file);

        $linesInfo = '';
        $linesClick = [];
        $linesCustom = [];

        foreach ($file as $line) {

            if (strpos($line, 'laralytics-info') > -1 && empty($linesInfo)) {
                $linesInfo = json_decode(substr($line, strpos($line, '{')), true);
            }

            if (strpos($line, 'laralytics-click') > -1 && count($linesClick) < count($payload['click'])) {
                $linesClick[] = json_decode(substr($line, strpos($line, '{')), true);
            }

            if (strpos($line, 'laralytics-custom') > -1 && count($linesCustom) < count($payload['custom'])) {
                $linesCustom[] = json_decode(substr($line, strpos($line, '{')), true);
            }
        }

        $linesClick = array_reverse($linesClick);
        $linesCustom = array_reverse($linesCustom);

        $this->assertEquals($payload['info']['version'], $linesInfo['version']);
        $this->assertEquals($payload['info']['browserWidth'], $linesInfo['browser_width']);
        $this->assertEquals($payload['info']['browserHeight'], $linesInfo['browser_height']);
        $this->assertEquals($payload['info']['deviceWidth'], $linesInfo['device_width']);
        $this->assertEquals($payload['info']['deviceHeight'], $linesInfo['device_height']);

        foreach ($linesClick as $key => $click) {

            $this->assertEquals($payload['click'][$key]['x'], $click['x']);
            $this->assertEquals($payload['click'][$key]['y'], $click['y']);
            $this->assertEquals($payload['click'][$key]['element'], $click['element']);
        }

        foreach ($linesCustom as $key => $custom) {

            $this->assertEquals($payload['custom'][$key]['x'], $custom['x']);
            $this->assertEquals($payload['custom'][$key]['y'], $custom['y']);
            $this->assertEquals($payload['custom'][$key]['element'], $custom['element']);
        }
    }

    /**
     * Test the payload method with the syslogd driver.
     */
    public function testPayloadInsertSyslogd()
    {
        //@TODO: find a way to specify identity using syslogd

        // set syslogd driver
        app('config')->set('laralytics.driver', 'syslogd');
        app('config')->set('laralytics.syslog.facility', LOG_LOCAL0);
        app('config')->set('laralytics.syslog.remote', [
            'host' => '127.0.0.1',
            'port' => 514
        ]);

        $instance = new Laralytics();
        $payload = $this->getSamplePayload();

        /** @var \Illuminate\Http\Request $request */
        $request = app()->make('Illuminate\Http\Request');
        $request = $request::create(str_random(5), 'GET');

        $instance->payload($request, $payload, true);

        $file = file('/var/log/syslog');
        $file = array_reverse($file);

        $linesInfo = '';
        $linesClick = [];
        $linesCustom = [];

        foreach ($file as $line) {

            if (strpos($line, 'laralytics-info') > -1 && empty($linesInfo)) {
                $linesInfo = json_decode(substr($line, strpos($line, '{')), true);
            }

            if (strpos($line, 'laralytics-click') > -1 && count($linesClick) < count($payload['click'])) {
                $linesClick[] = json_decode(substr($line, strpos($line, '{')), true);
            }

            if (strpos($line, 'laralytics-custom') > -1 && count($linesCustom) < count($payload['custom'])) {
                $linesCustom[] = json_decode(substr($line, strpos($line, '{')), true);
            }
        }

        $linesClick = array_reverse($linesClick);
        $linesCustom = array_reverse($linesCustom);

        $this->assertEquals($payload['info']['version'], $linesInfo['version']);
        $this->assertEquals($payload['info']['browserWidth'], $linesInfo['browser_width']);
        $this->assertEquals($payload['info']['browserHeight'], $linesInfo['browser_height']);
        $this->assertEquals($payload['info']['deviceWidth'], $linesInfo['device_width']);
        $this->assertEquals($payload['info']['deviceHeight'], $linesInfo['device_height']);

        foreach ($linesClick as $key => $click) {
            $this->assertEquals($payload['click'][$key]['x'], $click['x']);
            $this->assertEquals($payload['click'][$key]['y'], $click['y']);
            $this->assertEquals($payload['click'][$key]['element'], $click['element']);
        }

        foreach ($linesCustom as $key => $custom) {
            $this->assertEquals($payload['custom'][$key]['x'], $custom['x']);
            $this->assertEquals($payload['custom'][$key]['y'], $custom['y']);
            $this->assertEquals($payload['custom'][$key]['element'], $custom['element']);
        }
    }

    public function testCheckCookie()
    {
        $instance = new Laralytics();

        /** @var \Illuminate\Http\Request $request */
        $request = app()->make('Illuminate\Http\Request');
        $request = $request::create(str_random(5), 'GET');

        $cookie = $instance->checkCookie($request);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $cookie);
    }

    public function testGetUserId()
    {
        $instance = Mockery::mock('Bsharp\Laralytics\Laralytics')->shouldAllowMockingProtectedMethods();

        $instance->shouldReceive('getUserId')->once()->andReturn(0);
    }

    public function testHash()
    {
        $instance = Mockery::mock('Bsharp\Laralytics\Laralytics')->shouldAllowMockingProtectedMethods();

        $instance->shouldReceive('hash')
            ->once()
            ->with('test.com', '/home')
            ->andReturn(hash('md4', 'test.com', '/home'));
    }
}
