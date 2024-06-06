<?php

namespace App\Http\Controllers;

use App\Models\Course;
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
        }

        return response()->json(['status' => true, 'data' => $data]);
    }
}
