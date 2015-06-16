<?php

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

    public function testDatabaseDriver()
    {
        // set database driver
        app('config')->set('laralytics.driver', 'database');

        $instance = new \Bsharp\Laralytics\Laralytics();

        $host = str_random(10);
        $path = '/' . str_random(10);

        $instance->url($host, $path, 'GET');

        $row = \DB::table('laralytics_url')->orderBy('id', 'DESC')->first();

        $this->assertEquals($host, $row->host);
        $this->assertEquals($path, $row->path);
    }
}
