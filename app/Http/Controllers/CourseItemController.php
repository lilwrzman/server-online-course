<?php

namespace App\Http\Controllers;

use App\Models\CourseItem;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use ProtoneMedia\LaravelFFMpeg\Exporters\HLSExporter;
use ProtoneMedia\LaravelFFMpeg\Exporters\HLSVideoFilters;

class CourseItemController extends Controller
{
    public function index()
    {
        $items = CourseItem::all();

        return response()->json(['data' => $items], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|int',
            'title' => 'required|string',
            'content' => 'required|string',
            'type' => 'required|string|in:Material,Quiz,Exam',
            'video_file' => 'required_unless:type,Quiz,Exam|file|mimes:mp4|max:102400',
            'passing_score' => 'required_if:type,Quiz,Exam|int'
        ]);

        if($validator->fails()){
            return response()->json(['data' => $validator->errors()], 400);
        }

        $item = CourseItem::create($request->all());

        if($request->input('type') === 'Material'  && $request->hasFile('video_file')){
            try{
                $lowFormat = (new X264('aac'))->setKiloBitrate(500);
                $highFormat = (new X264('aac'))->setKiloBitrate(1000);
                $key = HLSExporter::generateEncryptionKey();

                FFMpeg::open($request->file('video_file'))
                    ->exportForHLS()
                    ->withEncryptionKey($key)
                    ->addFormat($lowFormat, function(HLSVideoFilters $filters){
                        $filters->resize(1280, 720);
                    })->addFormat($highFormat)
                    ->toDisk('public')
                    ->save('courses/videos/' . $item->id . '/content.m3u8');

                $item->info = [
                    'video' => 'public/courses/videos/' . $item->id . '/content.m3u8',
                    'key' => $key
                ];
                $item->save();

                return response()->json(['msg' => 'Material created successfully.'], 201);
            }catch(\Exception $e){
                Storage::deleteDirectory('public/courses/videos/' . $item->id);

                return response()->json(['msg' => 'Video convert failed.', 'error' => $e->getMessage()], 500);
            }
        }

        return response()->json(['data' => $request->all()], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $item = CourseItem::findOrFail($id);

        return response()->json(['data' => $item]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CourseItem $courseItem)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CourseItem $courseItem)
    {
        //
    }
}
