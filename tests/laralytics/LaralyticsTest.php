<?php

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
     *
     * @return void
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
        $this->assertAttributeNotEmpty('models', $instance);
        $this->assertAttributeNotEmpty('syslog', $instance);
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

        $row = \DB::table('laralytics_url')->orderBy('id', 'DESC')->first();

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
        // set syslog driver
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

            if (strpos($line, '{"host"') > -1) {
                $lines[] = json_decode(substr($line, strpos($line, '{')), true);
            }
        }

        $this->assertEquals('localhost', $lines[0]['host']);
        $this->assertEquals('/' . $uri[1], $lines[0]['path']);

        $this->assertEquals('localhost', $lines[1]['host']);
        $this->assertEquals('/' . $uri[0], $lines[1]['path']);
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
