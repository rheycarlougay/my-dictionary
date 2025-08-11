<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DictionaryController;
use App\Http\Controllers\FavoriteController;

// Authentication routes (public)
Route::group(['prefix' => '/auth'], function ($router) {
    $controller = AuthController::class;
    $router->post('/register', [$controller, 'register']);
    $router->post('/login', [$controller, 'login']);
    $router->get('/check', [$controller, 'check']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);

    // Dictionary routes
    Route::group(['prefix' => '/dictionary'], function ($router) {
        $controller = DictionaryController::class;
        $router->get('search', [$controller, 'search']);
    });

    // Favorites routes
    Route::group(['prefix' => '/favorites'], function ($router) {
        $controller = FavoriteController::class;
        $router->get('/', [$controller, 'index']);
        $router->get('/search', [$controller, 'search']);
        $router->post('/', [$controller, 'store']);
        $router->put('/{id}', [$controller, 'update']);
        $router->delete('/{id}', [$controller, 'destroy']);
        
        // Soft delete routes
        $router->get('/trashed', [$controller, 'trashed']);
        $router->post('/{id}/restore', [$controller, 'restore']);
        $router->delete('/{id}/force', [$controller, 'forceDelete']);
        $router->post('/restore-all', [$controller, 'restoreAll']);
        $router->delete('/force-delete-all', [$controller, 'forceDeleteAll']);
    });
});
