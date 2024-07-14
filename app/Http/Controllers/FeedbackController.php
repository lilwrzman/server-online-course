<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseFeedback;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
    public function index()
    {
        $feedbacks = CourseFeedback::with([
            'course:id,title',
            'user:id,avatar,info'
        ])->orderBy('created_at', 'desc')->get();

        return response()->json(['status' => true, 'data' => $feedbacks]);
    }

    public function courseFeedback($id)
    {
        $datas = Course::select('id', 'title')->with([
            'feedbacks' => function($query){
                $query->select('id', 'user_id', 'course_id', 'rating', 'review', 'created_at')
                    ->orderBy('created_at', 'desc');
            },
            'feedbacks.user:id,email,avatar,info',
        ])->findOrFail($id);

        return response()->json(['status' => true, 'data' => $datas], 200);
    }

    public function studentFeedback($id)
    {
        $user = Auth::user();

        if($user->role !== 'Student'){
            return response()->json(['error' => "Unauthorized."], 401);
        }

        $feedback = CourseFeedback::where('user_id', $user->id)
                        ->where('course_id', $id)
                        ->with(['user:id,email,info,avatar', 'course:id,title'])
                        ->first();

        return response()->json(['status' => true, 'data' => $feedback], 200);
    }

    public function postFeedback(Request $request)
    {
        $user = Auth::user();

        if($user->role !== 'Student'){
            return response()->json(['error' => "Unauthorized."], 401);
        }

        $validator = Validator::make($request->all(), [
            'course_id' => 'required|int',
            'rating' => 'required|int',
            'review' => 'required|string'
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()]);
        }

        if(CourseFeedback::where('user_id', $user->id)
            ->where('course_id', $request->input('course_id'))
            ->exists()){
            return response()->json(['error' => "Anda telah memberikan umpan balik."], 402);
        }

        $feedback = CourseFeedback::create([
            'user_id' => $user->id,
            'course_id' => $request->input('course_id'),
            'rating' => $request->input('rating'),
            'review' => $request->input('review')
        ]);

        if(!$feedback){
            return response()->json(['error' => 'Gagal menambahkan umpan balik!'], 500);
        }

        $course = Course::findOrFail($request->input('course_id'));

        $notification_student = Notification::create([
            'title' => 'Umpan Balik',
            'message' => 'Terimakasih atas pemberian umpan balik untuk materi ' . $course->title . '! Cek seluruh umpan balik disini!',
            'info' => [
                "target" => ["student"],
                "menu" => "testimonials",
                "course_id" => $course->id
            ]
        ]);

        $notification_teacher = Notification::create([
            'title' => 'Umpan Balik',
            'message' => 'Akun dengan username ' . $user->username . ' telah memberikan umpan balik untuk materi ' . $course->title . ' anda! Cek seluruh umpan balik sekarang!',
            'info' => [
                "target" => ["teacher"],
                "menu" => "feedback",
                "course_id" => $course->id
            ]
        ]);

        $teacher = User::findOrFail($course->teacher_id);
        $notification_student->assignToUsers($user);
        $notification_teacher->assignToUsers($teacher);

        return response()->json(['status' => true, 'message' => 'Terimakasih atas umpan baliknya!'], 200);
    }
}
