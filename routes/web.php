<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OpportunityController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', fn () => redirect()->route('login'));
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::middleware('role:admin,sales_executive')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/leads/datatable', [LeadController::class, 'datatable'])->name('leads.datatable');
        Route::post('/leads/import', [LeadController::class, 'import'])->name('leads.import');
        Route::resource('leads', LeadController::class)->except('destroy');
        Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->middleware('role:admin')->name('leads.destroy');

        Route::get('/follow-ups', [FollowUpController::class, 'index'])->name('followups.index');
        Route::post('/follow-ups', [FollowUpController::class, 'storeFromIndex'])->name('followups.store');
        Route::post('/follow-ups/import', [FollowUpController::class, 'import'])->name('followups.import');
        Route::post('/leads/{lead}/follow-ups', [FollowUpController::class, 'store'])->name('leads.followups.store');
        Route::patch('/follow-ups/{followUp}/status', [FollowUpController::class, 'updateStatus'])->name('followups.status');

        Route::get('/opportunities', [OpportunityController::class, 'index'])->name('opportunities.index');
        Route::post('/opportunities', [OpportunityController::class, 'store'])->name('opportunities.store');
        Route::post('/opportunities/import', [OpportunityController::class, 'import'])->name('opportunities.import');
        Route::patch('/opportunities/{opportunity}/stage', [OpportunityController::class, 'updateStage'])->name('opportunities.stage');

        Route::get('/notifications/poll', [NotificationController::class, 'poll'])->name('notifications.poll');
        Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::patch('/notifications/{notificationId}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('/notifications/broadcast', [NotificationController::class, 'broadcast'])->middleware('role:admin')->name('notifications.broadcast');
    });
});
