<?php

use Illuminate\Support\Facades\Route;
use LaravelCommonNew\DocTools\DocController;

if (config('common.showDoc')) {
    Route::middleware(['api'])->prefix('/api/docs')->name('api.docs.')->group(function ($router) {
        $router->get('openapi', [DocController::class, 'getOpenApi']);
    });
}
