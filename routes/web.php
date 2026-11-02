<?php

use App\Http\Controllers\GoogleCalendarController;
use App\Http\Middleware\CheckSchema;
use Illuminate\Support\Facades\Route;

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

