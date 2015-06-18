<?php

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

        $host = str_random(10);
        $path = '/' . str_random(10);

        $instance->url($host, $path, 'GET');

        $row = \App\LaralyticsUrl::orderBy('id', 'DESC')->first();

        $this->assertEquals($host, $row->host);
        $this->assertEquals($path, $row->path);
    }

    /**
     * Test the url method with the database driver.
     */
    public function testUrlInsertDatabase()
    {
        // set database driver
        app('config')->set('laralytics.driver', 'database');

        $instance = new Laralytics();

        $host = str_random(10);
        $path = '/' . str_random(10);

        $instance->url($host, $path, 'GET');

        $row = \DB::table('laralytics_url')->orderBy('id', 'DESC')->first();

        $this->assertEquals($host, $row->host);
        $this->assertEquals($path, $row->path);
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

        $host = [];
        $path = [];

        // First line
        $host[0] = str_random(10);
        $path[0] = '/' . str_random(10);

        $instance->url($host[0], $path[0], 'GET');

        // Second line
        $host[1] = str_random(10);
        $path[1] = '/' . str_random(10);

        $instance->url($host[1], $path[1], 'GET');

        $file = file($storageFile);

        $this->assertEquals(2, count($file));

        foreach ($file as $key => $line) {
            $lineArray = json_decode($line, true);

            $this->assertEquals($host[$key], $lineArray['host']);
            $this->assertEquals($path[$key], $lineArray['path']);
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

        $host = [];
        $path = [];

        // First line
        $host[0] = str_random(10);
        $path[0] = '/' . str_random(10);

        $instance->url($host[0], $path[0], 'GET');

        // Second line
        $host[1] = str_random(10);
        $path[1] = '/' . str_random(10);

        $instance->url($host[1], $path[1], 'GET');

        $file = file('/var/log/syslog');

        $lineTwo = end($file);
        $lineOne = prev($file);

        $lineOne = json_decode(substr($lineOne, strpos($lineOne, '{')), true);
        $lineTwo = json_decode(substr($lineTwo, strpos($lineTwo, '{')), true);

        $this->assertEquals($host[0], $lineOne['host']);
        $this->assertEquals($path[0], $lineOne['path']);

        $this->assertEquals($host[1], $lineTwo['host']);
        $this->assertEquals($path[1], $lineTwo['path']);
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

        $host = [];
        $path = [];

        // First line
        $host[0] = str_random(10);
        $path[0] = '/' . str_random(10);

        $instance->url($host[0], $path[0], 'GET');

        // Second line
        $host[1] = str_random(10);
        $path[1] = '/' . str_random(10);

        $instance->url($host[1], $path[1], 'GET');

        $file = file('/var/log/syslog');

        $lineTwo = end($file);
        $lineOne = prev($file);

        $lineOne = json_decode(substr($lineOne, strpos($lineOne, '{')), true);
        $lineTwo = json_decode(substr($lineTwo, strpos($lineTwo, '{')), true);

        $this->assertEquals($host[0], $lineOne['host']);
        $this->assertEquals($path[0], $lineOne['path']);

        $this->assertEquals($host[1], $lineTwo['host']);
        $this->assertEquals($path[1], $lineTwo['path']);
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
