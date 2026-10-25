<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AlbumController as AdminAlbumController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ArtistController as AdminArtistController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\GenreController as AdminGenreController;
use App\Http\Controllers\Admin\LanguageController as AdminLanguageController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\RedirectController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SongController as AdminSongController;
use App\Http\Controllers\Admin\SubmissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SongController;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ResolveRedirects;
use Illuminate\Support\Facades\Route;

Route::middleware(ResolveRedirects::class)->group(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::get('/songs', [SongController::class, 'index'])->name('songs.index');
    Route::get('/songs/{slug}', [SongController::class, 'show'])->name('songs.show');

    Route::get('/artists', [ArtistController::class, 'index'])->name('artists.index');
    Route::get('/artists/{slug}', [ArtistController::class, 'show'])->name('artists.show');

    Route::get('/albums/{slug}', [AlbumController::class, 'show'])->name('albums.show');

    Route::get('/genres', [GenreController::class, 'index'])->name('genres.index');
    Route::get('/genres/{slug}', [GenreController::class, 'show'])->name('genres.show');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1,login');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1,register');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:5,1,password')->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:5,1,password')->name('password.store');
});

Route::middleware(['auth', EnsureUserIsActive::class])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::patch('/account', [AccountController::class, 'updateProfile'])->name('account.update');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password');
    Route::delete('/account', [AccountController::class, 'destroy'])->name('account.destroy');

});

Route::middleware(['auth', EnsureUserIsActive::class, EnsureRole::class.':editor'])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminController::class)->name('dashboard');
        Route::resource('songs', AdminSongController::class)->except('show');
        Route::resource('artists', AdminArtistController::class)->except('show');
        Route::post('artists/{artist}/merge', [AdminArtistController::class, 'merge'])->name('artists.merge');
        Route::resource('albums', AdminAlbumController::class)->except('show');
        Route::resource('genres', AdminGenreController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);
        Route::resource('languages', AdminLanguageController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);
        Route::get('media', [MediaController::class, 'index'])->name('media.index');
        Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
        Route::resource('redirects', RedirectController::class)->only(['index', 'store', 'destroy']);

        Route::middleware(EnsureRole::class.':admin')->group(function () {
            Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::get('users/{user}', [UserController::class, 'edit'])->name('users.edit');
            Route::patch('users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
            Route::post('users/{user}/suspend', [UserController::class, 'suspend'])->name('users.suspend');
            Route::post('users/{user}/unsuspend', [UserController::class, 'unsuspend'])->name('users.unsuspend');
            Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });
    });

// Unknown URLs: send known old URLs to their new home, otherwise show the 404 page.
Route::fallback(fn () => abort(404))->middleware(ResolveRedirects::class);

Route::get('/search', SearchController::class)
    ->middleware('throttle:60,1')
    ->name('search');

Route::middleware(['auth', EnsureUserIsActive::class, EnsureRole::class.':contributor'])
    ->prefix('contribute')->name('contribute.')->group(function () {
        Route::get('/', [ContributionController::class, 'index'])->name('index');
        Route::get('/new', [ContributionController::class, 'create'])->name('create');
        Route::post('/', [ContributionController::class, 'store'])->middleware('throttle:20,60,submit')->name('store');
        Route::get('/{submission}', [ContributionController::class, 'show'])->name('show');
    });

Route::middleware(['auth', EnsureUserIsActive::class, EnsureRole::class.':editor'])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::get('submissions', [SubmissionController::class, 'index'])->name('submissions.index');
        Route::get('submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
        Route::post('submissions/{submission}/approve', [SubmissionController::class, 'approve'])->name('submissions.approve');
        Route::post('submissions/{submission}/reject', [SubmissionController::class, 'reject'])->name('submissions.reject');
    });

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [SeoController::class, 'index'])->name('seo.sitemap');
Route::get('/sitemaps/{type}-{page}.xml', [SeoController::class, 'show'])
    ->where(['type' => 'pages|songs|artists|albums|genres', 'page' => '[0-9]+'])
    ->name('seo.sitemap.show');

Route::get('/privacy', LegalPageController::class)->defaults('page', 'privacy')->name('legal.privacy');
Route::get('/copyright', LegalPageController::class)->defaults('page', 'copyright')->name('legal.copyright');
Route::get('/takedown', LegalPageController::class)->defaults('page', 'takedown')->name('legal.takedown');
Route::get('/report-lyrics-errors', LegalPageController::class)->defaults('page', 'corrections')->name('legal.corrections');

Route::middleware(['auth', EnsureUserIsActive::class, EnsureRole::class.':editor'])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    });

// Site settings: super administrators only.
Route::middleware(['auth', EnsureUserIsActive::class, EnsureRole::class.':super_admin'])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::get('settings/{group}', [SettingsController::class, 'show'])->name('settings.show');
        Route::put('settings/{group}', [SettingsController::class, 'update'])->name('settings.update');
    });
