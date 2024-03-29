<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::select('id', 'title', 'slug', 'thumbnail', 'price')
            ->withAvg('feedbacks', 'rating')
            ->get();

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
        $validator = Validator::make($request->all(), [
            'learning_path_id' => 'nullable|int',
            'teacher_id' => 'nullable|int',
            'title' => 'required|string',
            'description' => 'required|string',
            'thumbnail_file' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'price' => 'required|int',
            'status' => 'required|string|in:Drafted,Published'
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()], 400);
        }

        $field = $request->all();

        if($request->hasFile('thumbnail_file')){
            $thumbnail = $request->file('thumbnail_file');
            $thumbnailPath = $thumbnail->storeAs(
                'public/courses',
                Str::slug($request->input('title')) . '_' . time() . '.' . $thumbnail->getClientOriginalExtension()
            );

            $field['thumbnail'] = $thumbnailPath;
        }

        Course::create($field);

        return response()->json(['msg' => 'Course created successfully.'], 201);
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
        $course = Course::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'learning_path_id' => 'nullable|int',
            'teacher_id' => 'nullable|int',
            'title' => 'required|string',
            'description' => 'required|string',
            'thumbnail_file' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'price' => 'required|int',
            'status' => 'required|string|in:Drafted,Published'
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()], 400);
        }

        $course->slug = null;
        $course->update($request->all());

        if($request->hasFile('thumbnail_file')){
            if(Storage::exists($course->thumbnail) && $course->thumbnail !== 'public/courses/thumbnail.png'){
                Storage::delete($course->thumbnail);
            }

            $thumbnail = $request->file('thumbnail_file');
            $thumbnailPath = $thumbnail->storeAs(
                'public/courses',
                $request->input('title') . '_' . time() . '.' . $thumbnail->getClientOriginalExtension()
            );

            $course->update(['thumbnail' => $thumbnailPath]);
        }

        return response()->json(['msg' => 'Course updated successfully.'], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $course = Course::findOrFail($id);

        if(Storage::exists($course->thumbnail) && $course->thumbnail !== 'public/courses/thumbnail.png'){
            Storage::delete($course->thumbnail);
        }

        $course->delete();

        return response()->json(['msg' => 'Course deleted successfully.'], 200);
    }
}
