<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::all();

        foreach($courses as $course){
            $learningPath = $course->learningPath;
            if($learningPath){
                $course->path_title = $learningPath->title;
                unset($course->learningPath);
            }
        }

        return response()->json(['data' => $courses], 200);
    }

    public function published()
    {
        $course = Course::where('status', 'Published')->get();

        return response()->json(['data' => $course], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if(!$user->role === 'Superadmin'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'learning_path_id' => 'nullable|int',
            'teacher_id' => 'nullable|int',
            'title' => 'required|string',
            'description' => 'required|string',
            'thumbnail_file' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'price' => 'required|int'
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()]);
        }

        $field = $request->all();

        if($request->hasFile('thumbnail_file')){
            $thumbnail = $request->file('thumbnail_file');
            $thumbnailPath = $thumbnail->storeAs(
                'courses',
                Str::slug($request->input('title')) . '_' . time() . '.' . $thumbnail->getClientOriginalExtension(), 'public'
            );

            $field['thumbnail'] = $thumbnailPath;
        }

        $course = Course::create($field);
        if($course && $course->learning_path_id){
            $learningPath = $course->learningPath;
            $learningPath->courses += 1;
            $learningPath->save();

            $course->order = $learningPath->courses;
            $course->save();
        }

        if(!$course){
            return response()->json(['status' => false, 'message' => 'Gagal menambahkan Course.']);
        }

        return response()->json(['status' => true, 'message' => 'Berhasil menambahkan Course.']);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $course = Course::findOrFail($id);
        $course['silabus'] = [];
        $course['review_and_rating'] = [];
        $course['sum_enroll'] = 0;
        $course['avg_rating'] = 0;

        return response()->json(['data' => $course], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if(!$user->role === 'Superadmin'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $course = Course::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'learning_path_id' => 'nullable|int',
            'teacher_id' => 'nullable|int',
            'title' => 'required|string',
            'description' => 'required|string',
            'thumbnail_file' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'price' => 'required|int'
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()]);
        }

        $course->slug = null;
        $course->update($request->all());

        if($request->hasFile('thumbnail_file')){
            if(Storage::exists('public/' . $course->thumbnail) && !str_contains($course->thumbnail, 'thumbnail.png')){
                Storage::delete('public/' . $course->thumbnail);
            }

            $thumbnail = $request->file('thumbnail_file');
            $thumbnailPath = $thumbnail->storeAs(
                'courses',
                $request->input('title') . '_' . time() . '.' . $thumbnail->getClientOriginalExtension(). 'public'
            );

            $course->update(['thumbnail' => $thumbnailPath]);
        }

        return response()->json(['status' => true, 'message' => 'Berhasil mengubah Course.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $user = Auth::user();
        if(!$user->role === 'Superadmin'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $course = Course::findOrFail($request->input('id'));
        $course->delete();

        if($course->learning_path_id){
            $learningPath = $course->learningPath;
            $learningPath->courses -= 1;
            $learningPath->save();

            $courses = $learningPath->courses()->orderBy('order')->get();
            foreach ($courses as $index => $course){
                $course->order = $index + 1;
                $course->save();
            }
        }

        if(Storage::exists('public/' . $course->thumbnail) && !str_contains($course->thumbnail, 'thumbnail.png')){
            Storage::delete('public/' . $course->thumbnail);
        }

        return response()->json(['status' => true, 'message' => 'Berhasil menghapus Course.'], 200);
    }
}
