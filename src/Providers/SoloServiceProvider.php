<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com>
 * @link https://aaronfrancis.com
 * @link https://twitter.com/aarondfrancis
 */

namespace SoloTerm\Solo\Providers;

use SoloTerm\Solo\Console\Commands\About;
use SoloTerm\Solo\Console\Commands\Dumps;
use SoloTerm\Solo\Console\Commands\Install;
use SoloTerm\Solo\Console\Commands\Make;
use SoloTerm\Solo\Console\Commands\Monitor;
use SoloTerm\Solo\Console\Commands\Solo;
use SoloTerm\Solo\Manager;
use SoloTerm\Solo\Support\CustomDumper;
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

        if (class_exists('\SoloTerm\Solo\Console\Commands\Test')) {
            $this->commands([
                '\SoloTerm\Solo\Console\Commands\Test',
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
