<?php

return [
    'appointments' => [
        'enabled' => env('CREATE_APPOINTMENTS', true),
        'queue' => 'create-appointments',
        'batch' => 100,
    ],
];
