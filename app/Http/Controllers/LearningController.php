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
        $next_item = $request->input('next_item');

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
                if ($next_item) {
                    $latest_completed_item = $completed_items->max('item.order');
                    $next_item = CourseItem::where('course_id', $course->id)
                        ->where('order', '>', $latest_completed_item)
                        ->orderBy('order')
                        ->first();

                    if (!$next_item) {
                        $next_item = CourseItem::where('course_id', $course->id)
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
                    $item = CourseItem::findOrFail($item);
                }
            }
        }

        if ($item && ($item->type === 'Quiz' || $item->type === 'Exam')) {
            $questions_count = $item->questions()->count();
            $item->questions_count = $questions_count;
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
}
