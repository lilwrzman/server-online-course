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
    protected $signature = 'app:process-video-upload {fileName} {folderName}';

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
        $fileName = $this->argument('fileName');
        $folderName = $this->argument('folderName');
        $lowFormat = (new X264('aac'))->setKiloBitrate(500);
        $highFormat = (new X264('aac'))->setKiloBitrate(1000);
        $outputPath = "videos/{$folderName}/{$fileName}.m3u8";

        FFMpeg::fromDisk('uploads')
            ->open($fileName)
            ->exportForHLS()
            ->withRotatingEncryptionKey(function($filname, $contents) use ($folderName){
                Storage::disk('secrets')->put($folderName . '/' . $filname, $contents);
                $keyPath = Storage::disk('secrets')->url($folderName . '/' . $filname);
                return $keyPath;
            })
            ->addFormat($lowFormat)
            ->addFormat($highFormat)
            ->toDisk('public')
            ->save($outputPath);

        $this->info($outputPath);
    }
}
