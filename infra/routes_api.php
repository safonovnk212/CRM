<?php

use Illuminate\Support\Facades\Route;
// Если нужно логирование, импорт Log можно оставить тут, НО не в середине файла.
// use Illuminate\Support\Facades\Log;

Route::get('health', \App\Http\Controllers\Api\HealthController::class);
Route::post('lead', \App\Http\Controllers\Api\LeadController::class);
