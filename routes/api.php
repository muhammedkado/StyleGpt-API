<?php

use App\Http\Controllers\exploresController;
use App\Http\Controllers\GenerateImageController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::post('generateImage', [GenerateImageController::class, 'create']);

Route::get('explore', [exploresController::class, 'explore']);

Route::get('admin/publish', [exploresController::class, 'adminPublish']);

Route::get('admin/explore', [exploresController::class, 'adminExplore']);

Route::post('publish', [exploresController::class, 'publish']);

Route::post('createUser', [UserController::class, 'create']);

Route::get('/user/{uid}', [UserController::class, 'getUserByUid']);

Route::get('/user/{uid}/images', [UserController::class, 'getImageByUid']);
