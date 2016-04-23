<?php
/*
 * This file is part of the Laravel MultiLang package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\LaravelMultiLang;

use Blade;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\ServiceProvider;
use Longman\LaravelMultiLang\Console\MigrationCommand;
use Longman\LaravelMultiLang\Console\TextsCommand;

class MultiLangServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;


    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        // Publish config files
        $this->publishes([__DIR__ . '/../config/config.php' => config_path('multilang.php')]);

        // Append the country settings
        $this->mergeConfigFrom(
            __DIR__ . '/../config/config.php',
            'multilang'
        );

        // Register blade directives
        Blade::directive('t', function ($expression) {
            return "<?php echo e(t({$expression})); ?>";
        });

        /*$this->app['events']->listen(RouteMatched::class, function () {

            dump($this->app['router']);
            die();
        });*/


    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__ . '/../config/config.php';
        $this->mergeConfigFrom($configPath, 'debugbar');

        $this->app->singleton('multilang', function ($app) {
            $environment = $app->environment();
            $config = $app['config']->get('multilang');

            $multilang = new \Longman\LaravelMultiLang\MultiLang(
                $environment,
                $config,
                $app['cache'],
                $app['db']
            );

            if ($multilang->autoSaveIsAllowed()) {
                $app->terminating(function () use ($multilang) {
                    return $multilang->saveTexts();
                });
            }

            return $multilang;
        });

        $this->app->alias('multilang', 'Longman\LaravelMultiLang\MultiLang');

        $this->app['command.multilang.migration'] = $this->app->share(
            function () {
                return new MigrationCommand();
            }
        );

        $this->app['command.multilang.texts'] = $this->app->share(
            function () {
                return new TextsCommand();
            }
        );

        $this->commands(['command.multilang.migration', 'command.multilang.texts']);

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'multilang', 'command.multilang.migration',
            'command.multilang.texts', 'Longman\LaravelMultiLang\MultiLang'
        ];
    }

}
