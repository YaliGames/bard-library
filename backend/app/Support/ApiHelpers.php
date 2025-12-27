<?php

namespace App\Support;

class ApiHelpers
{
    public static function success($data = null, string $message = 'OK', int $status = 200)
    {
        return response()->json(['code' => 0, 'data' => $data, 'message' => $message], $status);
    }

    public static function error(string $message = 'Error', int $status = 500, $data = null)
    {
        return response()->json(['code' => $status, 'data' => $data, 'message' => $message], $status);
    }
}
