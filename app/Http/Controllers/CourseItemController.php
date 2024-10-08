<?php

namespace App\Http\Controllers;

use App\Models\AssessmentQuestion;
use App\Models\Course;
use App\Models\CourseAccess;
use App\Models\CourseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class CourseItemController extends Controller
{
    public function index($id)
    {
        $items = CourseItem::where('course_id', $id)->orderBy('order')->get(['id', 'title', 'description', 'type', 'slug', 'order']);

        return response()->json(['status' => true, 'data' => $items], 200);
    }

    public function storeVideo(Request $request, $id)
    {
        $user = Auth::user();
        $course = Course::findOrFail($id);

        if (!$user->role === 'Superadmin' || !$user->role === 'Teacher') {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        if ($user->role === 'Teacher' && $user->id != $course->teacher_id) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'description' => 'required|string',
            'video_file' => 'required|file|mimes:mp4|max:250000'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $file = $request->file('video_file');
            $uniqid = uniqid();
            $newFileName = $uniqid . '.' . $file->getClientOriginalExtension();
            $filePath = Storage::disk('uploads')->put($newFileName, file_get_contents($file));
            $folderName = $uniqid;

            if (!$filePath) {
                return response()->json(['status' => false, 'message' => 'Gagal upload video'], 400);
            }

            $media = FFMpeg::fromDisk('uploads')->open($newFileName);
            $duration = gmdate('H:i:s', $media->getDurationInSeconds());

            Artisan::call('app:process-video-upload', [
                'video_uniqid' => $uniqid,
                'video_extention' => $file->getClientOriginalExtension()
            ]);

            $item = CourseItem::create([
                "course_id" => $course->id,
                "type" => "Video",
                "title" => $request->input('title'),
                "description" => $request->input('description'),
                "info" => [
                    "playlist_path" => "videos/{$uniqid}/{$uniqid}.m3u8",
                    "duration" => $duration,
                    "playlist" => $uniqid . ".m3u8"
                ],
                "order" => $course->items + 1
            ]);

            if (!$item) {
                return response()->json(['status' => false, 'message' => 'Gagal menambahkan Video baru!'], 400);
            }

            $course->items = $course->items + 1;
            $course->save();

            Storage::disk('uploads')->delete($newFileName);

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Berhasil menambahkan Video baru!']);
        } catch (\Exception $e) {
            DB::rollBack();

            $course->items = $course->items == 0 ? 0 : $course->items - 1;
            $course->save();

            return response()->json(['status' => false, 'message' => 'Gagal menambahkan Video baru!', "exception" => $e->getMessage()], 400);
        }
    }

    public function updateVideo(Request $request, $id)
    {
        $user = Auth::user();

        if (!($user->role === 'Superadmin' || $user->role === 'Teacher')) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'description' => 'required|string',
            'video_file' => 'nullable|file|mimes:mp4|max:250000'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $courseItem = CourseItem::findOrFail($id);
            $courseItem->update([
                "title" => $request->input('title'),
                "description" => $request->input('description')
            ]);

            if($request->hasFile('video_file')){
                $path = explode('/', $courseItem->info['playlist_path'])[1];

                if(Storage::exists('public/videos/' . $path)){
                    Storage::deleteDirectory('public/videos/' . $path);
                }

                if(Storage::disk('secrets')->exists($path)){
                    Storage::disk('secrets')->deleteDirectory($path);
                }

                $file = $request->file('video_file');
                $uniqid = uniqid();
                $newFileName = $uniqid . '.' . $file->getClientOriginalExtension();
                $filePath = Storage::disk('uploads')->put($newFileName, file_get_contents($file));

                if (!$filePath) {
                    return response()->json(['status' => false, 'message' => 'Gagal upload video'], 400);
                }

                $media = FFMpeg::fromDisk('uploads')->open($newFileName);
                $duration = gmdate('H:i:s', $media->getDurationInSeconds());

                Artisan::call('app:process-video-upload', [
                    'video_uniqid' => $uniqid,
                    'video_extention' => $file->getClientOriginalExtension()
                ]);

                $courseItem->update([
                    "info" => [
                        "playlist_path" => "videos/{$uniqid}/{$uniqid}.m3u8",
                        "duration" => $duration,
                        "playlist" => $uniqid . ".m3u8"
                    ]
                ]);

                Storage::disk('uploads')->delete($newFileName);
            }

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Berhasil mengubah data video!']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['status' => false, 'message' => 'Gagal mengubah data video!', "exception" => $e->getMessage()], 400);
        }
    }

    public function storeAssessment(Request $request, $id)
    {
        $user = Auth::user();
        $course = Course::findOrFail($id);

        if (!$user->role === 'Superadmin' || !$user->role === 'Teacher') {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        if ($user->role === 'Teacher' && $user->id != $course->teacher_id) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $requestData = $request->getContent();
        $data = json_decode($requestData, true);
        $validator = Validator::make($data, [
            'title' => 'required|string',
            'description' => 'required|string',
            'type' => 'required|in:Quiz,Exam|string',
            'passing_score' => 'required_if:type,Quiz,Exam|int',
            'question_list' => 'required_if:type,Quiz,Exam|array',
            'question_list.*.question' => 'required|string',
            'question_list.*.options' => 'required|array|min:2',
            'question_list.*.options.*.text' => 'required|string',
            'question_list.*.options.*.is_true' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $questions = $data['question_list'];
            $item = CourseItem::create([
                "course_id" => $course->id,
                "type" => $data['type'],
                "title" => $data['title'],
                "description" => $data['description'],
                "info" => [
                    "passing_score" => $data['passing_score']
                ],
                "order" => $data['type'] == 'Exam' ? 0 : $course->items + 1
            ]);

            if (!$item) {
                return response()->json(['status' => false, 'message' => 'Gagal membuat ' . $data['type'] . ' baru!'], 400);
            }

            $course->items = $course->items + 1;
            $course->save();

            foreach ($questions as $key => $question) {
                $options = [];
                $correct_answer = "";
                foreach ($question['options'] as $opt) {
                    if ($opt['is_true']) {
                        $correct_answer = $opt['text'];
                    }

                    $options[] = $opt['text'];
                }

                AssessmentQuestion::create([
                    "item_id" => $item->id,
                    'question' => $question['question'],
                    'options' => $options,
                    'correct_answer' => $correct_answer,
                    "order" => $key + 1
                ]);
            }

            DB::commit();

            return response()->json(['status' => true, 'message' => 'Berhasil menambahkan ' . $data['type'] . ' baru!']);
        } catch (\Exception $e) {
            DB::rollBack();

            $course->items = $course->items == 0 ? 0 : $course->items - 1;
            $course->save();

            return response()->json(['status' => false, "message" => 'Gagal membuat ' . $data['type'] . ' baru!', "exception" => $e->getMessage()], 400);
        }
    }

    public function updateAssessment(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->role === 'Superadmin' || !$user->role === 'Teacher') {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $requestData = $request->getContent();
        $data = json_decode($requestData, true);
        $validator = Validator::make($data, [
            'title' => 'required|string',
            'description' => 'required|string',
            'type' => 'required|in:Quiz,Exam|string',
            'passing_score' => 'required_if:type,Quiz,Exam|int',
            'question_list' => 'required_if:type,Quiz,Exam|array',
            'question_list.*.question' => 'required|string',
            'question_list.*.options' => 'required|array|min:2',
            'question_list.*.options.*.text' => 'required|string',
            'question_list.*.options.*.is_true' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try{
            $questions = $data['question_list'];
            $to_be_removed = $data['removed_question'];
            $item = CourseItem::findOrFail($id)->update([
                'title' => $data['title'],
                'description' => $data['description'],
                "info" => [
                    "passing_score" => $data['passing_score']
                ]
            ]);

            if (!$item) {
                throw new \Exception('Gagal mengubah ' . $data['type'] . '!', 400);
            }

            foreach ($questions as $key => $question) {
                $options = [];
                $correct_answer = "";
                foreach ($question['options'] as $opt) {
                    if ($opt['is_true']) {
                        $correct_answer = $opt['text'];
                    }

                    $options[] = $opt['text'];
                }

                if($question['status'] == 'old'){
                    $oldQuestion = AssessmentQuestion::findOrFail($question['id'])->update([
                        'question' => $question['question'],
                        'options' => $options,
                        'correct_answer' => $correct_answer,
                        'order' => $key + 1
                    ]);

                    if (!$oldQuestion) {
                        throw new \Exception('Gagal mengubah soal ' . $data['type'] . '!', 400);
                    }
                }else if($question['status'] == 'new'){
                    $newQuestion = AssessmentQuestion::create([
                        "item_id" => $id,
                        'question' => $question['question'],
                        'options' => $options,
                        'correct_answer' => $correct_answer,
                        'order' => $key + 1
                    ]);

                    if (!$newQuestion) {
                        throw new \Exception('Gagal menambahkan soal ' . $data['type'] . '!', 400);
                    }
                }
            }

            foreach($to_be_removed as $target){
                AssessmentQuestion::findOrFail($target['id'])->delete();
            }

            DB::commit();

            return response()->json(['status' => true, 'message' => 'Berhasil mengubah ' . $data['type'] . ' baru!']);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function reorderItems(Request $request){
        $user = Auth::user();
        if (!$user->role === 'Superadmin' || !$user->role === 'Teacher') {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $requestData = $request->getContent();
        $data = json_decode($requestData, true);

        foreach($data as $d){
            $item = CourseItem::findOrFail($d['id'])->update(['order' => $d['order']]);
        }

        return response()->json(['status' => true, 'message' => 'Berhasil mengubah urutan submateri!']);
    }

    public function playlist($course_id, $uniqid, $playlist)
    {
        try {
            return FFMpeg::dynamicHLSPlaylist()
                ->fromDisk('public')
                ->open("videos/{$uniqid}/{$playlist}")
                ->setKeyUrlResolver(function ($key) use ($uniqid, $course_id) {
                    $keyUrl = route('video.key', [
                        'course_id' => $course_id,
                        'key' => $key,
                        'uniqid' => $uniqid]);
                    return $keyUrl;
                })
                ->setPlaylistUrlResolver(function ($playlist) use ($uniqid, $course_id) {
                    $playlistUrl = route('video.playlist', [
                        'course_id' => $course_id,
                        'uniqid' => $uniqid,
                        'playlist' => $playlist
                    ]);
                    return $playlistUrl;
                })
                ->setMediaUrlResolver(function ($media) use ($uniqid) {
                    $mediaUrl = Storage::disk('public')->url("videos/{$uniqid}/{$media}");
                    return $mediaUrl;
                });
        } catch (\Exception $e) {
            abort(500, "Error opening HLS playlist.");
        }
    }

    public function key($course_id, $uniqid, $key)
    {
        $user = Auth::user();

        $allowed = CourseAccess::where('user_id', $user->id)
                        ->where('course_id', $course_id)
                        ->exists();

        if ($user->role == "Student" && !$allowed) {
            abort(401, "Unauthorized.");
        } elseif ($user->role == "Corporate Admin") {
            abort(401, "Unauthorized.");
        }

        $keyPath = $uniqid . '/' . $key;

        if (Storage::disk('secrets')->exists($keyPath)) {
            return Storage::disk('secrets')->download($keyPath);
        } else {
            abort(404, "Key not found.");
        }
    }

    public function show(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->role === 'Superadmin' || !$user->role === 'Teacher') {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $validator = Validator::make($request->all(), ['type' => 'required|in:video,assessment']);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $type = $request->input('type');
        if($type == 'video'){
            $item = CourseItem::with(['course:id,title'])->where('type', $type)->findOrFail($id);
        }else{
            $item = CourseItem::with(['course:id,title', 'questions' => function($query){
                $query->orderBy('order');
            }])->findOrFail($id);
        }

        return response()->json(['status' => true, 'data' => $item]);
    }

    public function deleteAssessment(Request $request)
    {
        $user = Auth::user();
        if (!$user->role === 'Superadmin' || !$user->role === 'Teacher') {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $item = CourseItem::findOrFail($request->input('id'));
        $course = Course::findOrFail($item->course_id);
        DB::beginTransaction();
        try{
            $item->delete();
            $course->items = $course->items == 0 ? 0 : $course->items - 1;
            $course->save();
            DB::commit();

            return response()->json(['status' => true, 'message' => "Berhasil menghapus Submateri {$item->title} dari materi!"], 200);
        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            DB::rollBack();
            return response()->json(['status' => false, 'message' => "Data tidak ditemukan!"], 404);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['status' => false, 'message' => "Gagal menghapus Submateri {$item->title} dari materi!", 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteVideo(Request $request)
    {
        $user = Auth::user();
        if (!$user->role === 'Superadmin' || !$user->role === 'Teacher') {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $item = CourseItem::findOrFail($request->input('id'));
        $course = Course::findOrFail($item->course_id);
        DB::beginTransaction();
        try{
            $path = explode('/', $item->info['playlist_path'])[1];

            if(Storage::exists('public/videos/' . $path)){
                Storage::deleteDirectory('public/videos/' . $path);
            }

            if(Storage::disk('secrets')->exists($path)){
                Storage::disk('secrets')->deleteDirectory($path);
            }

            $item->delete();

            $course->items = $course->items == 0 ? 0 : $course->items - 1;
            $course->save();
            DB::commit();

            return response()->json(['status' => true, 'message' => "Berhasil menghapus Submateri {$item->title} dari materi!"], 200);
        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            DB::rollBack();
            return response()->json(['status' => false, 'message' => "Data tidak ditemukan!"], 404);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['status' => false, 'message' => "Gagal menghapus Submateri {$item->title} dari materi!", 'error' => $e->getMessage()], 500);
        }
    }
}
