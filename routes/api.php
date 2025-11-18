<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BlogController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/blogs', [BlogController::class, 'getBlogs']);
    Route::post('/blogs', [BlogController::class, 'addBlogs']);
    Route::post('/blogs/{id}/update', [BlogController::class, 'updateBlogs']);
    Route::delete('/blogs/{id}', [BlogController::class, 'deleteBlog']);
    Route::post('/like-blog', [BlogController::class, 'likeBlogs']);
});
