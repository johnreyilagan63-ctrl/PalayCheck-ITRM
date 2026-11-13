<?php

use App\Http\Controllers\Admin\ActivityController as AdminActivityController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DetectionController as AdminDetectionController;
use App\Http\Controllers\Admin\ImageController as AdminImageController;
use App\Http\Controllers\Admin\ModelController as AdminModelController;
use App\Http\Controllers\Admin\ResourceController as AdminResourceController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\AdviceController;
use App\Http\Controllers\Api\DetectionApiController;
use App\Http\Controllers\Api\DetectionStatusController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DetectionController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\PestController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/pests', [PestController::class, 'index'])->name('pests.index');

Route::get('/learning', [LearningController::class, 'index'])->name('learning.index');
Route::get('/learning/{learning}', [LearningController::class, 'show'])->name('learning.show');

Route::middleware('auth')->group(function () {
    Route::get('/pests/{pest}', [PestController::class, 'show'])->name('pests.show');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/advice', [AdviceController::class, 'index'])->name('advice.index');

    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
    Route::get('/history/{detection}', [HistoryController::class, 'show'])->name('history.show');
    Route::post('/history/{detection}/delete', [HistoryController::class, 'destroy'])->name('history.destroy');

    Route::get('/detection', [DetectionController::class, 'index'])->name('detection.index');
    Route::get('/api/detection-status', DetectionStatusController::class)->name('detection.status');
    Route::post('/api/detect', DetectionApiController::class)->name('detection.api');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::redirect('/', '/admin/dashboard');
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/detections', [AdminDetectionController::class, 'index'])->name('detections.index');
    Route::post('/detections/{detection}/delete', [AdminDetectionController::class, 'destroy'])->name('detections.destroy');

    Route::get('/images', [AdminImageController::class, 'index'])->name('images.index');
    Route::get('/activity', [AdminActivityController::class, 'index'])->name('activity.index');

    Route::get('/model', [AdminModelController::class, 'index'])->name('model.index');
    Route::post('/model', [AdminModelController::class, 'store'])->name('model.store');

    Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [AdminSettingController::class, 'update'])->name('settings.update');

    Route::get('/{resource}', [AdminResourceController::class, 'index'])
        ->whereIn('resource', ['users', 'pests', 'advice', 'learning'])
        ->name('resources.index');
    Route::get('/{resource}/new', [AdminResourceController::class, 'create'])
        ->whereIn('resource', ['users', 'pests', 'advice', 'learning'])
        ->name('resources.create');
    Route::post('/{resource}', [AdminResourceController::class, 'store'])
        ->whereIn('resource', ['users', 'pests', 'advice', 'learning'])
        ->name('resources.store');
    Route::get('/{resource}/{id}/edit', [AdminResourceController::class, 'edit'])
        ->whereIn('resource', ['users', 'pests', 'advice', 'learning'])
        ->whereNumber('id')
        ->name('resources.edit');
    Route::post('/{resource}/{id}/edit', [AdminResourceController::class, 'update'])
        ->whereIn('resource', ['users', 'pests', 'advice', 'learning'])
        ->whereNumber('id')
        ->name('resources.update');
    Route::post('/{resource}/{id}/delete', [AdminResourceController::class, 'destroy'])
        ->whereIn('resource', ['users', 'pests', 'advice', 'learning'])
        ->whereNumber('id')
        ->name('resources.destroy');
});
