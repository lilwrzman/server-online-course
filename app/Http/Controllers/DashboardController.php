<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\StudentProgress;
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
            $data['count_transaction'] = 0;
            $data['transaction_list'] = [];
        }else if($role == 'Student'){
            $latestProgresses = StudentProgress::with(['item.course'])
                                ->where('user_id', $user->id)
                                ->orderBy('created_at', 'desc')
                                ->get()
                                ->unique('item.course_id')
                                ->take(3);

            $courseIds = $latestProgresses->pluck('item.course_id');

            $courses = Course::whereIn('id', $courseIds)
                        ->with(['items', 'items.progresses' => function($query) use ($user) {
                            $query->where('user_id', $user->id)->orderBy('created_at', 'desc');
                        }])->get();

            var_dump($courses->toArray());

            foreach($courses as $course){
                $course->latest_progress = $latestProgresses->firstWhere('item.course_id', $course->id);
            }

            return response()->json(['status' => true, 'data' => $courses]);
        }

        return response()->json(['status' => true, 'data' => $data]);
    }
}
