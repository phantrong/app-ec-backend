<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class BaseController extends Controller
{
    public function sendResponse(
        $result = null,
        $statusCode = JsonResponse::HTTP_OK,
        $dataError = []
    ): JsonResponse {
        $response = [
            'success' => $statusCode === JsonResponse::HTTP_OK ? true : false,
            'message' => $this->getMessage($statusCode)
        ];

        if (isset($result['message'])) {
            $response['message'] = $result['message'];
        }

        if ($result !== null) {
            if ($statusCode === JsonResponse::HTTP_OK) {
                $response['data'] = $result;
            }

            if ($statusCode !== JsonResponse::HTTP_OK) {
                $response['errorCode'] = $result;
                if ($statusCode !== JsonResponse::HTTP_OK && $dataError) {
                    $response['data'] = $dataError;
                }
            }
        }

        return response()->json($response, $statusCode);
    }

    private function getMessage($statusCode)
    {
        $message = '';

        switch ($statusCode) {
            case JsonResponse::HTTP_UNAUTHORIZED:
                $message = 'Unauthorized';
                break;
            case JsonResponse::HTTP_FORBIDDEN:
                $message = 'Permission denied';
                break;
            case JsonResponse::HTTP_BAD_REQUEST:
                $message = 'Bad Request';
                break;
            case JsonResponse::HTTP_UNPROCESSABLE_ENTITY:
                $message = 'Validation errors';
                break;
            case JsonResponse::HTTP_NOT_FOUND:
                $message = 'Not found data';
                break;
            case JsonResponse::HTTP_NOT_ACCEPTABLE:
                $message = 'not acceptable';
                break;
            case JsonResponse::HTTP_INTERNAL_SERVER_ERROR:
                $message = 'System error';
                break;
            default:
                $message = 'Success';
        }
        return $message;
    }

    public function sendResponseData(
        $result = null,
        $statusCode = JsonResponse::HTTP_OK,
        $dataError = []
    ): JsonResponse {
        $response = [
            'success' => $statusCode === JsonResponse::HTTP_OK ? true : false,
            'message' => $this->getMessage($statusCode)
        ];

        if ($result !== null) {
            if ($statusCode === JsonResponse::HTTP_OK) {
                $response['data'] = ['data' => $result];
            }

            if ($statusCode !== JsonResponse::HTTP_OK) {
                $response['errorCode'] = $result;
                if ($statusCode !== JsonResponse::HTTP_OK && $dataError) {
                    $response['data'] = $dataError;
                }
            }
        }

        return response()->json($response, $statusCode);
    }

    public function sendError(Exception $e): JsonResponse
    {
        Log::error($e);
        $response = [
            "success" => false,
            // "file" => $e->getFile(),
            // "line" => $e->getLine(),
            "messages" => 'System error',
            // 'error' => $e->getMessage()
        ];
        return response()->json($response, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }
}
