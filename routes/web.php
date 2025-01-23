<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AppController::class, 'show'])->name('home');
Route::resource('events', EventController::class);
