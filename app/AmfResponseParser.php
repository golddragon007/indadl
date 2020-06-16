<?php

namespace App;

use Illuminate\Support\Arr;
use InvalidArgumentException;

class AmfResponseParser
{
    public function parseVideoData(array $response)
    {
        $tokens = Arr::get($response, 'data.filesh', []);

        $rawUrls = $this->getAvailableRawUrls($response);

        if (empty($rawUrls)) {
            return $this->getFallback($response);
        }

        $urls = collect($rawUrls)->map(function ($url, $resolution) use ($tokens) {
            $token = Arr::get($tokens, $resolution);

            if (str_contains($url, '?')) {
                return "$url&token=$token";
            }

            return "$url?token=$token";
        });

        return new VideoData($urls->toArray());
    }

    protected function getAvailableRawUrls($response)
    {
        $resolutions = [];

        // Old format, keep for backwards compatibility
        $files = Arr::get($response, 'data.flv_files');

        if (empty($files)) {
            // New format
            $files = Arr::get($response, 'data.video_files');
        }

        if (! $files) {
            return null;
        }

        foreach ($files as $file) {
            if (mb_strpos($file, '.360.mp4') !== false) {
                $resolutions['360'] = $file;
            }

           if (mb_strpos($file, '.720.mp4') !== false) {
               $resolutions['720'] = $file;
            }

            if (mb_strpos($file, '.1080.mp4') !== false) {
                $resolutions['1080'] = $file;
            }
        }

        return $resolutions;
    }

    protected function getFallback($response)
    {
        $file = array_get($response, 'data.video_file');

        if (! $file) {
            throw new InvalidArgumentException('Video URL could not be retrieved from Indavideo API.');
        }

        $token = (array) array_get($response, 'data.filesh', []);
        $token = array_shift($token);

        if (! $token) {
            throw new InvalidArgumentException('Video URL could not be retrieved from Indavideo API.');
        }

        return $file . "?token=$token";
    }
}
