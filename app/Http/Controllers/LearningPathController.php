<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\LearningPath;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LearningPathController extends Controller
{
    public function index()
    {
        $paths = LearningPath::all();

        return response()->json(['data' => $paths], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if(!$user->role === 'Superadmin'){ return response()->json(['error' => 'Unauthenticated.'], 401); }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'description' => 'required|string',
            'thumbnail_file' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'title.required' => 'Harap masukkan judul!',
            'title.string' => 'Harap masukkan judul dalam bentuk kombinasi huruf maupun angka!',
            'description.required' => 'Harap masukkan deskripsi!',
            'description.string' => 'Harap masukkan deskripsi dalam bentuk kombinasi huruf maupun angka!',
            'thumbnail_file.images' => 'File harus berupa gambar!',
            'thumbnail_file.mimes' => 'File harus memiliki format JPEG, PNG, atau JPG!',
            'thumbnail_file.max' => 'Ukuran file tidak boleh melebihi 2 Mb!'
        ]);

        if($validator->fails()) { return response()->json(['error' => $validator->errors()]); }

        $field = $request->only(['title', 'description']);

        if($request->hasFile('thumbnail_file')){
            $thumbnail = $request->file('thumbnail_file');
            $thumbnailPath = $thumbnail->storeAs(
                'learning-paths',
                uniqid() . '_' . time() . '.' . $thumbnail->getClientOriginalExtension(), 'public'
            );

            $field['thumbnail'] = $thumbnailPath;
        }

        $path = LearningPath::create($field);

        if(!$path){
            return response()->json(['status' => false, 'message' => 'Gagal menambahkan Alur Belajar.']);
        }

        $existingColors = LearningPath::pluck('color')->filter()->all();

        do {
            $color = $this->generatePastelColor();
        } while (!$this->isColorUniqueAndDifferent($color, $existingColors));

        $path->color = $color;

        if($request->input('courses')){
            $selected_course_ids = explode(',', $request->input('courses'));
            $last_order = Course::where('learning_path_id', $path->id)->max('order') ?? 0;

            foreach ($selected_course_ids as $increment => $course_id) {
                Course::where('id', $course_id)->update([
                    'learning_path_id' => $path->id,
                    'order' => $last_order + $increment + 1
                ]);
            }

            $path->courses = count($selected_course_ids);
        }

        $path->save();

        return response()->json(['status' => true, 'message' => 'Berhasil menambahkan Alur Belajar.', 'path' => $path], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $slug)
    {
        $path = LearningPath::with('courses')->where('slug', '=', $slug)->firstOrFail();

        if($request->input('with_courses') == 'yes'){
            $path['lone_course'] = Course::whereNull('learning_path_id')->get();
        }

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
        $section = $request->input('section');

        if($section == 'General'){
            $validator = Validator::make($request->all(), [
                'title' => 'required|string',
                'description' => 'required|string'
            ], [
                'title.required' => 'Harap masukkan judul!',
                'title.string' => 'Harap masukkan judul dalam bentuk kombinasi huruf maupun angka!',
                'description.required' => 'Harap masukkan deskripsi!',
                'description.string' => 'Harap masukkan deskripsi dalam bentuk kombinasi huruf maupun angka!'
            ]);

            if($validator->fails()){
                return response()->json(['error' => $validator->errors()]);
            }

            $path->slug = null;
            $path->update([
                'title' => $request->input('title'),
                'description' => $request->input('description')
            ]);

            return response()->json(['status' => true, 'message' => 'Data umum Alur Belajar berhasil diubah!', 'data' => $path], 201);

        }else if($section == 'Thumbnail'){
            $validator = Validator::make($request->all(), [
                'thumbnail_file' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ], [
                'thumbnail_file.required' => 'Harap unggah gambar!',
                'thumbnail_file.images' => 'File harus berupa gambar!',
                'thumbnail_file.mimes' => 'File harus memiliki format JPEG, PNG, atau JPG!',
                'thumbnail_file.max' => 'Ukuran file tidak boleh melebihi 2 Mb!'
            ]);

            if($validator->fails()){
                return response()->json(['error' => $validator->errors()]);
            }

            if(Storage::exists('public/' . $path->thumbnail) && !str_contains($path->thumbnail, 'thumbnail.png')){
                Storage::delete('public/' . $path->thumbnail);
            }

            $thumbnail = $request->file('thumbnail_file');
            $thumbnailPath = $thumbnail->storeAs(
                'learning-paths',
                uniqid() . '_' . time() . '.' . $thumbnail->getClientOriginalExtension(), 'public'
            );

            $path->update(['thumbnail' =>  $thumbnailPath]);

            return response()->json(['status' => true, 'message' => 'Thumbnail Alur Belajar berhasil diubah!', 'data' => $path], 201);

        }else if($section == 'Course'){
            $validator = Validator::make($request->all(), [
                'selected_course' => 'required',
            ], [
                'thumbnail_file.required' => 'Harap pilih materi yang ingin ditambahkan!',
            ]);

            if($validator->fails()){
                return response()->json(['error' => $validator->errors()]);
            }

            $selected_course_ids = explode(',', $request->input('selected_course'));
            $last_order = Course::where('learning_path_id', $path->id)->max('order') ?? 0;

            foreach ($selected_course_ids as $increment => $course_id) {
                Course::where('id', $course_id)->update([
                    'learning_path_id' => $path->id,
                    'order' => $last_order + $increment + 1
                ]);
            }

            $total_courses = Course::where('learning_path_id', $path->id)->count();
            $path->courses = $total_courses;
            $path->save();

            return response()->json([
                'status' => true,
                'message' => 'Materi yang dipilih gagal ditambahkan ke Alur Belajar!',
                'data' => $path], 201);
        }
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

        if(Storage::exists('public/' . $path->thumbnail) && !str_contains($path->thumbnail, 'thumbnail.png')){
            Storage::delete('public/' . $path->thumbnail);
        }

        Course::where('learning_path_id', '=', $path->id)->update([
            'learning_path_id' => null,
            'order' => 0
        ]);
        $path->delete();

        return response()->json(['status' => true, 'message' => 'Berhasil menghapus Alur Belajar.'], 200);
    }

    public function removeCourse(Request $request)
    {
        $user = Auth::user();
        if(!$user->role === 'Superadmin'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $path = LearningPath::findOrFail($request->input('learning_path_id'));
        $path->courses = $path->courses > 0 ?  $path->courses - 1 : 0;
        $path->save();

        $course = Course::findOrFail($request->input('course_id'));
        $course->learning_path_id = null;
        $course->order = 0;
        $course->save();

        return response()->json(['status' => true, 'message' => 'Berhasil menghapus materi dari Alur Belajar'], 200);
    }

    private function colorDistance($color1, $color2)
    {
        $r1 = hexdec(substr($color1, 1, 2));
        $g1 = hexdec(substr($color1, 3, 2));
        $b1 = hexdec(substr($color1, 5, 2));

        $r2 = hexdec(substr($color2, 1, 2));
        $g2 = hexdec(substr($color2, 3, 2));
        $b2 = hexdec(substr($color2, 5, 2));

        return sqrt(pow($r2 - $r1, 2) + pow($g2 - $g1, 2) + pow($b2 - $b1, 2));
    }

    private function isColorUniqueAndDifferent($newColor, $existingColors, $minDistance = 50)
    {
        foreach ($existingColors as $color) {
            if ($newColor == $color || $this->colorDistance($newColor, $color) < $minDistance) {
                return false;
            }
        }
        return true;
    }

    private function generatePastelColor()
    {
        $red = rand(100, 200);
        $green = rand(100, 200);
        $blue = rand(100, 200);

        return sprintf("#%02X%02X%02X", $red, $green, $blue);
    }
}
