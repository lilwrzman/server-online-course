<?php

namespace App\Console\Commands;

use FFMpeg\Format\Video\X264;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class ProcessVideoUpload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-video-upload {video_uniqid} {video_extention}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $video_uniqid = $this->argument('video_uniqid');
        $video_extention = $this->argument('video_extention');
        $inputFilePath = $video_uniqid . '.' . $video_extention;
        $outputPath = "videos/{$video_uniqid}/{$video_uniqid}.m3u8";

        try{
            Log::info("Starting HLS export for video: {$inputFilePath}");

            FFMpeg::fromDisk('uploads')
                ->open($inputFilePath)
                ->exportForHLS()
                ->withRotatingEncryptionKey(function($filename, $contents) use ($video_uniqid){
                    Storage::disk('secrets')->put($video_uniqid . '/' . $filename, $contents);
                    $keyPath = Storage::disk('secrets')->url($video_uniqid . '/' . $filename);
                    return $keyPath;
                })
                ->toDisk('public')
                ->save($outputPath);

            Log::info("HLS export completed for video: {$video_uniqid}, output saved at: {$outputPath}");
            $this->info($outputPath);
        } catch (\Exception $e) {
            Log::error("Failed to process video: {$inputFilePath}, error: " . $e->getMessage());
            $this->error('Failed to process video: ' . $e->getMessage());
        }

    }
}
