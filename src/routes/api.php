<?php

use LaravelCommonNew\App\Helpers\RouteHelper;
use Illuminate\Support\Facades\Route;
use LaravelCommonNew\App\Middleware\JsonWrapperMiddleware;
use LaravelCommonNew\App\Modules\Docs\DocsController;

Route::middleware([JsonWrapperMiddleware::class])->prefix('/api')->name('docs.')->group(function ($router) {
    if (config('app.debug')) {
        RouteHelper::Controller($router, DocsController::class);
    }
});
