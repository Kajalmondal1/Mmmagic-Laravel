<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
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


Route::post('register',[AuthController::class,'register']);
Route::post('login',[AuthController::class,'login']);
Route::get('profile',[AuthController::class,'getDetails'])->middleware('jwtVerify');
Route::post('createblog',[BlogController::class,'createBlog'])->middleware('jwtVerify');
Route::get('get-all-blog',[BlogController::class,'getAllBlog'])->middleware('jwtVerify');
Route::delete('delete-blog/{BlogID}',[BlogController::class,'deleteBlog'])->middleware('jwtVerify');
