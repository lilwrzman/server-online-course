<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LearningPathController extends Controller
{
    public function index()
    {
        $paths = LearningPath::all();
        $paths->makeHidden(['created_at', 'updated_at']);

        return response()->json(['data' => $paths], 200);
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
            'title' => 'required|string',
            'description' => 'required|string',
            'thumbnail_file' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()]);
        }

        $field = $request->only(['title', 'description']);

        if($request->hasFile('thumbnail_file')){
            $thumbnail = $request->file('thumbnail_file');
            $thumbnailPath = $thumbnail->storeAs(
                '/public/learning-paths',
                Str::slug($request->input('title')) . '_' . time() . '.' . $thumbnail->getClientOriginalExtension()
            );

            $field['thumbnail'] = $thumbnailPath;
        }

        LearningPath::create($field);

        return response()->json(['status' => true, 'message' => 'Berhasil menambahkan Learning Path.'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($slug)
    {
        $path = LearningPath::where('slug', '=', $slug)->firstOrFail();

        return response()->json(['data' => $path], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        if(!$user->role === 'Superadmin'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $path = LearningPath::findOrFail($request->input('id'));
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'description' => 'required|string',
            'thumbnail_file' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()]);
        }

        $path->slug = null;
        $path->update($request->all());

        if($request->hasFile('thumbnail_file')){
            if(Storage::exists($path->thumbnail) && $path->thumbnail !== '/public/learning-paths/thumbnail.png'){
                Storage::delete($path->thumbnail);
            }

            $thumbnail = $request->file('thumbnail_file');
            $thumbnailPath = $thumbnail->storeAs(
                '/public/learning-paths',
                Str::slug($path->title) . '_' . time() . '.' . $thumbnail->getClientOriginalExtension()
            );

            $path->update(['thumbnail' => $thumbnailPath]);
        }

        return response()->json(['status' => true, 'message' => 'Berhasil mengedit Learning Path.', 'data' => $path], 201);
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

        $path = LearningPath::findOrFail($request->input('id'));

        if(Storage::exists($path->thumbnail) && $path->thumbnail !== '/public/learning-paths/default.png'){
            Storage::delete($path->thumbnail);
        }

        $path->delete();

        return response()->json(['status' => true, 'message' => 'Berhasil menghapus Learning Path.', 'data' => $path], 200);
    }
}
