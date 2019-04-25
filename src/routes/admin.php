<?php

Route::group([
    'prefix' => "admin/webflow",
    'middleware' => ['web', 'role:admin'],
    'namespace' => 'PortedCheese\WebflowIntegration\Http\Controllers',
    'as' => 'admin.webflow.',
], function () {
    Route::get('/download', "WebflowController@index")
        ->name('download');
    Route::post('/download', "WebflowController@load")
        ->name('load');

    Route::get('/', "WebflowController@list")
        ->name('index');
    Route::get('/{page}', "WebflowController@show")
        ->name('show');

    Route::post('/set/{page}', "WebflowController@setHome")
        ->name('set-home');
    Route::post('/unset/{page}', "WebflowController@unsetHome")
        ->name('unset-home');
});

Route::group([
    'namespace' => 'PortedCheese\WebflowIntegration\Http\Controllers',
    'middleware' => ['web'],
    'as' => 'webflow.page.',
], function () {
    $pages = Cache::remember('webflow-pages', 5, function() {
        return \PortedCheese\WebflowIntegration\Models\WebflowPage::all();
    });
    if (!empty($pages)) {
        foreach ($pages as $page) {
            Route::get("/$page->slug", 'WebflowController@page')->name($page->slug);
        }
    }
});