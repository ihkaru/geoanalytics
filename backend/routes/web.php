<?php

use App\Http\Controllers\DataQualityController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/data-quality/usaha', [DataQualityController::class, 'usahaCheck']);
