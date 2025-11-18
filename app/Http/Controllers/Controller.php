<?php

namespace App\Http\Controllers;

abstract class Controller
{
    // Common Success Response
    public function success($message = 'Success', $data = null, $code = 200)
    {
        return response()->json([
            'status'  => true,
            'message' => $message,
            'data'    => $data
        ], $code);
    }

    // Common Error Response
    public function error($message = 'Error', $code = 400, $errors = [])
    {
        return response()->json([
            'status'  => false,
            'message' => $message,
            'errors'  => $errors
        ], $code);
    }
}
