<?php

namespace App\Console\Commands;

use FFMpeg\Format\Video\X264;
use Illuminate\Console\Command;
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
        // $lowFormat = (new X264('aac'))->setKiloBitrate(500);
        // $highFormat = (new X264('aac'))->setKiloBitrate(1000);
        $outputPath = "videos/{$video_uniqid}/{$video_uniqid}.m3u8";

        FFMpeg::fromDisk('uploads')
            ->open($video_uniqid . '.' . $video_extention)
            ->exportForHLS()
            ->withRotatingEncryptionKey(function($filename, $contents) use ($video_uniqid){
                Storage::disk('secrets')->put($video_uniqid . '/' . $filename, $contents);
                $keyPath = Storage::disk('secrets')->url($video_uniqid . '/' . $filename);
                return $keyPath;
            })
            // ->addFormat($lowFormat)
            // ->addFormat($highFormat)
            ->toDisk('public')
            ->save($outputPath);

        $this->info($outputPath);
    }
}
