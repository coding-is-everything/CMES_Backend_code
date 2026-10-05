<?php

use Illuminate\Support\Facades\Route;

Route::get('/users', function () {
    return response()->json([
        'success' => true,
        'data' => [
            [
                'id' => 1,
                'name' => 'Abhishek'
            ],
            [
                'id' => 2,
                'name' => 'Rahul'
            ]
        ]
    ]);
});