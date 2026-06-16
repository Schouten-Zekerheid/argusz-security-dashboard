<?php

use App\Http\Controllers\AuditLogExportController;
use App\Http\Controllers\AzureAuthController;
use App\Http\Controllers\LocalAuthController;
use App\Livewire\Admin\AuditLog;
use App\Livewire\Admin\SlaSettings;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Dashboard;
use App\Livewire\FindingDetail;
use App\Livewire\Findings;
use App\Livewire\PipelineRunDetail;
use App\Livewire\ServiceDetail;
use App\Livewire\ServiceRunHistory;
use App\Livewire\TrendAnalysis;
use Illuminate\Support\Facades\Route;

Route::view('/', 'auth.login')->name('home');
Route::get('/dashboard', Dashboard::class)
    ->middleware('auth')
    ->name('dashboard');

Route::get('/findings', Findings::class)
    ->middleware('auth')
    ->name('findings');

Route::get('/trends', TrendAnalysis::class)
    ->middleware('auth')
    ->name('trends');

Route::get('/findings/{id}', FindingDetail::class)
    ->middleware('auth')
    ->name('findings.show');

Route::get('/services/{id}', ServiceDetail::class)
    ->middleware('auth')
    ->name('services.show');

Route::get('/services/{serviceId}/runs', ServiceRunHistory::class)
    ->middleware('auth')
    ->name('services.runs');

Route::get('/services/{serviceId}/runs/{runId}', PipelineRunDetail::class)
    ->middleware('auth')
    ->name('pipeline-runs.show');

Route::get('/admin/users', UserManagement::class)
    ->middleware(['auth', 'permission:users.manage'])
    ->name('admin.users');
Route::get('/admin/settings', SlaSettings::class)
    ->middleware(['auth', 'permission:settings.manage'])
    ->name('admin.settings');
Route::get('/admin/audit-log/export', AuditLogExportController::class)
    ->middleware(['auth', 'permission:view.logs'])
    ->name('admin.audit-log.export');
Route::get('/admin/audit-log', AuditLog::class)
    ->middleware(['auth', 'permission:view.logs'])
    ->name('admin.audit-log');

Route::post('/auth/login', [LocalAuthController::class, 'login'])
    ->name('auth.local.login');

Route::get('/auth/{provider}/redirect', [AzureAuthController::class, 'redirect'])
    ->where('provider', 'azure')
    ->name('auth.azure.redirect');
Route::get('/auth/{provider}/callback', [AzureAuthController::class, 'callback'])
    ->where('provider', 'azure')
    ->name('auth.azure.callback');
Route::post('/auth/logout', [LocalAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('auth.logout');
