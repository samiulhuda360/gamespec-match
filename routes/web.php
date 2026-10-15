<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AppController;
use App\Http\Controllers\HardwareController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\UpgradeController;

use App\Http\Controllers\CollectionPageController;

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CollectionController;

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AskController;
use App\Http\Controllers\ProfileController;

Route::get('/', [AppController::class, 'index'])->name('apps.index');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/apps/suggest', [AppController::class, 'suggest'])->name('apps.suggest');
Route::get('/game/{app:slug}', [AppController::class, 'show'])
    ->name('apps.show');


// Browse & Filter Pages
Route::get('/games', [AppController::class, 'browse'])->name('apps.browse');
Route::get('/games/free', [AppController::class, 'free'])->name('apps.free');
Route::get('/games/latest', [AppController::class, 'latest'])->name('apps.latest');
Route::get('/games/popular', [AppController::class, 'popular'])->name('apps.popular');
Route::get('/games/deals', [SearchController::class, 'deals'])->name('deals.index');

Route::prefix('hardware')->name('hardware.')->group(function () {
    Route::get('/add-specs', [HardwareController::class, 'addSpecs'])->name('add-specs');
    Route::redirect('/create', '/hardware/add-specs', 301)->name('create');
    Route::post('/', [HardwareController::class, 'store'])->name('store');
    Route::post('/describe', [HardwareController::class, 'describe'])->middleware('throttle:ai')->name('describe');
    Route::get('/{id}', [HardwareController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [HardwareController::class, 'edit'])->name('edit');
    Route::put('/{id}', [HardwareController::class, 'update'])->name('update');

    // Rate limited endpoints (60 requests per minute per IP)
    Route::middleware('throttle:60,1')->group(function () {
        Route::post('/check/{appId}', [HardwareController::class, 'checkGame'])->name('check');
    });
});

Route::get('/search', fn (Request $request) => redirect()->route('search.advanced', $request->query()))
    ->name('search.index');

Route::get('/search/advanced', [SearchController::class, 'advanced'])->name('search.advanced');

// Plain-words search (AI when configured, rules otherwise)
Route::get('/ask', AskController::class)->middleware('throttle:ai')->name('ask');
Route::get('/upgrade', UpgradeController::class)->middleware('throttle:ai')->name('upgrade');

// Keep old route for backward compatibility
Route::get('/apps', function () {
    return redirect()->route('apps.browse');
});

// ✅ PUBLIC collection page
Route::get('/collections/{slug}', [CollectionPageController::class, 'show'])
    ->name('collections.show');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'show'])->name('admin.login');
    Route::post('login', [AuthController::class, 'login'])->name('admin.login.submit');
    Route::post('logout', [AuthController::class, 'logout'])->name('admin.logout');

    Route::middleware('admin')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/hardware-detections', [\App\Http\Controllers\Admin\HardwareDetectionController::class, 'index'])->name('hardware-detections');

        Route::get('/collections', [CollectionController::class, 'index'])->name('collections.index');
        Route::get('/collections/create', [CollectionController::class, 'create'])->name('collections.create');
        Route::post('/collections', [CollectionController::class, 'store'])->name('collections.store');
        Route::get('/collections/{collection}/edit', [CollectionController::class, 'edit'])->name('collections.edit');
        Route::put('/collections/{collection}', [CollectionController::class, 'update'])->name('collections.update');
        Route::delete('/collections/{collection}', [CollectionController::class, 'destroy'])->name('collections.destroy');
    });
});

// Laravel Breeze Authentication Routes
Route::get('/account', AccountController::class)->middleware(['auth', 'verified'])->name('dashboard');
Route::redirect('/dashboard', '/account');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Hardware Detection API
Route::post('/api/hardware/detection', [App\Http\Controllers\Api\HardwareDetectionController::class, 'store'])->name('api.hardware.detection');
Route::get('/api/hardware/detection/stats', [App\Http\Controllers\Api\HardwareDetectionController::class, 'unmatchedStats'])->middleware('admin')->name('api.hardware.detection.stats');

require __DIR__.'/auth.php';
