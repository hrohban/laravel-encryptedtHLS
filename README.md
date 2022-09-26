

# Installation

### I Created a project with Laravel framework, FFmpeg software and Encrypted HLS to saved movie in my host with HLS and play movie for customers and protected to download movies by Customers
---
## ` Step1`
- You can install the laravel via composer:
```bash
composer create-project laravel/laravel:^8.* movie_hls

cd movie_hls
 
php artisan serve
```
## ` Step2`
_FFmpeg is the leading multimedia framework, able to decode, encode, transcode, mux, demux, stream, filter and play pretty much anything that humans and machines have created. It supports the most obscure ancient formats up to the cutting edge. No matter if they were designed by some standards committee, the community or a corporation. It is also highly portable: FFmpeg compiles, runs, and passes our testing infrastructure FATE across Linux, Mac OS X, Microsoft Windows, the BSDs, Solaris, etc. under a wide variety of build environments, machine architectures, and configurations._


- You must download and installl the [ffmpeg software](https://ffmpeg.org/download.html)on your OS.

- Then you can install the package via composer:

```bash
composer require pbmedia/laravel-ffmpeg
```
Add the Service Provider and Facade to your ```app.php``` config file if you're not using Package Discovery.

```php
// config/app.php
'providers' => [
    ...
    ProtoneMedia\LaravelFFMpeg\Support\ServiceProvider::class,
    ...
];
'aliases' => [
    ...
    'FFMpeg' => ProtoneMedia\LaravelFFMpeg\Support\FFMpeg::class
    ...
];
```

Publish the config file using the artisan CLI tool:

```bash
php artisan vendor:publish --provider="ProtoneMedia\LaravelFFMpeg\Support\ServiceProvider"
```
- Open the .env file in your Laravel package and add or edit FFmpeg Path:
```bash
FFMPEG_BINARIES=C:/ffmpeg/bin/ffmpeg.exe
FFPROBE_BINARIES=C:/ffmpeg/bin/ffprobe.exe
```
## ` step3`
You can confgure some new desks as a storage in config/filysystems.php 
```php
'videos' => [
            'driver' => 'local',
            'root' => storage_path('videos'),
            
        ],
        'secrets' => [
            'driver' => 'local',
            'root' => storage_path('secrets'),
            
        ],

```
If we face laravel storage link permission denied. So, this tutorial will help us to give permission for linking public storage directory in laravel app.
```bash
php artisan storage:link
```
That creates a symlink from public/storage to storage/app/public for we and that’s all there is to it. Now any file in /storage/app/public can be accessed via a link.


## ` step4`
## **Create a custom command**
- Use the make:command command to create a new command. Simply pass in the command name, like so:
```bash
php artisan make:command CreateHLSMovie
```
This command creates a file named CreateHLSMovie.php, named after the command name, in a newly created Commands directory in the Console folder.
- update the ＄signature property of the command, like this:
```php
protected ＄signature = 'movie:hls {movieName}';
```

You can set your main code that is convert your movie to hls in handle function
```php
public function handle()
    {
        $movieName = $this->argument('movieName');
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
```
## ` step5`
_Protecting your HLS encryption keys_
__To make working with encrypted HLS even better, we've added a DynamicHLSPlaylist class that modifies playlists on-the-fly and specifically for your application. This way, you can add your authentication and authorization logic. As we're using a plain Laravel controller, you can use features like Gates and Middleware._

_In this example, we've saved the HLS export to the public disk, and we've stored the encryption keys to the secrets disk, which isn't publicly available. As the browser can't access the encryption keys, it won't play the video. Each playlist has paths to the encryption keys, and we need to modify those paths to point to an accessible endpoint._

_This implementation consists of two routes. One that responses with an encryption key and one that responses with a modified playlist. The first route (video.key) is relatively simple, and this is where you should add your additional logic._

_The second route (video.playlist) uses the DynamicHLSPlaylist class. Call the dynamicHLSPlaylist method on the FFMpeg facade, and similar to opening media files, you can open a playlist utilizing the fromDisk and open methods. Then you must provide three callbacks. Each of them gives you a relative path and expects a full path in return. As the DynamicHLSPlaylist class implements the Illuminate\Contracts\Support\Responsable interface, you can return the instance._

_The first callback (KeyUrlResolver) gives you the relative path to an encryption key. The second callback (MediaUrlResolver) gives you the relative path to a media segment (.ts files). The third callback (PlaylistUrlResolver) gives you the relative path to a playlist._

_Now instead of using Storage::disk('public')->url('demo.m3u8') to get the full url to your primary playlist, you can use route('video.playlist', ['playlist' => 'demo.m3u8']). The DynamicHLSPlaylist class takes care of all the paths and urls._
```php
Route::get('/video/secret/{key}', function ($key) {
    return Storage::disk('secrets')->download($key);
})->name('video.key');

Route::get('/video/{playlist}', function ($playlist) {
    return FFMpeg::dynamicHLSPlaylist()
        ->fromDisk('public')
        ->open($playlist)
        ->setKeyUrlResolver(function ($key) {
            return route('video.key', ['key' => $key]);
        })
        ->setMediaUrlResolver(function ($mediaFilename) {
            return Storage::disk('public')->url($mediaFilename);
        })
        ->setPlaylistUrlResolver(function ($playlistFilename) {
            return route('video.playlist', ['playlist' => $playlistFilename]);
        });
})->name('video.playlist');
```
## ` step6`
Using Video.js to play HLS in any browser
```html
<video id="my-video" 
    class="video-js vjs-big-play-centered" controls  controlsList="nodownload"  preload="auto" data-setup='{"fluid":true}'  width="700" height="300" >
     <source src="{{ route('video.playlist',['playlist'=> 'test.m3u8'])  }}"  type="application/x-mpegURL">
 </video>
```

## ` step7`
If your video is not loaded, add the port to  APP_URL in .env file.
```bash
APP_URL=http://localhost:8000
```

## ` step8`
 Run this command in your terminal
 
 demo.mp4=>your video file name
```bash
php artisan movie:hls demo.mp4
```

---
Created by Hanieh Rohban

Email: h.rohban@gmail.com

GitHub:https://github.com/hrohban
