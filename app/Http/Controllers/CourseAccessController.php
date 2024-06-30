<?php

namespace App\Http\Controllers;

use App\Models\CourseAccess;
use App\Models\CourseItem;
use App\Models\StudentProgress;
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
                'course:id,learning_path_id,title,thumbnail,slug',
                'course.learningPath:id,title,color',
                'course.items:id,course_id,slug'
            ])->get(['id', 'user_id', 'course_id', 'status', 'type', 'access_date']);

        $itemIds = CourseItem::whereIn('course_id', $my_courses->pluck('course_id'))->pluck('id');

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

        $completed_items = StudentProgress::whereIn('item_id', $itemIds)
            ->where('user_id', $user->id)
            ->get()
            ->groupBy('item.course_id')
            ->map(function ($progresses) {
                return $progresses->count();
            });

        $my_courses->each(function ($course) use ($latest_progress, $completed_items) {
            $course->latest_progress = $latest_progress->get($course->course_id);
            $course->completed_items = $completed_items->get($course->course_id, 0);
            $course->total_items = $course->course->items->count();
        });

        return response()->json(['status' => true, 'data' => $my_courses], 200);
    }
}
