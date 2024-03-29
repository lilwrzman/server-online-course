<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LearningPathController extends Controller
{
    public function index()
    {
        $paths = LearningPath::all();
        foreach($paths as $path){
            $path->sum_courses = $path->courses()->count();
        }

        return response()->json(['data' => $paths], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'description' => 'required|string',
            'thumbnail_file' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()], 400);
        }

        $field = $request->all();

        if($request->hasFile('thumbnail_file')){
            $thumbnail = $request->file('thumbnail_file');
            $thumbnailPath = $thumbnail->storeAs(
                'public/learning-paths',
                Str::slug($request->input('title')) . '_' . time() . '.' . $thumbnail->getClientOriginalExtension()
            );

            $field['thumbnail'] = $thumbnailPath;
        }

        LearningPath::create($field);

        return response()->json(['msg' => 'Learning Path created successfully.'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $path = LearningPath::findOrFail($id);
        $path->sum_courses = $path->courses()->count();

        return response()->json(['data' => $path], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $path = LearningPath::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'description' => 'required|string',
            'thumbnail_file' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()], 400);
        }

        $path->slug = null;
        $path->update($request->all());

        if($request->hasFile('thumbnail_file')){
            if(Storage::exists($path->thumbnail) && $path->thumbnail !== 'public/learning-paths/thumbnail.png'){
                Storage::delete($path->thumbnail);
            }

            $thumbnail = $request->file('thumbnail_file');
            $thumbnailPath = $thumbnail->storeAs(
                'public/learning-paths',
                Str::slug($path->title) . '_' . time() . '.' . $thumbnail->getClientOriginalExtension()
            );

            $path->update(['thumbnail' => $thumbnailPath]);
        }

        return response()->json(['data' => 'Learning Path updated successfully.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $path = LearningPath::findOrFail($id);

        if(Storage::exists($path->thumbnail) && $path->thumbnail !== 'public/learning-paths/default.png'){
            Storage::delete($path->thumbnail);
        }

        $path->delete();

        return response()->json(['msg' => 'Learning Path deleted successfully.'], 200);
    }
}
