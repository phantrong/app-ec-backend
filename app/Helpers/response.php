<?php

function responseArrError(int $status, $errorCode = [], $dataError = [])
{
    return [
        'errorCode' => $errorCode,
        'status' => $status,
        'data' => $dataError,
    ];
}
