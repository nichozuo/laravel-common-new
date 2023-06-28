<?php

use Illuminate\Support\Facades\Route;
use LaravelCommonNew\DocTools\DocController;

if (config('app.debug')) {
    Route::middleware(['api'])->prefix('/api/docs')->name('api.docs.')->group(function ($router) {
        $router->get('openapi', [DocController::class, 'getOpenApi']);
    });
}
