<?php

use App\Http\Controllers\Api\UsahaController;
use App\Http\Controllers\Api\WilayahController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/usaha', [UsahaController::class, 'index']);
Route::get('/wilayah/kecamatan', [WilayahController::class, 'kecamatan']);
Route::get('/geojson/subsls', [WilayahController::class, 'subslsGeojson']);
Route::get('/usaha/suggestions', [UsahaController::class, 'searchSuggestions']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
