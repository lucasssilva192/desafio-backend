<?php

use App\Http\Controllers\UserController;
use Database\Factories\UserFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/users', [UserController::class, 'get_users']);
Route::post('/new_user', [UserController::class, 'add_user']);
Route::post('/user/{id}', [UserController::class, 'show_user']);
Route::delete('/user/{id}', [UserController::class, 'delete_user']);
//
Route::post('/movement', [UserController::class, 'new_movement']);
Route::get('/movements', [UserController::class, 'get_movements']);
Route::delete('/movement/{user_id}/{mov_id}', [UserController::class, 'delete_movement']);
Route::get('/csv_movements/{filter}', [UserController::class, 'csv_movements']);
