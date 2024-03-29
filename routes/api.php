<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseItemController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LearningPathController;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Endpoint: Authentication
Route::post('/register', [AuthController::class, 'register']);
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify']);
Route::post('/login', [AuthController::class, 'login']);

Route::group([
    "middleware" => ['auth:api']
], function(){
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::get('/logout', [AuthController::class, 'logout']);
});


// Learning Paths
Route::get('/learning-paths', [LearningPathController::class, 'index']);
Route::get('/learning-paths/{id}', [LearningPathController::class, 'show']);
Route::post('/learning-paths', [LearningPathController::class, 'store']);
Route::post('/learning-paths/{id}', [LearningPathController::class, 'update']);
Route::delete('/learning-paths/{id}', [LearningPathController::class, 'destroy']);

// Courses
Route::get('/courses/published', [CourseController::class, 'published']);
Route::get('/courses/all', [CourseController::class, 'index']);
Route::get('/courses/{id}', [CourseController::class, 'show']);
Route::post('/courses', [CourseController::class, 'store']);
Route::post('/courses/{id}', [CourseController::class, 'update']);
Route::delete('/courses/{id}', [CourseController::class, 'destroy']);

// Course Items
Route::post('/items', [CourseItemController::class, 'store']);

// Events
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);
Route::post('/events', [EventController::class, 'store']);
Route::put('/events/{id}', [EventController::class, 'update']);
Route::delete('/events/{id}', [EventController::class, 'destroy']);
