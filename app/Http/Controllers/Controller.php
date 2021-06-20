<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function res($param1, $param2 = null)
    {
        return response()->json([
            'message' => $param1,
            'data'    => is_numeric($param2) ? null : $param2
        ], is_numeric($param2) ? $param2 : 200);
    }

    public function validationCustom($validator)
    {
        $messages = [];
        foreach ($validator as $key => $message) {
            foreach ($message as $error) {
                $messages[] = $error;
            }
        };

        return $this->res($messages, 422);
    }
}
