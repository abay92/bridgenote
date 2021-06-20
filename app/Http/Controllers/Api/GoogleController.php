<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Socialite;

class GoogleController extends Controller
{
    public function __construct()
    {
        //
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function redirectTo()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function handleCallback()
    {
        try {
            $user = Socialite::driver('google')->stateless()->user();
            $finduser = User::where('google_id', $user->id)->first();
     
            if ($finduser) {
                $authToken = JWTAuth::fromUser($finduser);
            } else {
                $now = date('Y-m-d H:i:s');
                $newUser = User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id' => $user->id,
                    'email_verified_at' => $now,
                    'password' => Hash::make(config('app.password_default')),
                    'last_login' => $now,
                ]);

                $authToken = JWTAuth::fromUser($newUser);
            }

            return $this->res([trans('message.success')], $authToken ? ['token' => $authToken] : 401);
        } catch (\Exception $e) {
            return $this->res([$e->getMessage()], 500);
        }
    }
}
