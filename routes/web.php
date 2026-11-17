<?php

use App\Http\Controllers\BoardController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\VacancyController;
use App\Http\Controllers\VacancyImportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('board', BoardController::class)->name('board');

    Route::resource('vacancies', VacancyController::class);
    Route::post('vacancy-imports', [VacancyImportController::class, 'store'])->middleware('throttle:20,1')->name('vacancy-imports.store');
    Route::resource('companies', CompanyController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::patch('job-applications/{jobApplication}/move', [JobApplicationController::class, 'move'])->name('job-applications.move');
    Route::patch('job-applications/{jobApplication}', [JobApplicationController::class, 'update'])->name('job-applications.update');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
