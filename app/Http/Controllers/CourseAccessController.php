<?php

namespace App\Http\Controllers;

use App\Models\CourseAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseAccessController extends Controller
{
    public function myCourses()
    {
        $user = Auth::user();

        if(!$user->role === 'Student'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $my_courses = CourseAccess::where('user_id', $user->id)
            ->with([
                'course:id,learning_path_id,title,thumbnail',
                'course.learningPath:id,title,color',
                'course.items:id,course_id'
            ])->get(['id', 'user_id', 'course_id', 'status', 'type', 'access_date']);

        return response()->json(['status' => true, 'data' => $my_courses], 200);
    }
}
