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
    }

    public function register()
    {

    }

}