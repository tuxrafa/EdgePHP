<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BlockController;
use App\Http\Controllers\Api\V1\FragmentController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\TemplateController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::get('/pages', [PageController::class, 'index']);
    Route::post('/pages', [PageController::class, 'store']);
    Route::get('/pages/children', [PageController::class, 'children']);
    Route::get('/pages/{id}', [PageController::class, 'show']);
    Route::patch('/pages/{id}', [PageController::class, 'update']);
    Route::post('/pages/{id}/publish', [PageController::class, 'publish']);

    Route::get('/templates', [TemplateController::class, 'index']);
    Route::post('/templates', [TemplateController::class, 'store']);
    Route::get('/templates/{id}', [TemplateController::class, 'show']);
    Route::patch('/templates/{id}', [TemplateController::class, 'update']);
    Route::post('/templates/{id}/publish', [TemplateController::class, 'publish']);
    Route::get('/templates/{id}/usage', [TemplateController::class, 'usage']);
    Route::get('/templates/{id}/preview', [TemplateController::class, 'preview']);

    Route::get('/fragments', [FragmentController::class, 'index']);
    Route::post('/fragments', [FragmentController::class, 'store']);
    Route::get('/fragments/{id}', [FragmentController::class, 'show']);
    Route::patch('/fragments/{id}', [FragmentController::class, 'update']);
    Route::post('/fragments/{id}/publish', [FragmentController::class, 'publish']);
    Route::get('/fragments/{id}/usage', [FragmentController::class, 'usage']);
    Route::get('/fragments/{id}/preview', [FragmentController::class, 'preview']);

    Route::get('/blocks', [BlockController::class, 'index']);
    Route::post('/blocks/scan', [BlockController::class, 'scan']);
    Route::get('/blocks/{name}', [BlockController::class, 'show']);
});
