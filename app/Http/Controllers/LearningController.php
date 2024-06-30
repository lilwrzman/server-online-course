<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseAccess;
use App\Models\CourseItem;
use App\Models\StudentProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LearningController extends Controller
{
    public function learning(Request $request)
    {
        $user = Auth::user();
        $item_id = $request->input('item_id');

        if($user->role !== 'Student'){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $course = Course::where('slug', $request->input('course'))
            ->select('id', 'title', 'description', 'slug')
            ->firstOrFail();

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
                ->where('course_id', $course->id)
                ->firstOrFail();
        }else {
            if ($completed_items->isEmpty()) {
                $item = CourseItem::where('course_id', $course->id)
                    ->where('order', 1)
                    ->first();
            } else {
                $latest_completed_item = $completed_items->max('item.order');
                $item = $completed_items->where('item.order', $latest_completed_item)->first()->item->id;
                $item = CourseItem::findOrFail($item);
            }
        }

        return response()->json([
            'status' => true,
            'course' => $course,
            'completed_items' => $completed_items,
            'item' => $item
        ], 200);
    }
}
