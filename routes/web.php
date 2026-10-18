<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dealer;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\Public;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web publik (SSR untuk SEO — Blueprint UI §5 & §18)
|--------------------------------------------------------------------------
*/
Route::get('/', [Public\HomeController::class, 'index'])->name('home');
Route::get('/kendaraan', [Public\CatalogController::class, 'index'])->name('catalog');
Route::get('/motor', [Public\CatalogController::class, 'preset'])->defaults('preset', 'motor')->name('catalog.motor');
Route::get('/motor/baru', [Public\CatalogController::class, 'preset'])->defaults('preset', 'motor-baru')->name('catalog.motor.new');
Route::get('/motor/seken', [Public\CatalogController::class, 'preset'])->defaults('preset', 'motor-seken')->name('catalog.motor.used');
Route::get('/mobil', [Public\CatalogController::class, 'preset'])->defaults('preset', 'mobil')->name('catalog.car');
Route::get('/iklan/{listing}', [Public\ListingController::class, 'show'])->name('listings.show');
Route::post('/iklan/{listing}/minat', [Public\ListingController::class, 'lead'])->middleware('throttle:leads')->name('listings.lead');
Route::post('/iklan/{listing}/laporkan', [Public\ListingController::class, 'report'])->middleware('throttle:reports')->name('listings.report');
Route::get('/dealer', [Public\DealerController::class, 'index'])->name('dealers.index');
Route::get('/dealer/{tenant}', [Public\DealerController::class, 'show'])->name('dealers.show');

Route::view('/simulasi-kredit', 'public.pages.credit')->name('pages.credit');
Route::view('/tukar-tambah', 'public.pages.trade-in')->name('pages.trade-in');
Route::view('/panduan', 'public.pages.guide')->name('pages.guide');
Route::view('/pusat-bantuan', 'public.pages.help')->name('pages.help');
Route::view('/tentang-kami', 'public.pages.about')->name('pages.about');
Route::view('/kontak', 'public.pages.contact')->name('pages.contact');
Route::view('/kebijakan-privasi', 'public.pages.privacy')->name('pages.privacy');
Route::view('/syarat-ketentuan', 'public.pages.terms')->name('pages.terms');
Route::view('/laporkan-iklan', 'public.pages.report-info')->name('pages.report');
Route::view('/offline', 'public.pages.offline')->name('offline');
Route::get('/sitemap.xml', [Public\SitemapController::class, 'index'])->name('sitemap');
Route::get('/jual', [Public\SellController::class, 'create'])->name('sell.create');

/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:login');
});
Route::post('/keluar', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Akun pembeli & penjual perorangan
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/jual', [Public\SellController::class, 'store'])->name('sell.store');
    Route::post('/iklan/{listing}/favorit', [Public\ListingController::class, 'toggleFavorite'])->name('listings.favorite');
    Route::post('/iklan/{listing}/booking', [Public\ListingController::class, 'book'])->middleware('throttle:leads')->name('listings.book');

    Route::get('/akun', [AccountController::class, 'index'])->name('account.index');
    Route::get('/akun/favorit', [AccountController::class, 'favorites'])->name('account.favorites');
    Route::get('/akun/iklan', [AccountController::class, 'listings'])->name('account.listings');
    Route::get('/akun/iklan/{listing}', [AccountController::class, 'showListing'])->name('account.listings.show');
    Route::post('/akun/iklan/{listing}/ajukan', [AccountController::class, 'submitListing'])->name('account.listings.submit');
    Route::post('/akun/iklan/{listing}/terjual', [AccountController::class, 'soldListing'])->name('account.listings.sold');
    Route::post('/akun/iklan/{listing}/arsip', [AccountController::class, 'archiveListing'])->name('account.listings.archive');
    Route::get('/akun/minat', [AccountController::class, 'leads'])->name('account.leads');
    Route::patch('/akun/profil', [AccountController::class, 'updateProfile'])->name('account.profile');
    Route::patch('/akun/tema', [AccountController::class, 'updateTheme'])->name('account.theme');

    // Media unit: unggah per kategori & akses dokumen privat (dengan otorisasi + audit)
    Route::post('/unit/{unit}/media', [MediaController::class, 'store'])->name('media.store');
    Route::patch('/media/{media}/izin-publikasi', [MediaController::class, 'consent'])->name('media.consent');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.private');
});

/*
|--------------------------------------------------------------------------
| Dashboard Dealer SaaS (tenant-scoped)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('dasbor')->name('dealer.')->group(function () {
    Route::get('/mulai', [Dealer\OnboardingController::class, 'create'])->name('onboarding');
    Route::post('/mulai', [Dealer\OnboardingController::class, 'store'])->name('onboarding.store');

    Route::middleware('tenant')->group(function () {
        Route::get('/', [Dealer\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/ditangguhkan', [Dealer\DashboardController::class, 'suspended'])->name('suspended');
        Route::post('/pindah/{tenantId}', [Dealer\DashboardController::class, 'switchTenant'])->name('switch');

        Route::get('/unit', [Dealer\UnitController::class, 'index'])->name('units.index');
        Route::get('/unit/baru', [Dealer\UnitController::class, 'create'])->name('units.create');
        Route::post('/unit', [Dealer\UnitController::class, 'store'])->name('units.store');
        Route::get('/unit/{unit}', [Dealer\UnitController::class, 'show'])->name('units.show');
        Route::get('/unit/{unit}/ubah', [Dealer\UnitController::class, 'edit'])->name('units.edit');
        Route::put('/unit/{unit}', [Dealer\UnitController::class, 'update'])->name('units.update');
        Route::post('/unit/{unit}/status', [Dealer\UnitController::class, 'status'])->name('units.status');
        Route::post('/unit/{unit}/biaya', [Dealer\UnitController::class, 'addCost'])->name('units.costs.store');
        Route::post('/unit/{unit}/biaya/{cost}/reversal', [Dealer\UnitController::class, 'reverseCost'])->name('units.costs.reverse');
        Route::post('/unit/{unit}/iklan', [Dealer\ListingController::class, 'store'])->name('listings.store');
        Route::post('/media/{media}/verifikasi', [Dealer\UnitController::class, 'verifyMedia'])->name('media.verify');

        Route::get('/iklan', [Dealer\ListingController::class, 'index'])->name('listings.index');
        Route::put('/iklan/{listing}', [Dealer\ListingController::class, 'update'])->name('listings.update');
        Route::post('/iklan/{listing}/ajukan', [Dealer\ListingController::class, 'submit'])->name('listings.submit');
        Route::post('/iklan/{listing}/terjual', [Dealer\ListingController::class, 'sold'])->name('listings.sold');
        Route::post('/iklan/{listing}/arsip', [Dealer\ListingController::class, 'archive'])->name('listings.archive');

        Route::get('/leads', [Dealer\LeadController::class, 'index'])->name('leads.index');
        Route::get('/leads/{lead}', [Dealer\LeadController::class, 'show'])->name('leads.show');
        Route::patch('/leads/{lead}', [Dealer\LeadController::class, 'update'])->name('leads.update');

        Route::get('/booking', [Dealer\BookingController::class, 'index'])->name('bookings.index');
        Route::patch('/booking/{booking}', [Dealer\BookingController::class, 'update'])->name('bookings.update');

        Route::get('/anggota', [Dealer\MemberController::class, 'index'])->name('members.index');
        Route::post('/anggota', [Dealer\MemberController::class, 'store'])->name('members.store');
        Route::patch('/anggota/{membership}', [Dealer\MemberController::class, 'update'])->name('members.update');

        Route::get('/langganan', [Dealer\BillingController::class, 'index'])->name('billing');
        Route::get('/audit', [Dealer\SettingsController::class, 'audit'])->name('audit');
        Route::get('/pengaturan', [Dealer\SettingsController::class, 'edit'])->name('settings');
        Route::put('/pengaturan/profil', [Dealer\SettingsController::class, 'updateProfile'])->name('settings.profile');
        Route::put('/pengaturan/tema', [Dealer\SettingsController::class, 'updateTheme'])->name('settings.theme');
        Route::post('/pengaturan/cabang', [Dealer\SettingsController::class, 'storeBranch'])->name('settings.branches.store');
    });
});

/*
|--------------------------------------------------------------------------
| Panel admin platform
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'platform'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('platform:moderator')->group(function () {
        Route::get('/moderasi', [Admin\ModerationController::class, 'index'])->name('moderation');
        Route::get('/moderasi/{listing}', [Admin\ModerationController::class, 'show'])->name('moderation.show');
        Route::post('/moderasi/{listing}/setujui', [Admin\ModerationController::class, 'approve'])->name('moderation.approve');
        Route::post('/moderasi/{listing}/tolak', [Admin\ModerationController::class, 'reject'])->name('moderation.reject');
        Route::get('/laporan', [Admin\ReportController::class, 'index'])->name('reports');
        Route::patch('/laporan/{report}', [Admin\ReportController::class, 'update'])->name('reports.update');
    });

    Route::middleware('platform:admin')->group(function () {
        Route::get('/tenant', [Admin\TenantController::class, 'index'])->name('tenants.index');
        Route::get('/tenant/{tenantId}', [Admin\TenantController::class, 'show'])->name('tenants.show');
        Route::post('/tenant/{tenantId}/status', [Admin\TenantController::class, 'status'])->name('tenants.status');
        Route::post('/tenant/{tenantId}/verifikasi', [Admin\TenantController::class, 'verify'])->name('tenants.verify');
        Route::get('/pengguna', [Admin\UserController::class, 'index'])->name('users.index');
        Route::post('/pengguna/{user}/status', [Admin\UserController::class, 'status'])->name('users.status');
        Route::get('/katalog', [Admin\CatalogController::class, 'index'])->name('catalog');
        Route::post('/katalog/merek', [Admin\CatalogController::class, 'storeBrand'])->name('catalog.brands.store');
        Route::post('/katalog/model', [Admin\CatalogController::class, 'storeModel'])->name('catalog.models.store');
        Route::get('/paket', [Admin\PlanController::class, 'index'])->name('plans');
        Route::put('/paket/{plan}', [Admin\PlanController::class, 'update'])->name('plans.update');
        Route::get('/audit', [Admin\DashboardController::class, 'audit'])->name('audit');
    });
});
