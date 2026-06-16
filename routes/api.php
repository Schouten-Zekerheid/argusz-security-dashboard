<?php

use App\Http\Controllers\ScanResultController;
use App\Http\Controllers\ScanTargetsController;
use App\Http\Controllers\SnoozedFingerprintsController;
use Illuminate\Support\Facades\Route;

Route::post('/ingest', [ScanResultController::class, 'store'])
    ->middleware(['throttle:60,1', 'oidc']);

Route::get('/snoozed-fingerprints', [SnoozedFingerprintsController::class, 'index'])
    ->middleware(['throttle:60,1', 'oidc']);

Route::get('/services/scan-targets', [ScanTargetsController::class, 'index'])
    ->middleware(['throttle:30,1', 'oidc']);
