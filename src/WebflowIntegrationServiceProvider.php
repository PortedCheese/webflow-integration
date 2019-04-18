<?php

namespace PortedCheese\WebflowIntegration;

use Illuminate\Support\ServiceProvider;

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
    }

    public function register()
    {

    }

}