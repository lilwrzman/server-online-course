<?php

namespace App\Http\Controllers;

use App\Models\AssessmentQuestion;
use App\Models\Course;
use App\Models\CourseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        if ($user->role === 'Teacher' && $user->id != $course->teacher_id) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|unique:course_items',
            'description' => 'required|string',
            'video_file' => 'required|file|mimes:mp4|max:102400'
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
            $folderName = Str::slug($course->title) . '_' . Str::slug($request->input('title'));

            if (!$filePath) {
                return response()->json(['status' => false, 'message' => 'Gagal upload video'], 400);
            }

            $media = FFMpeg::fromDisk('uploads')->open($newFileName);
            $duration = gmdate('H:i:s', $media->getDurationInSeconds());

            Artisan::call('app:process-video-upload', [
                'fileName' => $newFileName,
                'folderName' => $folderName
            ]);

            $playlistPath = trim(Artisan::output());

            $item = CourseItem::create([
                "course_id" => $course->id,
                "type" => "Video",
                "title" => $request->input('title'),
                "description" => $request->input('description'),
                "info" => [
                    "playlist_path" => $playlistPath,
                    "duration" => $duration
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

            foreach ($questions as $question) {
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
                    'correct_answer' => $correct_answer
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

            foreach ($questions as $question) {
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
                        'order' => $question['order']
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
                        'order' => $question['order']
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

    public function playlist($playlist)
    {
        return FFMpeg::dynamicHLSPlaylist()
            ->fromDisk('public')
            ->open("videos/{$playlist}")
            ->setKeyUrlResolver(function ($key) {
                return route('video.key', ['key' => $key]);
            })
            ->setPlaylistUrlResolver(function ($playlist) {
                return route('video.playlist', ['playlist' => $playlist]);
            })
            ->setMediaUrlResolver(function ($media) {
                return Storage::disk('public')->url("videos/{$media}");
            });
    }

    public function key($key)
    {
        // todo
        return Storage::disk('secrets')->download($key);
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


    public function update(Request $request, CourseItem $courseItem)
    {
        //
    }


    public function destroy(CourseItem $courseItem)
    {
        //
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
            $path = $item->info['playlist_path'];
            $pathToCheck = Str::slug($course->title) . '_' . Str::slug($item->title);

            if (Storage::exists('public/videos/' . $pathToCheck)) {
                Storage::deleteDirectory('public/videos/' . $pathToCheck);
            }

            if(Storage::disk('secrets')->exists($pathToCheck)){
                Storage::disk('secrets')->deleteDirectory($pathToCheck);
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
