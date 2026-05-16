<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\AlarmController;
use App\Http\Controllers\EnergyController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\LogController;

Route::get('/', fn() => redirect()->route('login'));

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Language switch (accessible to all)
Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('lang.switch');

// Protected routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Buildings
    Route::resource('buildings', BuildingController::class);

    // Floors
    Route::get('/floors', [FloorController::class, 'index'])->name('floors.index');
    Route::get('/floors/{floor}', [FloorController::class, 'show'])->name('floors.show');

    // Equipment
    Route::resource('equipment', EquipmentController::class);

    // Alarms
    Route::get('/alarms', [AlarmController::class, 'index'])->name('alarms.index');
    Route::get('/alarms/{alarm}', [AlarmController::class, 'show'])->name('alarms.show');
    Route::post('/alarms/{alarm}/acknowledge', [AlarmController::class, 'acknowledge'])->name('alarms.acknowledge');
    Route::post('/alarms/{alarm}/resolve', [AlarmController::class, 'resolve'])->name('alarms.resolve');
    Route::post('/alarms/{alarm}/silence', [AlarmController::class, 'silence'])->name('alarms.silence');
    Route::post('/alarms/{alarm}/assign', [AlarmController::class, 'assign'])->name('alarms.assign');

    // Energy
    Route::get('/energy', [EnergyController::class, 'index'])->name('energy.index');

    // Schedules
    Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/schedules/calendar', [ScheduleController::class, 'calendar'])->name('schedules.calendar');
    Route::get('/schedules/device-settings', [ScheduleController::class, 'deviceSettings'])->name('schedules.device-settings');
    Route::get('/schedules/create', [ScheduleController::class, 'create'])->name('schedules.create');
    Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
    Route::get('/schedules/{schedule}/edit', [ScheduleController::class, 'edit'])->name('schedules.edit');
    Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
    Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');
    Route::patch('/schedules/{schedule}/toggle', [ScheduleController::class, 'toggle'])->name('schedules.toggle');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports', [ReportController::class, 'generate'])->name('reports.store');
    Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');

    // Users
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');

    // API helpers
    Route::get('/api/equipment/search', [EquipmentController::class, 'search'])->name('api.equipment.search');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/backup-now', fn() => back()->with('success', 'Backup started successfully.'))->name('settings.backup-now');

    // Logs
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
});
