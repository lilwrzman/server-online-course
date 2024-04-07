<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseItemController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

/*
    Done: 🟩
    On-Progress: 🟨
    Pending: ⬜
*/


// Endpoint: Authentication 🟩
Route::post('/register', [AuthController::class, 'register']); // 🟩
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify']); // 🟩
Route::post('/login', [AuthController::class, 'login']); // 🟩

Route::group([
    "middleware" => ['auth:api']
], function(){
    Route::get('/profile', [UserController::class, 'profile']); // 🟩
    Route::get('/logout', [AuthController::class, 'logout']); // 🟩
});
// End of Endpoint: Authentication


// Endpoint: Account Management 🟨
Route::group([
    "middleware" => ['auth:api']
], function(){
    Route::get('/account/get', [UserController::class, 'index']); // 🟩
    Route::get('/account/{id}', [UserController::class, 'detail']); // 🟩
    Route::post('/account/add', [UserController::class, 'create']); // 🟨
    Route::post('/account/update', [UserController::class, 'update']); // 🟨
    Route::post('/account/delete', [UserController::class, 'delete']); // 🟨
});
// End of Endpoint: Account Management


// Endpoint: Learning Paths 🟩
Route::get('/learning-path/get', [LearningPathController::class, 'index']); // 🟩
Route::get('/learning-path/get/{slug}', [LearningPathController::class, 'show']); // 🟩

Route::group([
    "middleware" => ['auth:api']
], function(){
    Route::post('/learning-path/add', [LearningPathController::class, 'store']); // 🟩
    Route::post('/learning-path/update', [LearningPathController::class, 'update']); // 🟩
    Route::post('/learning-path/delete', [LearningPathController::class, 'destroy']); //🟩
});
// End of Endpoint: Learning Paths


// Endpoint: Courses ⬜
Route::get('/course/get', [CourseController::class, 'index']);
Route::get('/course/get/slug', [CourseController::class, 'show']);

Route::group([
    "middleware" => ['auth:api']
], function(){
    Route::post('/course/add', [CourseController::class, 'store']);
    Route::post('/course/update', [CourseController::class, 'update']);
    Route::delete('/course/delete', [CourseController::class, 'destroy']);
});
// End of Endpoint: Courses


// Endpoint: Course Items ⬜
Route::post('/items', [CourseItemController::class, 'store']);

// Endpoint: Events ⬜
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);
Route::post('/events', [EventController::class, 'store']);
Route::put('/events/{id}', [EventController::class, 'update']);
Route::delete('/events/{id}', [EventController::class, 'destroy']);
