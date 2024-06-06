<?php

use App\Http\Controllers\exploresController;
use App\Http\Controllers\GenerateImageController;
use App\Http\Controllers\paymentController;
use App\Http\Controllers\SearchProductController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route to generate an image
Route::post('generateImage', [GenerateImageController::class, 'create']);

// Route to explore random images
Route::get('explore', [exploresController::class, 'explore']);

// Route to publish images by admin
Route::get('admin/publish', [exploresController::class, 'adminPublish']);

// Route to explore images by admin
Route::get('admin/explore', [exploresController::class, 'adminExplore']);

// Route to publish images
Route::post('publish', [exploresController::class, 'publish']);

// Route to create a user
Route::post('createUser', [UserController::class, 'create']);

// Route to get user by UID
Route::get('/user/{uid}', [UserController::class, 'getUserByUid']);

// Route to get images by UID
Route::get('/user/{uid}/images', [UserController::class, 'getImageByUid']);

// Route to search products
Route::post('searchProduct', [SearchProductController::class, 'searchProduct']);
