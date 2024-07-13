<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Discussion;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DiscussionController extends Controller
{
    public function courseDiscussion($id)
    {
        $datas = Course::select('id', 'title')
                    ->with([
                        'discussions' => function($query) {
                            $query->whereNull('parent_id')
                                ->select('id', 'user_id', 'course_id', 'parent_id', 'content', 'created_at')
                                ->orderBy('created_at', 'desc')
                                ->with([
                                    'user:id,email,info,avatar,role',
                                    'replies' => function($query){
                                        $query->select('id', 'user_id', 'course_id', 'parent_id', 'content', 'created_at')
                                            ->with(['user:id,email,info,avatar,role']);
                                    }
                                ]);
                        }
                    ])->findOrFail($id);

        return response()->json(['status' => true, 'data' => $datas], 200);
    }

    public function postDiscussion(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'course_id' => 'required|int',
            'parent_id' => 'sometimes|int',
            'content' => 'required|string'
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()], 402);
        }

        $discuss = Discussion::create([
            'user_id' => $user->id,
            'course_id' => $request->input('course_id'),
            'parent_id' => $request->input('parent_id'),
            'content' => $request->input('content')
        ]);

        if(!$discuss){
            return response()->json(['error' => 'Gagal menambah data diskusi!'], 500);
        }

        if($request->input('parent_id') && $request->input('parent_id') != $user->id){
            $parent = User::findOrFail($request->input('parent_id'));
            $course = Course::findOrFail($request->input('course_id'));
            $notification = Notification::create([
                'title' => 'Forum Diskusi',
                'message' => 'Pengguna akun dengan username ' . $user->username . ' membalas diskusi anda pada materi ' . $course->title . '!',
                'info' => [
                    "target" => [strtolower($parent->role)],
                    "menu" => "discussion",
                    "course_id" => $course->id
                ]
            ]);

            $notification->assignToUsers($parent);
        }

        return response()->json(['status' => true, 'message' => 'Diskusi berhasil ditambahkan!'], 200);
    }
}
