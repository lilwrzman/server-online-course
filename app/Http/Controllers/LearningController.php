<?php

namespace App\Http\Controllers;

use App\Models\AssessmentHistory;
use App\Models\AssessmentQuestion;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseAccess;
use App\Models\CourseItem;
use App\Models\Notification;
use App\Models\StudentProgress;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LearningController extends Controller
{
    public function learning(Request $request)
    {
        $user = Auth::user();
        $item_id = (int) $request->input('item_id');
        $next_item = (bool) $request->input('next_item');
        $course_id = (int) $request->input('course');
        var_dump($item_id);
        var_dump($request->input('course'));
        var_dump($next_item);

        if($user->role !== 'Student'){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $course = Course::select('id', 'title', 'description', 'slug')
            ->findOrFail($course_id);

        if(!$user->hasAccessToCourse($course->id)){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $itemIds = CourseItem::where('course_id', $course->id)->pluck('id');
        $completed_items = StudentProgress::whereIn('item_id', $itemIds)
            ->where('user_id', $user->id)
            ->with(['item:id,slug,order'])
            ->get(['id', 'user_id', 'item_id', 'is_done']);

        if($item_id) {
            $item = CourseItem::where('id', $item_id)
                ->with(['questions:id,item_id'])
                ->where('course_id', $course->id)
                ->firstOrFail();
        }else {
            if ($completed_items->isEmpty()) {
                $item = CourseItem::where('course_id', $course->id)
                    ->with(['questions:id,item_id'])
                    ->where('order', 1)
                    ->first();
            } else {
                if ($next_item) {
                    $latest_completed_item = $completed_items->max('item.order');
                    $next_item = CourseItem::where('course_id', $course->id)
                        ->with(['questions:id,item_id'])
                        ->where('order', '>', $latest_completed_item)
                        ->orderBy('order')
                        ->first();

                    if (!$next_item) {
                        $next_item = CourseItem::where('course_id', $course->id)
                            ->with(['questions:id,item_id'])
                            ->where('type', 'exam')
                            ->first();
                    }

                    return response()->json([
                        'status' => true,
                        'course' => $course,
                        'completed_items' => $completed_items,
                        'item' => $next_item
                    ], 200);
                } else{
                    $latest_completed_item = $completed_items->max('item.order');
                    $item = $completed_items->where('item.order', $latest_completed_item)->first()->item->id;
                    $item = CourseItem::with(['questions:id,item_id'])->findOrFail($item);
                }
            }
        }

        return response()->json([
            'status' => true,
            'course' => $course,
            'completed_items' => $completed_items,
            'item' => $item
        ], 200);
    }

    public function updateProgress(Request $request)
    {
        $user = Auth::user();

        if($user->role !== 'Student'){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $item_id = $request->input('item_id');

        $progress = StudentProgress::where('item_id', $item_id)
                        ->where('user_id', $user->id)
                        ->first();

        if(!$progress){
            $new_progress = StudentProgress::create([
                'user_id' => $user->id,
                'item_id' => $item_id,
                'is_done' => true
            ]);

            if(!$new_progress){
                return response()->json(['error' => 'Gagal menambahkan progress!'], 500);
            }

            return response()->json(['status' => true, 'message' => 'Berhasil update progress!'], 200);
        }

        return response()->json(['status' => true, 'message' => 'Progress sudah ada!'], 200);
    }

    public function getAssessment(Request $request)
    {
        $user = Auth::user();
        $item_id = $request->input('item_id');
        $show_answer = $request->input('show_answer');

        if($user->role != 'Student'){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'required|int',
            'show_answer' => 'required'
        ]);

        if($validator->fails()){
            return response()->json(['error_validator' => $validator->errors()], 400);
        }

        $what_to_select = $show_answer ? ['id', 'item_id', 'question', 'options', 'correct_answer'] : ['id', 'item_id', 'question', 'options'];
        $item = CourseItem::whereIn('type', ['Quiz', 'Exam'])
            ->with(["questions" => function($query) use ($what_to_select){
                $query->select($what_to_select);
            }])
            ->findOrFail($item_id);

        return response()->json(['status' => true, 'data' => $item], 200);
    }

    public function submitAssessment(Request $request)
    {
        $user = Auth::user();
        $item_id = $request->input('item_id');
        $answers = $request->input('answers');

        if($user->role != 'Student'){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'required|int',
            'answers' => 'required|array'
        ]);

        if($validator->fails()){
            return response()->json(['error_validator' => $validator->errors()], 400);
        }

        $item = CourseItem::findOrFail($item_id);
        if(!in_array($item->type, ['Quiz', 'Exam'])) {
            return response()->json(['error' => 'Invalid item type'], 400);
        }

        $questions = AssessmentQuestion::where('item_id', $item_id)->get();

        $score = 0;
        $total_questions = $questions->count();

        foreach ($answers as $answer) {
            $question = $questions->where('id', $answer['question_id'])->first();

            if ($question && $question->correct_answer == $answer['answer']) {
                $score++;
            }
        }

        $percentage_score = ($score / $total_questions) * 100;

        $passing_score = $item->info['passing_score'] ?? 0;
        $isPass = $percentage_score >= $passing_score;

        $assessmentHistory = AssessmentHistory::create([
            'user_id' => $user->id,
            'item_id' => $item_id,
            'answer' => $answers,
            'score' => $percentage_score,
            'is_pass' => $isPass
        ]);

        if($item->type == 'Exam' && $isPass){
            $new_progress = StudentProgress::create([
                'user_id' => $user->id,
                'item_id' => $item_id,
                'is_done' => true
            ]);

            $complete = CourseAccess::where('user_id', $user->id)
                            ->where('course_id', $item->course_id)
                            ->update(['status' => 'Completed']);

            $course = Course::findOrFail($item->course_id);
            $completionDate = now();
            $certificateService = new CertificateService();
            $certificatePath = $certificateService->generateCertificate($user->info['fullname'], $course->title, $completionDate);
            $certificate = Certificate::create([
                "course_id" => $item->course_id,
                "student_id" => $user->id,
                "completion_date" => $completionDate,
                "certificate" => $certificatePath
            ]);

            $notification_student = Notification::create([
                'title' => 'Pembelajaran Selesai',
                'message' => 'Selamat, anda telah berhasil menyelesaikan pembelajaran materi ' . $course->title . '! Kami sangat mengharapkan umpan balik dari anda! Beri umpan balik dan dapatkan sertifikat sekarang!',
                'info' => [
                    "target" => ["student"],
                    "menu" => "my-courses",
                    "course_id" => $course->id
                ]
            ]);

            $notification_teacher = Notification::create([
                'title' => 'Pembelajaran Selesai',
                'message' => 'Akun dengan username ' . $user->username . ' telah menyelesaikan pembelajaran materi ' . $course->title . ' anda! Cek halaman progres sekarang!',
                'info' => [
                    "target" => ["teacher"],
                    "menu" => "progress",
                    "course_id" => $course->id
                ]
            ]);

            $teacher = User::findOrFail($course->teacher_id);
            $notification_student->assignToUsers($user);
            $notification_teacher->assignToUsers($teacher);
        }

        $result = "";
        if($isPass){
            $result = "Selamat, Anda lulus kuis dengan perolehan nilai: {$percentage_score}";
        }else{
            $result = "Mohon maaf, Anda masih gagal kuis dengan perolehan nilai: {$percentage_score}";
        }

        return response()->json(['status' => true, 'message' => $result, 'title' => $isPass ? 'Selamat!' : 'Oops!'], 200);
    }

    public function assessmentHistory($id)
    {
        $user = Auth::user();

        if($user->role != 'Student'){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $histories = AssessmentHistory::where('user_id', $user->id)
                        ->where('item_id', $id)
                        ->get();

        return response()->json(['status' => true, 'data' => $histories], 200);
    }

    public function detailHistory($id)
    {
        $user = Auth::user();

        if($user->role != 'Student'){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $history = AssessmentHistory::findOrFail($id);

        return response()->json(['status' => true, 'data' => $history], 200);
    }

    public function getProgress(Request $request)
    {
        $user = Auth::user();

        if($user->role == 'Student'){
            $courses = CourseAccess::where('user_id', $user->id)
                ->with([
                    'course:id,title,thumbnail,slug',
                    'course.items:id,course_id,slug'
                ])->get(['id', 'user_id', 'course_id', 'status', 'type', 'access_date']);

            $itemIds = CourseItem::whereIn('course_id', $courses->pluck('course_id'))->pluck('id');

            $completed_items = StudentProgress::whereIn('item_id', $itemIds)
                ->where('user_id', $user->id)
                ->get()
                ->groupBy('item.course_id')
                ->map(function ($progresses) {
                    return $progresses->count();
                });

            $latest_progress = StudentProgress::whereIn('item_id', $itemIds)
                ->where('user_id', $user->id)
                ->select('item_id', 'created_at')
                ->orderBy('created_at', 'desc')
                ->with('item.course:id,title')
                ->get()
                ->groupBy('item.course_id')
                ->map(function ($progresses) {
                    return $progresses->first();
                });

            $courses->each(function ($course) use ($latest_progress, $completed_items) {
                $course->latest_progress = $latest_progress->get($course->course_id);
                $course->completed_items = $completed_items->get($course->course_id, 0);
                $course->total_items = $course->course->items->count();
            });

            return response()->json(['status' => true, 'data' => $courses], 200);
        }
    }

    public function getStudentListProgress(Request $request)
    {
        $user = Auth::user();

        if($user->role != 'Corporate Admin'){
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $students = $user->corporateStudents()->with(['courseAccesses'])->get();
        $result = $students->map(function($student) {
            $accessedCourses = $student->courseAccesses->count();
            $completedCourses = $student->courseAccesses->where('status', "Completed")->count();

            return [
                'id' => $student->id,
                'info' => $student->info,
                'accessed_courses' => $accessedCourses,
                'completed_courses' => $completedCourses,
            ];
        });

        return response()->json(['status' => true, 'data' => $result], 200);
    }

    public function getStudentProgressDetail($id)
    {
        $user = Auth::user();

        if($user->role != 'Corporate Admin'){
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $student = User::findOrFail($id);

        $courses = CourseAccess::where('user_id', $id)
            ->with([
                'course:id,title,thumbnail,slug',
                'course.items:id,course_id,slug'
            ])->get(['id', 'user_id', 'course_id', 'status', 'type', 'access_date']);

        $itemIds = CourseItem::whereIn('course_id', $courses->pluck('course_id'))->pluck('id');

        $completed_items = StudentProgress::whereIn('item_id', $itemIds)
            ->where('user_id', $id)
            ->get()
            ->groupBy('item.course_id')
            ->map(function ($progresses) {
                return $progresses->count();
            });

        $latest_progress = StudentProgress::whereIn('item_id', $itemIds)
            ->where('user_id', $id)
            ->select('item_id', 'created_at')
            ->orderBy('created_at', 'desc')
            ->with('item.course:id,title')
            ->get()
            ->groupBy('item.course_id')
            ->map(function ($progresses) {
                return $progresses->first();
            });

        $courses->each(function ($course) use ($latest_progress, $completed_items) {
            $course->latest_progress = $latest_progress->get($course->course_id);
            $course->completed_items = $completed_items->get($course->course_id, 0);
            $course->total_items = $course->course->items->count();
        });

        return response()->json(['status' => true, 'data' => [
            "courses" => $courses,
            "student" => $student
        ]], 200);
    }

    public function getCourseProgress(Request $request)
    {
        $user = Auth::user();

        if($user->role !== 'Superadmin' && $user->role !== 'Teacher'){
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        if($user->role == "Superadmin"){
            $courses = Course::all(['id', 'title']);
        }else{
            $courses = Course::where('teacher_id', $user->id)->get(['id', 'title']);
        }

        foreach($courses as $course){
            $course->count_student = $course->courseAccesses()->count();
            $course->count_student_complete = $course->courseAccesses()->where('status', 'Completed')->count();
        }

        return response()->json(['status' => true, 'data' => $courses], 200);
    }

    public function getCourseProgressDetail($id)
    {
        $user = Auth::user();

        if($user->role !== 'Superadmin' && $user->role !== 'Teacher'){
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $course = Course::select(['id', 'title', 'items'])->with([
            'courseAccesses:id,course_id,user_id,status,type',
            'courseAccesses.student:id,username,email,info',
        ])->findOrFail($id);

        foreach($course->courseAccesses as $access){
            $items_id = CourseItem::where('course_id', $course->id)->get(['id']);
            $access->student->progress = StudentProgress::where('user_id', $access->student->id)
                                            ->whereIn('item_id', $items_id->pluck('id'))
                                            ->count();
        }

        return response()->json(['status' => true, 'data' => $course], 200);
    }
}
