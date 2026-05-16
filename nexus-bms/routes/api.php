<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IoTController;

Route::prefix('api')->middleware('iot.token')->group(function () {
    Route::post('/iot/equipment/{code}/status', [IoTController::class, 'updateEquipmentStatus']);
    Route::post('/iot/meter/{name}/reading', [IoTController::class, 'pushMeterReading'])->where('name', '.*');
    Route::get('/dashboard/live', [IoTController::class, 'dashboardLive']);
});
