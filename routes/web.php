<?php

use App\Http\Controllers\PullHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PullHistoryController::class, 'dashboard'])->name('dashboard');
Route::get('/pull-history', [PullHistoryController::class, 'history'])->name('pull-history.index');
Route::get('/api', [PullHistoryController::class, 'index'])->name('api.index');
Route::get('/pull-history/export', [PullHistoryController::class, 'export'])->name('pull-history.export');
