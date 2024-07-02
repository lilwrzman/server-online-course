<?php

namespace App\Http\Controllers;

use App\Models\AssessmentHistory;
use App\Models\AssessmentQuestion;
use App\Models\Course;
use App\Models\CourseAccess;
use App\Models\CourseItem;
use App\Models\StudentProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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

        if($user->role != 'Student'){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'required|int'
        ]);

        if($validator->fails()){
            return response()->json(['error_validator' => $validator->errors()], 400);
        }

        $item = CourseItem::whereIn('type', ['Quiz', 'Exam'])
            ->with(['questions'])
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
            'answer' => json_encode($answers),
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
        }

        $result = "";
        if($isPass){
            $result = "Selamat, Anda lulus kuis dengan perolehan nilai: {$percentage_score}";
        }else{
            $result = "Mohon maaf, Anda masih gagal kuis dengan perolehan nilai: {$percentage_score}";
        }

        return response()->json(['status' => true, 'message' => $result, 'title' => $isPass ? 'Selamat!' : 'Oops!'], 200);
    }

    public function assessmentHistory(Request $request)
    {
        $user = Auth::user();
        $item_id = $request->input('item_id');

        if($user->role != 'Student'){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'required|int'
        ]);

        if($validator->fails()){
            return response()->json(['error_validator' => $validator->errors()], 400);
        }

        $histories = AssessmentHistory::where('user_id', $user->id)
                        ->where('item_id', $item_id)
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
}
