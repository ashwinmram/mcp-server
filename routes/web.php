<?php

use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('lang/{locale}', [LanguageController::class, 'swap'])->name('locale.swap');

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
        'demoVideoId' => config('landing.demo_video_id'),
    ]);
})->name('home');

Route::get('documentation', function () {
    return Inertia::render('Documentation', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('documentation');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
