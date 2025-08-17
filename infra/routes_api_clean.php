<?php

use Illuminate\Support\Facades\Route;

Route::get('health', \App\Http\Controllers\Api\HealthController::class);
Route::post('lead', \App\Http\Controllers\Api\LeadController::class);
