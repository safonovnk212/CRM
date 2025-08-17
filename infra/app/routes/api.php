<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IntakeController;

Route::post("intake", [IntakeController::class, "store"]);
