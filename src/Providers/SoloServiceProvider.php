<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com>
 * @link https://aaronfrancis.com
 * @link https://twitter.com/aarondfrancis
 */

namespace AaronFrancis\Solo\Providers;

use AaronFrancis\Solo\Console\Commands\About;
use AaronFrancis\Solo\Console\Commands\Dumps;
use AaronFrancis\Solo\Console\Commands\Install;
use AaronFrancis\Solo\Console\Commands\Make;
use AaronFrancis\Solo\Console\Commands\Monitor;
use AaronFrancis\Solo\Console\Commands\Solo;
use AaronFrancis\Solo\Manager;
use AaronFrancis\Solo\Support\CustomDumper;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class SoloServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Manager::class);

        $this->mergeConfigFrom(__DIR__ . '/../../config/solo.php', 'solo');
    }

    public function boot()
    {
        $this->registerDumper();

        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->registerCommands();
        $this->publishFiles();
    }

    protected function registerCommands()
    {
        $this->commands([
            Monitor::class,
            Solo::class,
            Install::class,
            About::class,
            Make::class,
            Dumps::class
        ]);

        if (class_exists('\AaronFrancis\Solo\Console\Commands\Test')) {
            $this->commands([
                '\AaronFrancis\Solo\Console\Commands\Test',
            ]);
        }
    }

    protected function registerDumper()
    {
        CustomDumper::register($this->app->basePath(), $this->app['config']->get('view.compiled'));
    }

    protected function publishFiles()
    {
        $this->publishes([
            __DIR__ . '/../../config/solo.php' => config_path('solo.php'),
        ], 'solo-config');
    }
}
