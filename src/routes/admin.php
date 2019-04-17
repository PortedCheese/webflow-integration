<?php

Route::group([
    'prefix' => "admin/webflow",
    'middleware' => ['web', 'role:admin'],
    'namespace' => 'PortedCheese\WebflowIntegration\Http\Controllers',
    'as' => 'admin.webflow.',
], function () {
    Route::get('/', "WebflowController@index")
        ->name('index');
    Route::post('/', "WebflowController@load")
        ->name('load');
});

Route::group([
    'namespace' => 'App\Http\Controllers\Site',
    'middleware' => ['web'],
    'as' => 'webflow.page.',
], function () {
    $menus = Cache::remember('pages', 5, function() {
        return \App\Menu::all();
    });
    if (!empty($menus)) {
        foreach ($menus as $menu) {
            Route::get("/$menu->key", 'HomeController@menu')->name($menu->key);
        }
    }
});