<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseAccess;
use App\Models\CourseFeedback;
use App\Models\StudentProgress;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $role = $user->role;
        $data = [];

        if($role == 'Superadmin'){
            $data['count_student'] = User::where('role', 'Student')->count();
            $data['count_course'] = Course::count();
            $data['count_corporate'] = User::where('role', 'Corporate Admin')->count();
            $data['count_transaction'] = Transaction::where('status', 'success')->count();
            $data['transaction_list'] = Transaction::with([
                'course:id,title,thumbnail',
                'course.items:id,course_id,type,title',
                'student:id,username,info'
            ])->orderBy('created_at', 'desc')->take(5)->get();

            $data['transaction_list']->makeHidden('snap_token');

            return response()->json(['status' => true, 'data' => $data]);
        }else if($role == 'Teacher'){
            $accesses = CourseAccess::with([
                'course' => function($query) use ($user) {
                    $query->where('teacher_id', $user->id);
                },
                'student:id,username,info,avatar'
            ])->get();

            $data['count_student'] = $accesses->count();
            $data['count_student_done'] = $accesses->where('status', 'Completed')->count();
            $data['count_course'] = Course::where('teacher_id', $user->id)->count();
            $data['latest_feedback'] = CourseFeedback::whereIn('course_id', $accesses->pluck('course_id'))
                                        ->orderBy('created_at', 'desc')
                                        ->take(5)
                                        ->get();

            return response()->json(['status' => true, 'data' => $data]);
        }else if($role == 'Student'){
            $latestProgresses = StudentProgress::with(['item.course:id,title'])
                                ->where('user_id', $user->id)
                                ->orderBy('created_at', 'desc')
                                ->get()
                                ->unique('item.course_id')
                                ->take(3);

            $courseIds = $latestProgresses->pluck('item.course_id');

            $courses = Course::whereIn('id', $courseIds)
                        ->with(['items.progresses' => function($query) use ($user) {
                            $query->where('user_id', $user->id)->orderBy('created_at', 'desc');
                        }])->get(['id', 'title']);

            foreach($courses as $course){
                $course->latest_progress = $latestProgresses->firstWhere('item.course_id', $course->id);

                $progressCount = StudentProgress::whereHas('item', function ($query) use ($course) {
                    $query->where('course_id', $course->id);
                })->where('user_id', $user->id)->count();
                $course->progress_count = $progressCount;
                $course->items_count = $course->items->count();

                $course->feedback = $course->feedbacks->firstWhere('user_id', $user->id);

                $course->makeHidden(['items', 'feedbacks']);
                $course->latest_progress->item->makeHidden(['description', 'slug', 'info', 'order', 'created_at', 'updated_at', 'course']);
            }

            return response()->json(['status' => true, 'data' => $courses]);
        }
    }
}
