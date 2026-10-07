<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\OrganizerController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/events', [PublicController::class, 'events'])->name('events');
Route::get('/events/{event}', [PublicController::class, 'show'])->name('event');
Route::get('/packages', [PublicController::class, 'packages'])->name('packages');
Route::get('/admin/login', [AuthController::class, 'adminLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'adminLogin'])->middleware('throttle:auth');
Route::middleware('guest:web')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
    Route::view('/forgot-password', 'auth.forgot')->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'forgot'])->middleware('throttle:auth')->name('password.email');
    Route::get('/reset-password/{token}', fn (string $token) => view('auth.reset', compact('token')))->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update')->middleware('throttle:auth');
});
Route::post('/payments/midtrans/notification', [PaymentController::class, 'notification'])->middleware('throttle:60,1');
Route::middleware('auth:web')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', fn (Request $r) => redirect()->route(in_array($r->user()->role, ['volunteer', 'organizer']) ? $r->user()->role.'.activity' : 'admin.login'))->name('dashboard');
    Route::view('/email/verify', 'auth.verify')->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $r) {
        $r->fulfill();

        return redirect()->route('dashboard');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', function (Request $r) {
        $r->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Tautan verifikasi dikirim.');
    })->middleware('throttle:2,1')->name('verification.send');
    Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('application.show');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->middleware('verified')->name('document.download');
    Route::post('/notices/{notice}/read', [DashboardController::class, 'readNotice'])->name('notice.read');
    foreach (['organizer', 'volunteer'] as $role) {
        Route::middleware('role:'.$role)->prefix($role)->name($role.'.')->group(function () {
            Route::get('/dashboard', fn (Request $r) => redirect()->route($r->user()->role.'.activity'))->name('dashboard');
            Route::get('/aktivitas', [DashboardController::class, 'show'])->name('activity');
            Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
            Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
        });
    }
});
Route::middleware(['auth:web', 'verified', 'role:volunteer'])->group(function () {
    Route::get('/volunteer/applications', [ApplicationController::class, 'index'])->name('volunteer.applications');
    Route::get('/volunteer/history', [ApplicationController::class, 'history'])->name('volunteer.history');
    Route::post('/positions/{position}/apply', [ApplicationController::class, 'draft'])->name('application.draft');
    Route::post('/applications/{application}/submit', [ApplicationController::class, 'submit'])->name('application.submit');
    Route::post('/applications/{application}/withdraw', [ApplicationController::class, 'withdraw'])->name('application.withdraw');
    Route::post('/applications/{application}/documents', [DocumentController::class, 'upload'])->middleware('throttle:uploads')->name('document.upload');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('document.delete');
    Route::post('/documents/{document}/retry', [DocumentController::class, 'retry'])->middleware('throttle:uploads')->name('document.retry');
    Route::post('/applications/{application}/assessment/start', [ApplicationController::class, 'start'])->name('assessment.start');
    Route::get('/applications/{application}/assessment', [ApplicationController::class, 'assessment'])->name('assessment.show');
    Route::post('/applications/{application}/assessment', [ApplicationController::class, 'answer'])->name('assessment.answer');
});
Route::middleware(['auth:web', 'verified', 'role:organizer'])->prefix('organizer')->name('organizer.')->group(function () {
    Route::get('/transactions', [OrganizerController::class, 'transactions'])->name('transactions');
    Route::get('/events', [OrganizerController::class, 'index'])->name('events');
    Route::get('/events/create', [OrganizerController::class, 'create'])->name('create');
    Route::post('/events', [OrganizerController::class, 'store'])->name('store');
    Route::get('/events/{event}', [OrganizerController::class, 'show'])->name('event');
    Route::get('/events/{event}/edit', [OrganizerController::class, 'edit'])->name('edit');
    Route::put('/events/{event}', [OrganizerController::class, 'update'])->name('update');
    Route::post('/events/{event}/positions', [OrganizerController::class, 'addPosition'])->name('position');
    Route::delete('/events/{event}/positions/{position}', [OrganizerController::class, 'removePosition'])->name('position.delete');
    Route::post('/events/{event}/submit', [OrganizerController::class, 'submit'])->name('submit');
    Route::get('/events/{event}/applicants', [OrganizerController::class, 'applicants'])->name('applicants');
    Route::post('/applications/{application}/decision', [OrganizerController::class, 'decision'])->name('decision');
    Route::get('/events/{event}/attendance', [OrganizerController::class, 'attendance'])->name('attendance');
    Route::post('/events/{event}/attendance', [OrganizerController::class, 'saveAttendance'])->name('attendance.save');
    Route::post('/events/{event}/complete', [OrganizerController::class, 'complete'])->name('complete');
    Route::post('/events/{event}/pay', [OrganizerController::class, 'pay'])->middleware('throttle:5,1')->name('pay');
    Route::post('/orders/{order}/reconcile', [OrganizerController::class, 'reconcile'])->middleware('throttle:10,1')->name('reconcile');
});
Route::middleware('admin.panel')->prefix('admin')->name('admin.')->group(function () {
    Route::post('/logout', [AuthController::class, 'adminLogout'])->name('logout');
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/account', [AdminController::class, 'account'])->name('account');
    Route::post('/account', [AdminController::class, 'saveAccount'])->name('account.save');
    Route::get('/events', [AdminController::class, 'events'])->name('events');
    Route::post('/events/{event}/review', [AdminController::class, 'review'])->name('review');
    Route::post('/events/{event}/publication', [AdminController::class, 'publication'])->name('publication');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::post('/users/{user}/toggle', [AdminController::class, 'toggle'])->name('toggle');
    Route::get('/packages', [AdminController::class, 'packages'])->name('packages');
    Route::post('/packages/{package}', [AdminController::class, 'savePackage'])->name('package.save');
    Route::post('/skills', [AdminController::class, 'addSkill'])->name('skill');
    Route::get('/logs', [AdminController::class, 'logs'])->name('logs');
    Route::get('/transactions', [AdminController::class, 'transactions'])->name('transactions');
    Route::post('/orders/{order}/reconcile', [AdminController::class, 'reconcile'])->middleware('throttle:10,1')->name('reconcile');
    Route::get('/master', [AdminController::class, 'master'])->name('master');
    Route::post('/master', [AdminController::class, 'saveMaster'])->name('master.save');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [AdminController::class, 'saveSettings'])->name('settings.save');
});
