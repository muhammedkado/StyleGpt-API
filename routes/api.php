<?php

use App\Http\Controllers\GenerateImageController;
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

/*Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('test', function (){
    return 'hi';
});*/

Route::get('index', [GenerateImageController::class, 'index']);
Route::post('generateimage/create', [GenerateImageController::class, 'create']);
Route::get('show', [GenerateImageController::class, 'show']);
Route::get('delete', [GenerateImageController::class, 'destroy']);
