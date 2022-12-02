<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\UploadFileRequest;
use App\Services\UploadService;
use Illuminate\Http\Request;

class UploadController extends BaseController
{
    private UploadService $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    public function uploadSingleFile(UploadFileRequest $request)
    {
        try {
            $links = $this->uploadService->uploadFileStorage($request->image);
            return $this->sendResponse(asset($links));
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function deleteFile(Request $request)
    {
        try {
            return $this->uploadService->deleteListFile($request->image);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }
}
