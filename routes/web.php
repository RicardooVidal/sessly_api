<?php

use App\Http\Controllers\GoogleCalendarController;
use App\Http\Middleware\CheckSchema;
use Illuminate\Support\Facades\Route;

Route::get('/telescope-access/{ticket}', [\App\Http\Controllers\TelescopeAccessController::class, 'show'])
    ->where('ticket', '[A-Za-z0-9]{64}')
    ->middleware('throttle:10,1')
    ->name('telescope.access');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/session/set', function () {
    session(['teste' => 'funcionou']);

    return 'Sessão salva';
});

Route::get('/session/get', function () {
    dd(session('teste'));
});


Route::get(
    '/auth/google',
    [GoogleCalendarController::class, 'redirect']
);

Route::get(
    '/auth/google/callback',
    [GoogleCalendarController::class, 'callback']
);

Route::get(
    '/test/google-meet',
    [GoogleCalendarController::class, 'testMeeting']
);
