<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\WaitListController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Middleware\CheckSchema;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get(
    '/auth/google/callback',
    [GoogleCalendarController::class, 'callback']
);

Route::middleware('auth:sanctum')->get(
    '/google/connect',
    [GoogleCalendarController::class, 'connect']
);

Route::middleware('auth:sanctum')->get(
    '/google/testing',
    [GoogleCalendarController::class, 'test']
);

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')
    ->middleware('auth:sanctum');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::get('/companies', [AuthController::class, 'companies'])
    ->whereUuid('user')
    ->name('companies')
    ->middleware('auth:sanctum');

    Route::middleware([CheckSchema::class, 'auth:sanctum'])->group(function () {
        Route::resource('patient', PatientController::class)->except(['update']);
        Route::group(['prefix' => 'patient', 'as' => 'patient.'], function () {
            Route::get('/select/get', [PatientController::class, 'select'])->name('select');
            Route::put('/', [PatientController::class, 'update'])->name('update');
        });

        Route::resources(['wait-list' => WaitListController::class]);
        Route::resource('contract', ContractController::class)->except(['update']);
//        Route::put('contract', [ContractController::class, 'update'])->name('contract.update');

        Route::resource('appointment', AppointmentController::class)->except(['update']);
        Route::put('appointment', [AppointmentController::class, 'update'])->name('appointment.update');

        Route::get('/medical-record/get-by-patient/{uuid}', [MedicalRecordController::class, 'getMedicalRecord'])
            ->whereUuid('uuid')
            ->name('medical-record.get-by-patient');

        Route::post('/medical-record/import', [MedicalRecordController::class, 'importMedicalRecord'])
            ->name('medical-record.import');

        Route::resources([
            'payment' => PaymentController::class
        ]);
});
