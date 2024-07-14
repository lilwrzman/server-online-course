<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CourseAccessController;
use App\Http\Controllers\CourseBundleController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseItemController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\NotificationController;
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
    Route::post("/password-reset", [UserController::class, 'passwordReset']);
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
Route::get('/video/playlist/{course_id}/{uniqid}/{playlist}', [CourseItemController::class, 'playlist'])->name('video.playlist');

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


// Endpoint: Feedback
Route::get("/course/{id}/feedback", [FeedbackController::class, 'courseFeedback']);
Route::get("/feedback/get", [FeedbackController::class, 'index']);

Route::group([
    "middleware" => ['auth:api']
], function(){
    Route::get("/course/{id}/student/feedback", [FeedbackController::class, 'studentFeedback']);
    Route::post("/course/feedback/post", [FeedbackController::class, 'postFeedback']);
});
// End of Endpoint: Feedback


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


// Endpoint: Discussion
Route::group([
    "middleware" => ['auth:api'],
], function(){
    Route::get("/course/{id}/discussion", [DiscussionController::class, 'courseDiscussion']);
    Route::post("/discussion/post", [DiscussionController::class, 'postDiscussion']);
});
// End of Endpoint: Discussion

// Endpoint: Events
Route::get('/events/get', [EventController::class, 'index']);
Route::get('/event/get/{id}', [EventController::class, 'show']);

Route::group([
    "middleware" => ['auth:api'],
], function(){
    Route::post('/event/add', [EventController::class, 'store']);
    Route::post('/event/update', [EventController::class, 'update']);
    Route::post('/event/change-thumbnail', [EventController::class, 'changeThumbnail']);
    Route::post('/event/delete', [EventController::class, 'destroy']);
});
// End of Endpoint: Events

// Endpoint: Articles
Route::get('/articles/get', [ArticleController::class, 'index']);
Route::get('/article/get/{id}', [ArticleController::class, 'show']);

Route::group([
    "middleware" => ['auth:api'],
], function(){
    Route::post('/article/add', [ArticleController::class, 'store']);
    Route::post('/article/update', [ArticleController::class, 'update']);
    Route::post('/article/change-thumbnail', [ArticleController::class, 'changeThumbnail']);
    Route::post('/article/delete', [ArticleController::class, 'destroy']);
});
// End of Endpoint: Articles


// Endpoint: Certificate
Route::group([
    "middleware" => ['auth:api'],
], function(){
    Route::get('/certificate/{course_id}', [CertificateController::class, 'get']);
});
// End of Endpoint: Certificate


// Endpoint: Notification
Route::group([
    "middleware" => ['auth:api'],
], function(){
    Route::get("/notification/get", [NotificationController::class, 'index']);
    Route::post("/notification/update", [NotificationController::class, 'updateSeen']);
});
// End of Endpoint: Notification
