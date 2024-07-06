<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseAccessController;
use App\Http\Controllers\CourseBundleController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseItemController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\MyCoursesController;
use App\Http\Controllers\RedeemCodeController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
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


// Endpoint: Account Management 🟩
Route::group([
    "middleware" => ['auth:api']
], function(){
    Route::get('/account/get', [UserController::class, 'index']); // 🟩
    Route::get('/account/{id}', [UserController::class, 'detail']); // 🟩
    Route::post('/account/add', [UserController::class, 'create']); // 🟩
    Route::post('/account/update', [UserController::class, 'update']); // 🟩
    Route::post('/account/update/avatar', [UserController::class, 'updateAvatar']); // 🟩
    // Route::post('/account/delete', [UserController::class, 'delete']); // 🟩

    // TO DO
    Route::post('/account/{id}/change-status', [UserController::class, 'changeStatus']);
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
    Route::post('/learning-path/delete', [LearningPathController::class, 'destroy']); // 🟩
    Route::post('/learning-path/remove-course', [LearningPathController::class, 'removeCourse']); // 🟩
});
// End of Endpoint: Learning Paths


// Endpoint: Courses 🟩
Route::get('/course/get', [CourseController::class, 'index']); // 🟩
Route::get('/course/no-learning-path/get', [CourseController::class, 'loneCourse']); // 🟩
Route::get('/course/get/{id}', [CourseController::class, 'show']); // 🟩

Route::group([
    "middleware" => ['auth:api']
], function(){
    Route::post('/course/add', [CourseController::class, 'store']); // 🟩
    Route::post('/course/update', [CourseController::class, 'update']); // 🟩
    Route::post('/course/delete', [CourseController::class, 'destroy']); // 🟩
    Route::post('/course/{id}/remove-teacher', [CourseController::class, 'removeTeacher']); // 🟩
    Route::post('/course/reorder', [CourseController::class, 'reorderCourse']);
});
// End of Endpoint: Courses


// Endpoint: Course Items
Route::get('/course/{id}/items/get', [CourseItemController::class, 'index']); // Get all item in Course by Course's ID
Route::get('/video/playlist/{uniqid}/{playlist}', [CourseItemController::class, 'playlist'])->name('video.playlist');

Route::group([
    "middleware" => ['auth:api']
], function(){
    Route::get('/video/key/{uniqid}/{key}', [CourseItemController::class, 'key'])->name('video.key');
    Route::get('/items/get/{id}', [CourseItemController::class, 'show']); // Get item's detail in Courses by Course's ID
    Route::post('/items/reorder', [CourseItemController::class, 'reorderItems']); // Reorder the items inside the Course
    Route::post('/course/{id}/assessment/add', [CourseItemController::class, 'storeAssessment']);  // Add new Assessment (Quiz or Exam) in Course by it's ID
    Route::post('/course/{id}/video/add', [CourseItemController::class, 'storeVideo']); // Add new Video in Course by it's ID
    Route::post('/assessment/{id}/update', [CourseItemController::class, 'updateAssessment']); // Update Assessment by Item's ID
    Route::post('/assessment/delete', [CourseItemController::class, 'deleteAssessment']); // Delete Quiz or Exam and it's question from database by it's ID
    Route::post('/video/delete', [CourseItemController::class, 'deleteVideo']); // Delete Video and it's playlist from database and storage by it's ID
});


// Endpoint: Bundle 🟩
Route::group([
    "middleware" => ['auth:api']
], function(){
    Route::get('/bundle/get', [CourseBundleController::class, 'index']);
    Route::get('/bundle/get/{id}', [CourseBundleController::class, 'show']);
    Route::post('/bundle/add', [CourseBundleController::class, 'store']); // 🟩
    Route::post('/bundle/update', [CourseBundleController::class, 'update']); // 🟩
    Route::post('/bundle/{id}/change-access', [CourseBundleController::class, 'changeAccess']);
    Route::post('/redeem/get', [RedeemCodeController::class, 'show']);
    Route::post('/redeem', [RedeemCodeController::class, 'redeem']);
});
// End of Endpoint: Bundle


// Endpoint: Dashboard
Route::group([
    "middleware" => ['auth:api']
], function(){
    Route::get('/dashboard', [DashboardController::class, 'dashboard']);
});
// End of Endpoint: Dashboard


// Endpoint: Transaction
Route::group([
    "middleware" => ['auth:api']
], function(){
    Route::get('/transaction/export', [TransactionController::class, 'export']);
    Route::get('/transaction/histories', [TransactionController::class, 'transactionHistory']);
    Route::post('/checkout/process', [TransactionController::class, 'process']);
    Route::post('/checkout/success/{id}', [TransactionController::class, 'success']);
    Route::post('/checkout/pending/{id}', [TransactionController::class, 'pending']);
});
// End of Endpoint: Transaction


// Endpoint: Student
Route::group([
    "middleware" => ['auth:api']
], function(){
    Route::get('/student/my-courses', [CourseAccessController::class, 'myCourses']);
    Route::post("/student/learn", [LearningController::class, 'learning']);
    Route::post("/student/assessment", [LearningController::class, 'getAssessment']);
    Route::post("/student/assessment/submit", [LearningController::class, 'submitAssessment']);
    Route::post("/student/assessment/history", [LearningController::class, 'assessmentHistory']);
    Route::post("/student/assessment/history/{id}", [LearningController::class, 'detailHistory']);
});
// End of Endpoint: Student


// Endpoint: Student List
Route::group([
    "middleware" => ['auth:api']
], function(){
    Route::get("/teacher/student", [UserController::class, 'teacherStudent']);
    Route::get("/corporate/student", [UserController::class, 'corporateStudentList']);
    Route::post("/corporate/student/check", [UserController::class, 'checkByEmail']);
    Route::post("/corporate/student/add", [UserController::class, 'addToCorporate']);
});
// End of Endpoint: Student List


// Endpoint: Progress
Route::group([
    "middleware" => ['auth:api'],
], function(){
    Route::post("/sudent/progress/update", [LearningController::class, 'updateProgress']);
    Route::get("/sudent/progress", [LearningController::class, 'getProgress']);
    Route::get("/corporate/progress", [LearningController::class, 'getStudentListProgress']);
    Route::get("/corporate/progress/{id}", [LearningController::class, 'getStudentProgressDetail']);
    Route::get("/course/progress", [LearningController::class, 'getCourseProgress']);
    Route::get("/course/progress/detail/{id}", [LearningController::class, 'getCourseProgressDetail']);
});
// End of Endpoint: Progress

// Endpoint: Events ⬜
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);
Route::post('/events', [EventController::class, 'store']);
Route::put('/events/{id}', [EventController::class, 'update']);
Route::delete('/events/{id}', [EventController::class, 'destroy']);
