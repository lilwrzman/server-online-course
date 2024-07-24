<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    public function index()
    {
        $user = Auth::guard('api')->user();
        $data = [];
        $data['courses'] = Course::with(['learningPath:id,title,color'])->get();

        foreach($data['courses'] as $course){
            $course['items'] = $course->items()->count();
            $totalRating = 0;
            $countFeedbacks = $course->feedbacks->count();

            if ($countFeedbacks > 0) {
                foreach ($course->feedbacks as $feedback) {
                    $totalRating += $feedback->rating;
                }
                $averageRating = $totalRating / $countFeedbacks;
            } else {
                $averageRating = 0;
            }

            $course['rating'] = $averageRating;
        }

        if ($user && $user->role == 'Teacher') {
            $data['my_courses'] = Course::with('learningPath:id,title,color')->where('teacher_id', $user->id)->get();
            $data['my_courses']->makeHidden(['learning_path_id']);

            foreach ($data['my_courses'] as $course) {
                $course['items'] = $course->items()->count();

                $totalRating = 0;
                $countFeedbacks = $course->feedbacks->count();

                if ($countFeedbacks > 0) {
                    foreach ($course->feedbacks as $feedback) {
                        $totalRating += $feedback->rating;
                    }
                    $averageRating = $totalRating / $countFeedbacks;
                } else {
                    $averageRating = 0;
                }

                $course['rating'] = $averageRating;
            }
        }

        $data['courses']->makeHidden(['learning_path_id']);

        return response()->json(['data' => $data], 200);
    }

    public function loneCourse()
    {
        $courses = Course::whereNull('learning_path_id')->get();

        return response()->json(['data' => $courses], 200);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if(!$user->role === 'Superadmin'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'teacher_id' => 'required|int',
            'title' => 'required|string',
            'description' => 'required|string',
            'thumbnail_file' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'price' => 'required|int',
            'facilities' => 'required|array',
            'facilities.*' => 'required|string'
        ], [
            'teacher_id.required' => 'Silahkan pilih salah satu pemateri.',
            'title.required' => 'Harap masukkan judul.',
            'desciption.required' => 'Harap masukkan deskripsi.',
            'price.required' => 'Harap tentukan harga jual materi.',
            'price.int' => 'Harap masukkan harga dalam bentuk angka.',
            'facilities.required' => 'Harap menentukan fasilitas pembelajaran materi.'
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()], 422);
        }

        $field = $request->all();

        if($request->hasFile('thumbnail_file')){
            $thumbnail = $request->file('thumbnail_file');
            $thumbnailPath = $thumbnail->storeAs(
                'courses',
                uniqid() . '_' . time() . '.' . $thumbnail->getClientOriginalExtension(), 'public'
            );

            $field['thumbnail'] = $thumbnailPath;
        }

        $course = Course::create($field);

        if(!$course){
            return response()->json(['status' => false, 'message' => 'Gagal menambahkan Course.']);
        }

        $notification_admin = Notification::create([
            'title' => 'Materi',
            'message' => 'Anda telah menambahkan materi baru! Atur dan terbitkan apabila telah lengkap!',
            'info' => [
                "target" => ["superadmin"],
                "menu" => "course",
                "course_id" => $course->id
            ]
        ]);

        $notification_teacher = Notification::create([
            'title' => 'Materi',
            'message' => 'Anda telah ditugaskan untuk mengurus Materi ' . $course->title . '! Cek dan atur sekarang!',
            'info' => [
                "target" => ["teacher"],
                "menu" => "course",
                "course_id" => $course->id
            ]
        ]);

        $teacher = User::findOrFail($request->input('teacher_id'));

        $notification_admin->assignToUsers($user);
        $notification_teacher->assignToUsers($teacher);

        return response()->json(['status' => true, 'message' => 'Berhasil menambahkan Course.']);
    }

    public function show(Request $request, $id)
    {
        $course = Course::with([
                'teacher:id,info,avatar',
                'items:id,course_id,title,description,info,type',
                'items.questions:id,item_id'
            ])
            ->findOrFail($id);

        $totalRating = 0;
        $countFeedbacks = $course->feedbacks->count();

        if ($countFeedbacks > 0) {
            foreach ($course->feedbacks as $feedback) {
                $totalRating += $feedback->rating;
            }
            $averageRating = $totalRating / $countFeedbacks;
        } else {
            $averageRating = 0;
        }

        $course['rating'] = $averageRating;

        if($request->input('with_teachers') == 'yes'){
            $course['teachers'] = User::where('id', '!=', $course->teacher_id)
                ->where('role', '=', 'Teacher')->get();
        }

        unset($course->teacher_id);

        return response()->json(['data' => $course], 200);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        if(!$user->role === 'Superadmin' && !$user->role === "Teacher"){
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $course = Course::findOrFail($request->input('id'));
        $section = $request->input('section');

        if($section == 'General'){
            $validator = Validator::make($request->all(), [
                'title' => 'required|string',
                'description' => 'required|string',
                'facilities' => 'required|array',
                'facilities.*' => 'required|string'
            ], [
                'title.required' => 'Harap masukkan judul.',
                'title.string' => 'Harap masukkan judul dalam bentuk kombinasi huruf maupun angka!',
                'desciption.required' => 'Harap masukkan deskripsi.',
                'description.string' => 'Harap masukkan deskripsi dalam bentuk kombinasi huruf maupun angka!',
                'facilities.required' => 'Harap menentukan fasilitas pembelajaran materi.'
            ]);

            if($validator->fails()){
                return response()->json(['error' => $validator->errors()], 422);
            }

            $course->slug = null;
            $course->update([
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'facilities' => $request->input('facilities')
            ]);

            return response()->json(['status' => true, 'message' => 'Data umum Materi berhasil diubah!', 'data' => $course], 201);

        }else if($section == 'Thumbnail'){
            $validator = Validator::make($request->all(), [
                'thumbnail_file' => 'required|image|mimes:jpeg,png,jpg|max:2048'
            ], [
                'thumbnail_file.required' => 'Harap unggah gambar!',
                'thumbnail_file.images' => 'File harus berupa gambar!',
                'thumbnail_file.mimes' => 'File harus memiliki format JPEG, PNG, atau JPG!',
                'thumbnail_file.max' => 'Ukuran file tidak boleh melebihi 2 Mb!'
            ]);

            if($validator->fails()){
                return response()->json(['error' => $validator->errors()], 422);
            }

            if(Storage::exists('public/' . $course->thumbnail) && !str_contains($course->thumbnail, 'thumbnail.png')){
                Storage::delete('public/' . $course->thumbnail);
            }

            $thumbnail = $request->file('thumbnail_file');
            $thumbnailPath = $thumbnail->storeAs(
                'courses',
                uniqid() . '_' . time() . '.' . $thumbnail->getClientOriginalExtension(), 'public'
            );

            $course->update(['thumbnail' => $thumbnailPath]);

            return response()->json(['status' => true, 'message' => 'Thumbnail Materi berhasil diubah!', 'data' => $course], 201);

        }else if($section == 'Teacher'){
            $validator = Validator::make($request->all(), [
                'teacher_id' => 'required|int'
            ], [
                'teacher_id.required' => 'Harap pilih pemateri!',
            ]);

            if($validator->fails()){
                return response()->json(['error' => $validator->errors()], 422);
            }

            $course->teacher_id = $request->input('teacher_id');
            $course->save();

            return response()->json(['status' => true, 'message' => 'Pemateri berhasil diubah!', 'data' => $course], 201);

        }else if($section == 'Price'){
            $validator = Validator::make($request->all(), [
                'price' => 'required|int'
            ]);

            if($validator->fails()){
                return response()->json(['error' => $validator->errors()], 422);
            }

            $course->price = $request->input('price');
            $course->save();

            return response()->json(['status' => true, 'message' => 'Harga Materi berhasil diubah!', 'data' => $course], 201);
        }
    }

    public function destroy(Request $request)
    {
        $user = Auth::user();
        if(!$user->role === 'Superadmin'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        DB::beginTransaction();

        try {
            $course = Course::findOrFail($request->input('id'));

            if (Storage::exists('public/' . $course->thumbnail) && !str_contains($course->thumbnail, 'thumbnail.png')) {
                Storage::delete('public/' . $course->thumbnail);
            }

            if ($course->learning_path_id) {
                $learningPath = $course->learningPath;
                $learningPath->courses -= 1;
                $learningPath->save();

                $courses = $learningPath->courses()->whereNot('id', $course->id)->orderBy('order')->get();
                foreach ($courses as $index => $item) {
                    $item->order = $index + 1;
                    $item->save();
                }
            }

            $course->delete();

            DB::commit();

            return response()->json(['status' => true, 'message' => 'Berhasil menghapus Course.'], 200);
        } catch (QueryException $e) {
            DB::rollBack();

            if ($e->getCode() == '23000') {
                return response()->json(['error' => 'Materi tidak dapat dihapus karena berkaitan dengan Course Access.']);
            }

            return response()->json(['error' => 'Gagal menghapus materi.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal menghapus materi.']);
        }
    }

    public function removeTeacher($id)
    {
        $user = Auth::user();
        if(!$user->role === 'Superadmin'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $course = Course::findOrFail($id);
        $course->teacher_id = null;
        $course->save();

        return response()->json(['status' => true, 'message' => 'Berhasil melepas pemateri dari materi ' . $course->title . '!'], 200);
    }

    public function reorderCourse(Request $request)
    {
        $user = Auth::user();
        if (!$user->role === 'Superadmin') {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $requestData = $request->getContent();
        $data = json_decode($requestData, true);

        foreach($data as $d){
            $item = Course::findOrFail($d['id'])->update(['order' => $d['order']]);
        }

        return response()->json(['status' => true, 'message' => 'Berhasil mengubah urutan materi pada alur belajar!']);
    }

    public function publish($id)
    {
        $user = Auth::user();

        if(!$user->role === 'Superadmin' && !$user->role === "Teacher"){
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $published = Course::findOrFail($id)->update([
            "isPublished" => true
        ]);

        if(!$published){
            return response()->json(['error' => 'Gagal memublikasikan materi.'], 500);
        }

        return response()->json(['status' => true, 'message' => "Publikasi berhasil!", 201]);
    }

    public function unPublish($id)
    {
        $user = Auth::user();

        if(!$user->role === 'Superadmin' && !$user->role === "Teacher"){
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $published = Course::findOrFail($id)->update([
            "isPublished" => false
        ]);

        if(!$published){
            return response()->json(['error' => 'Gagal membatalkan publikasi materi.'], 500);
        }

        return response()->json(['status' => true, 'message' => "Publikasi berhasil dibatalkan!", 201]);
    }
}
