<?php

namespace PortedCheese\WebflowIntegration;

use App\Menu;
use Illuminate\Support\ServiceProvider;
use PortedCheese\WebflowIntegration\Console\Commands\WebflowParseCommand;

class WebflowIntegrationServiceProvider extends ServiceProvider
{

    public function boot()
    {
        // Подключение роутов.
        $this->loadRoutesFrom(__DIR__ . '/routes/admin.php');

        // Подключение шаблонов.
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'webflow-integration');

        // Подключение миграций.
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Добавить конфигурацию.
        app()->config['filesystems.disks.webflow'] = [
            'driver' => 'local',
            'root' => public_path('webflow'),
        ];

        // Подключаем метатеги.
        $seo = app()->config['seo-integration.models'];
        $seo['webflow_pages'] = 'PortedCheese\WebflowIntegration\Models\WebflowPage';
        app()->config['seo-integration.models'] = $seo;

        // Настраиваем тему.
        $theme = app()->config['theme.themes'];
        $theme['webflow'] = 'webflow.index';
        app()->config['theme.themes'] = $theme;

        // Console.
        $this->commands([
            WebflowParseCommand::class,
        ]);

        // Добавить меню.
        view()->composer('layouts.webflow.index', function ($view) {
            if (class_exists('\App\Menu')) {
                $view->with('webflowMenu', Menu::getByKey('main'));
            }
            else {
                $view->with('webflowMenu', []);
            }
        });
    }

    public function register()
    {

    }

}