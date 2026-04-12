<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/* |-------------------------------------------------------------------------- | API Routes |-------------------------------------------------------------------------- | | Here is where you can register API routes for your application. These | routes are loaded by the RouteServiceProvider within a group which | is assigned the "api" middleware group. Enjoy building your API! | */

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Example API route
Route::get('/v1/status', function () {
    return response()->json([
    'status' => 'online',
    'system' => 'DepEd Leave Card Management System',
    'version' => '1.0.0'
    ]);
});

// Sync Routes
Route::post('/sync-employee', [\App\Http\Controllers\Api\EmployeeSyncController::class, 'sync']);
Route::post('/sync-employee-bulk', [\App\Http\Controllers\Api\EmployeeSyncController::class, 'syncBulk']);
Route::post('/sync-users', [\App\Http\Controllers\Api\UserSyncController::class, 'receive']);

// New API Route Aliases (DepEd Integration V2)
Route::post('/receive-user', [\App\Http\Controllers\Api\EmployeeSyncController::class, 'sync']);
Route::post('/receive-masterlist', [\App\Http\Controllers\Api\EmployeeSyncController::class, 'syncBulk']);
