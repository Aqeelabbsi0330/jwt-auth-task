<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JwtController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::post('/register', [JwtController::class, 'register']);
Route::post('/login', [JwtController::class, 'login']);
Route::post('/logout', [JwtController::class, 'logout']);
Route::post('/refresh-token', [AuthController::class, 'refreshToken']);

Route::middleware(['jwt'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'index']);
});
