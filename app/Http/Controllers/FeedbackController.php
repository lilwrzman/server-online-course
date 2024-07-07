<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
    public function courseFeedback($id)
    {
        $feedbacks = CourseFeedback::where('course_id', $id)
            ->with(['user:id,email,info,avatar', 'course:id,title'])
            ->latest()->take(3)
            ->get(['id', 'user_id', 'course_id', 'rating', 'review', 'created_at']);

        return response()->json(['status' => true, 'data' => $feedbacks], 200);
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

        return response()->json(['status' => true, 'message' => 'Terimakasih atas umpan baliknya!'], 200);
    }
}
