<?php

namespace App\Services;

use App\Enums\EnumFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class UploadService
{
    const EXTENSION_UN_SUPPORT = ['heic'];

    public function uploadFile($listFile, $type = null, $folder = ''): array
    {
        $folderNotResize = ['profile'];

        $listFile = is_array($listFile) ? $listFile : [$listFile];
        $domain = config('services.link_s3');
        $listLinks = [];
        $visibility = config('services.visibility');
        $size = config('filesystems.image_width_product');
        switch ($type) {
            case EnumFile::IMAGE_AVATAR:
                $size = config('filesystems.image_width_avatar');
                break;
            case EnumFile::IMAGE_BACK_GROUND:
                $size = config('filesystems.image_width_background');
                break;
            default:
                break;
        }
        foreach ($listFile as $file) {
            $filePath = 'lcm/' . now()->format('Y-m-d/');
            if ($folder) {
                $filePath = $folder . '/' . now()->format('Y-m-d/');
            }
            $filePath .= $file->hashName();
            $extension = $file->getClientOriginalExtension();
            if (in_array($extension, self::EXTENSION_UN_SUPPORT)) {
                $extension = 'jpg';
            }

            $exif = @exif_read_data($file);
            if (!empty($exif['Orientation'])) {
                $file = $this->rotateFileImage($exif, $file, $extension);
            }

            if (in_array($folder, $folderNotResize)) {
                $fileImg = Image::make($file);
            } else {
                $fileImg = Image::make($file)->resize($size, $size, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->encode($extension);
            }

            Storage::disk(config('filesystems.cloud'))->put($filePath, $fileImg->stream(), $visibility);
            $listLinks[] = $domain . $filePath;
        }
        return $listLinks;
    }

    public function rotateFileImage($exif, $file, $extension)
    {
        if (preg_match('/jpg|jpeg/i', $extension)) {
            $img = imagecreatefromjpeg($file);
        } elseif (preg_match('/png/i', $extension)) {
            $img = imagecreatefrompng($file);
        } elseif (preg_match('/gif/i', $extension)) {
            $img = imagecreatefromgif($file);
        } else {
            $img = imagecreatefromjpeg($file);
        }

        switch ($exif['Orientation']) {
            case 8:
                $img = imagerotate($img, 90, 0);
                break;
            case 3:
                $img = imagerotate($img, 180, 0);
                break;
            case 6:
                $img = imagerotate($img, -90, 0);
                break;
        }
        return $img;
    }

    public function uploadFileStorage($file)
    {
        $fileName = $file->getClientOriginalName();
        if (!Storage::exists('public/')) {
            Storage::makeDirectory('public'); //creates directory
            @chmod('public/', 0755);
        }
        if (!Storage::exists('public/images')) {
            Storage::makeDirectory('public/images'); //creates directory
            @chmod('public/images/', 0755);
        }
        return $file->storeAs(config('filesystems.folder_image_stripe'), $fileName, 'local');
    }

    public function deleteListFile($listFile)
    {
        $listFile = is_array($listFile) ? $listFile : [$listFile];
        foreach ($listFile as $file) {
            $fileName = str_replace(config('services.link_s3'), '', $file);
            Storage::disk(config('filesystems.cloud'))->delete($fileName);
        }
    }

    public function uploadSingleFile($image)
    {
        $domain = config('services.link_s3');
        $visibility = config('services.visibility');
        $filePath = now()->format('Y-m-d/') . $image->hashName();
        $path = Storage::disk(config('filesystems.cloud'))->put($filePath, $image, $visibility);
        return $domain . $path;
    }
}
