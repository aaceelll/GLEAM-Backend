<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;

Route::get('/storage/materi/{filename}', function ($filename) {
    // path benar
    $path = storage_path('app/public/materi/' . $filename);

    if (!File::exists($path)) {
        abort(404, 'File tidak ditemukan.');
    }

    $mime = File::mimeType($path) ?: 'application/pdf';
    return Response::make(File::get($path), 200, [
        'Content-Type' => $mime,
        'Access-Control-Allow-Origin' => '*', // biar CORS aman pas testing
    ]);
});

Route::get('/', function () {
    return view('welcome');
});
