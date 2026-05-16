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
use App\Http\Controllers\BackupController;

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
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('can.permission:dashboard,view')->name('dashboard');

    // Buildings
    Route::middleware('can.permission:buildings,view')->group(function () {
        Route::get('/buildings', [BuildingController::class, 'index'])->name('buildings.index');
        Route::get('/buildings/{building}', [BuildingController::class, 'show'])->whereNumber('building')->name('buildings.show');
    });
    Route::middleware('can.permission:buildings,create')->group(function () {
        Route::get('/buildings/create', [BuildingController::class, 'create'])->name('buildings.create');
        Route::post('/buildings', [BuildingController::class, 'store'])->name('buildings.store');
    });
    Route::middleware('can.permission:buildings,edit')->group(function () {
        Route::get('/buildings/{building}/edit', [BuildingController::class, 'edit'])->name('buildings.edit');
        Route::put('/buildings/{building}', [BuildingController::class, 'update'])->name('buildings.update');
    });
    Route::delete('/buildings/{building}', [BuildingController::class, 'destroy'])
        ->middleware('can.permission:buildings,delete')->name('buildings.destroy');

    // Floors
    Route::middleware('can.permission:floors,view')->group(function () {
        Route::get('/floors', [FloorController::class, 'index'])->name('floors.index');
        Route::get('/floors/{floor}', [FloorController::class, 'show'])->name('floors.show');
    });
    Route::post('/floors/{floor}/equipment-position', [FloorController::class, 'updatePositions'])
        ->middleware('can.permission:floors,edit')->name('floors.update-positions');

    // Equipment
    Route::middleware('can.permission:equipment,view')->group(function () {
        Route::get('/equipment', [EquipmentController::class, 'index'])->name('equipment.index');
        Route::get('/equipment/{equipment}', [EquipmentController::class, 'show'])->whereNumber('equipment')->name('equipment.show');
        Route::get('/api/equipment/search', [EquipmentController::class, 'search'])->name('api.equipment.search');
    });
    Route::middleware('can.permission:equipment,create')->group(function () {
        Route::get('/equipment/create', [EquipmentController::class, 'create'])->name('equipment.create');
        Route::post('/equipment', [EquipmentController::class, 'store'])->name('equipment.store');
    });
    Route::middleware('can.permission:equipment,edit')->group(function () {
        Route::get('/equipment/{equipment}/edit', [EquipmentController::class, 'edit'])->name('equipment.edit');
        Route::put('/equipment/{equipment}', [EquipmentController::class, 'update'])->name('equipment.update');
    });
    Route::delete('/equipment/{equipment}', [EquipmentController::class, 'destroy'])
        ->middleware('can.permission:equipment,delete')->name('equipment.destroy');

    // Alarms
    Route::middleware('can.permission:alarms,view')->group(function () {
        Route::get('/alarms', [AlarmController::class, 'index'])->name('alarms.index');
        Route::get('/alarms/{alarm}', [AlarmController::class, 'show'])->name('alarms.show');
    });
    Route::middleware('can.permission:alarms,edit')->group(function () {
        Route::post('/alarms/{alarm}/acknowledge', [AlarmController::class, 'acknowledge'])->name('alarms.acknowledge');
        Route::post('/alarms/{alarm}/resolve', [AlarmController::class, 'resolve'])->name('alarms.resolve');
        Route::post('/alarms/{alarm}/silence', [AlarmController::class, 'silence'])->name('alarms.silence');
        Route::post('/alarms/{alarm}/assign', [AlarmController::class, 'assign'])->name('alarms.assign');
    });

    // Energy
    Route::get('/energy', [EnergyController::class, 'index'])
        ->middleware('can.permission:energy,view')->name('energy.index');

    // Schedules
    Route::middleware('can.permission:schedules,view')->group(function () {
        Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
        Route::get('/schedules/calendar', [ScheduleController::class, 'calendar'])->name('schedules.calendar');
        Route::get('/schedules/device-settings', [ScheduleController::class, 'deviceSettings'])->name('schedules.device-settings');
    });
    Route::middleware('can.permission:schedules,create')->group(function () {
        Route::get('/schedules/create', [ScheduleController::class, 'create'])->name('schedules.create');
        Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
    });
    Route::middleware('can.permission:schedules,edit')->group(function () {
        Route::get('/schedules/{schedule}/edit', [ScheduleController::class, 'edit'])->name('schedules.edit');
        Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
        Route::patch('/schedules/{schedule}/toggle', [ScheduleController::class, 'toggle'])->name('schedules.toggle');
    });
    Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])
        ->middleware('can.permission:schedules,delete')->name('schedules.destroy');

    // Reports
    Route::middleware('can.permission:reports,view')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}/download', [ReportController::class, 'download'])->name('reports.download');
    });
    Route::middleware('can.permission:reports,create')->group(function () {
        Route::post('/reports', [ReportController::class, 'generate'])->name('reports.store');
        Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
    });
    Route::delete('/reports/{report}', [ReportController::class, 'destroy'])
        ->middleware('can.permission:reports,delete')->name('reports.destroy');

    // Users
    Route::middleware('can.permission:users,view')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
    });
    Route::middleware('can.permission:users,create')->group(function () {
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
    });
    Route::middleware('can.permission:users,edit')->group(function () {
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    });
    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('can.permission:users,delete')->name('users.destroy');

    // Settings
    Route::middleware('can.permission:settings,view')->group(function () {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    });
    Route::middleware('can.permission:settings,edit')->group(function () {
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    });

    // Backup
    Route::middleware('can.permission:settings,edit')->group(function () {
        Route::post('/settings/backup-now', [BackupController::class, 'run'])->name('settings.backup-now');
        Route::get('/settings/backup/{filename}/download', [BackupController::class, 'download'])->name('settings.backup-download');
        Route::delete('/settings/backup/{filename}', [BackupController::class, 'destroy'])->name('settings.backup-destroy');
    });

    // Logs
    Route::get('/logs', [LogController::class, 'index'])
        ->middleware('can.permission:logs,view')->name('logs.index');
});
