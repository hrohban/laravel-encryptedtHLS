<?php

namespace App\Console\Commands;


use FFMpeg\Format\Video\X264;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use ProtoneMedia\LaravelFFMpeg\Exporters\HLSExporter;

class CreateHLSMovie extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movie:hls {movieName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $movieName = $this->argument('movieName');
        set_time_limit(0);
        $lowBitrate = (new X264)->setKiloBitrate(250);
        //$midBitrate = (new X264)->setKiloBitrate(500);
        //$highBitrate = (new X264)->setKiloBitrate(1000);
        //$superBitrate = (new X264)->setKiloBitrate(1500);
        $encryptionKey = HLSExporter::generateEncryptionKey();
            
        FFMpeg::fromDisk('videos')
        ->open($movieName)
        ->withRotatingEncryptionKey(function ($filename, $contents) {
        // use this callback to store the encryption keys
        Storage::disk('secrets')->put($filename, $contents);
    })->addFormat($lowBitrate, function($media) {
        $media->addFilter('scale=640:480');
    })->toDisk('public')->save('videos/test.m3u8');

  
        return 'done!';
    }
}
