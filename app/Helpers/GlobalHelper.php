<?php

if (!function_exists('res')) {
    function res($param1, $param2 = null)
    {
        return response()->json([
            'message' => $param1,
            'data'    => is_numeric($param2) ? null : $param2
        ], is_numeric($param2) ? $param2 : 200);
    }
}
