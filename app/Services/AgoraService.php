<?php

namespace App\Services;

use App\Agora\AgoraDynamicKey\RtcTokenBuilder;
use App\Enums\EnumLiveStream;
use Http\Client;
use Illuminate\Support\Facades\Http;

class AgoraService
{
    const TIME_EXPIRED_RESOURCE_ID = 24;

    public function generateToken($channelName)
    {
        $appID = config('services.agora_app_id');
        $appCertificate = config('services.agora_app_certificate');
        $user = null;
        $role = RtcTokenBuilder::ROLE_ATTENDEE;
        $expireTimeInSeconds = config('services.agora_time_expire');
        $currentTimestamp = now()->getTimestamp();
        $privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;

        return RtcTokenBuilder::buildTokenWithUserAccount(
            $appID,
            $appCertificate,
            $channelName,
            $user,
            $role,
            $privilegeExpiredTs
        );
    }

    public function generateCredential()
    {
        $customerKey = config('services.agora_customer_id');
        $customerSecret = config('services.agora_customer_secret');
        $credentials = $customerKey.":".$customerSecret;
        $base64Credentials = base64_encode($credentials);
        $auth = "Basic ".$base64Credentials;
        $response = Http::withHeaders([
            'Content-type' => 'application/json',
            'Authorization' => $auth
        ])->GET('https://api.agora.io/dev/v1/projects', [
        ]);
        $response = json_decode($response->body());
        if ($response->success === false) {
            return false;
        }
        return $base64Credentials;
    }

    public function getResourceId($livestream)
    {
        $timeExpired = self::TIME_EXPIRED_RESOURCE_ID;
        $auth = $this->generateCredential();
        $uid = $livestream->id;
        $uid = "" . ++$uid;
        $agoraAppId = config('services.agora_app_id');
        $response = Http::withHeaders([
            'Content-type' => 'application/json',
            'Authorization' => "Basic $auth"
        ])->POST("https://api.agora.io/v1/apps/$agoraAppId/cloud_recording/acquire", [
            "cname" => $livestream->channel_name,
            "uid" => $uid,
            "clientRequest" => [
                "resourceExpiredHour" => $timeExpired
            ]
        ]);
        $response = json_decode($response->body());
        return $response;
    }

    public function startRecord($livestream, $resourceId)
    {
        $auth = $this->generateCredential();
        $agoraAppId = config('services.agora_app_id');
        $uid = $livestream->id;
        $uid = "" . ++$uid;
        $response = Http::withHeaders([
            'Content-type' => 'application/json',
            'Authorization' => "Basic $auth"
        ])->POST("https://api.agora.io/v1/apps/$agoraAppId/cloud_recording/resourceid/$resourceId/mode/mix/start", [
            "cname" => $livestream->channel_name,
            "uid" => $uid,
            "clientRequest" => [
                "token" => $livestream->token,
                "recordingConfig" => [
                    "maxIdleTime" => 600,
                    "streamTypes" => 2,
                    "channelType" => 0,
                    "videoStreamType" => 0,
                    "transcodingConfig" => [
                        "height" => 500,
                        "width" => 640,
                        "bitrate" => 500,
                        "fps" => 15,
                        "mixedVideoLayout" => 1,
                        "backgroundColor" => "#FFFFFF"
                    ]
                ],
                "recordingFileConfig" => [
                    "avFileType" => ["hls", "mp4"]
                ],
                "storageConfig" => [
                    "secretKey" => config('services.aws_secret_key'),
                    "vendor" => 1,
                    "region" => 10,
                    "bucket" => config('services.aws_bucket'),
                    "accessKey" => config('services.aws_access_key'),
                    "fileNamePrefix" => [
                        config('services.folder_livestream_1'),
                        config('services.folder_livestream_2')
                    ]
                ]
            ]
        ]);
        return json_decode($response->body());
    }

    public function stopRecord($livestream)
    {
        $auth = $this->generateCredential();
        $agoraAppId = config('services.agora_app_id');
        $uid = $livestream->id;
        $uid = "" . ++$uid;
        $response = Http::withHeaders([
            'Content-type' => 'application/json',
            'Authorization' => "Basic $auth"
        ])->POST(
            "https://api.agora.io/v1/apps/$agoraAppId/cloud_recording/resourceid/" .
            $livestream->resource_id_cloud . "/sid/$livestream->start_id_cloud/mode/mix/stop",
            [
                "cname" => $livestream->channel_name,
                "uid" => $uid,
                "clientRequest" => [
                    "async_stop" => false
                ]
            ]
        );
        $response = json_decode($response->body())->serverResponse;
        $files = $response->fileList;
        $fileMp4 = config('services.link_s3').$files[EnumLiveStream::INDEX_FILE_MP4]->fileName;
        return $livestream->update([
            'url_video' => $fileMp4
        ]);
    }
}
