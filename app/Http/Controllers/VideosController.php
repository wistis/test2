<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Video;

use Illuminate\Support\Str;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class VideosController extends Controller
{
    public $status = 5;

    public function index()
    {

        $videos_sql = Video::orderby('id', 'DESC');

            $videos_sql->where('user_id', auth()->user()->id);



        if (request()->has('_limit')) {

            $courses = $videos_sql->paginate((int)request('_limit'));
        } else {
            $courses = $videos_sql->paginate(100);
        }

        $data['videos'] = $courses;

        return $data;
    }

    public function show(Video $video)
    {


        $data['video'] = $video;

        return $data;

    }






    public function getdata()
    {

        if (request('server_key') != '3105') {
            return response()->json(['error' => 1, 'message' => 'Server key error']);

        }
        if (request('method') == 'getfileto_load') {
            return $this->getfileto_load();
        }
        if (request('method') == 'fileuploaded') {
            return $this->fileuploaded();
        }
        if (request('method') == 'videototranscode') {
            return $this->videototranscode();
        }

        if (request('method') == 'set_status') {
            return $this->set_status();
        }
        if (request('method') == 'getnotdel') {
            return $this->getnotdel();
        }

    }

    public function getnotdel()
    {
        $data['keys'] = [];//    Video::where('status', request('status'))->where('server_id',2)->pluck('name','key')->toArray();

        return $data;

    }

    public function set_status()
    {
        $video = Video::where('key', request('key'))->first();
        if (!$video) {

            return response()->json(['error' => 1, 'message' => 'Video not find']);
        } else {
            //  $video->server_id=request('server_id');
            $video->status = request('status');
            if (request()->has('error_mess')) {
                $video->error_mess = request('error_mess');
            }
            if (request()->has('encoding')) {
                $video->encoding = request('encoding');
            }
            if (request('status') == 2) {

                $video->tstart = time();
            }
            if (request('status') == 4) {

                $video->file = '/files/converted/' . $video->user_id . '/' . $video->key . '/video.m3u8';
                $video->tend = time();
            }

            if (request('status') == 5) {

                $video->file = '/files/converted/' . $video->user_id . '/' . $video->key . '/video.m3u8';
                $video->tend = time();

                $url = 'https://data.coursonix.com/files/converted/' . $video->user_id . '/' . $video->key . '/video_1080p.m3u8';

                try {
                    $timer = 0;
                    $file = file_get_contents($url);
                    $res_arr = explode("\n", $file);
                    foreach ($res_arr as $k => $str) {
                        if (Str::contains($str, '#EXTINF:')) {
                            $str = trim($str, ',');
                            $explo = explode(':', $str);
                            if (isset($explo[1])) {
                                $timer += $explo[1];
                            }

                        }
                    }
                    $video->timer = $timer;
                } catch (\Exception $e) {

                }

            }

            $video->updated_at_2 = date('Y-m-d H:i:s', strtotime('+3 hours'));
            $video->save();

        }

    }

    public function videototranscode()
    {

        if (request('server_id') == 1) {
            $video = Video::where('status', 1)->orderby('id', 'desc')->first();

            $videowork = Video::whereIN('status', [2, 3])/*->where('server_id', request('server_id'))*/ ->first();
            if ($videowork) {
                return response()->json([]);
            }
        }
        if (request('server_id') == 2) {
            $video = Video::where('status', 1)->first();

            $videowork = Video::whereIN('status', [2, 3])/*->where('server_id', request('server_id'))*/ ->count();
            if ($videowork > 8) {
                return response()->json([]);
            }
        }
        $data['video'] = $video;

        return response()->json($data);
    }

    public function fileuploaded()
    {
        $video = Video::where('status', 0)->where('key', request('key'))->first();
        if (!$video) {

            return response()->json(['error' => 1, 'message' => 'Video not find']);
        } else {
            $video->ext = request('ext');
            $video->status = 1;
            $video->save();
        }

        return response()->json($video);

    }

    public function getfileto_load()
    {

        $video = Video::where('status', 0)->where('key', request('key'))->first();
        if (!$video) {

            return response()->json(['error' => 1, 'message' => 'Video not find']);
        }

        return response()->json($video);

    }

    /*public function streem(){

        $config = [
            'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
            'ffprobe.binaries' => '/usr/bin/ffprobe',
            'timeout'          => 3600,
            'ffmpeg.threads'   => 12,
        ];

        $log = new Logger('FFmpeg_Streaming');
        $log->pushHandler(new StreamHandler(storage_path('ffmpeg-streaming.log')));

        $ffmpeg = \Streaming\FFMpeg::create($config, $log);
        $video = $ffmpeg->open(public_path('1.avi'));
        $video->dash()
            ->x264()
            ->autoGenerateRepresentations()
            ->save(public_path('mvideo/1.mpd'));

    }*/
}
