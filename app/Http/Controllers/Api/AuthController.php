<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Models\User;
use Auth;
use Validator;

class AuthController extends Controller
{
    public function __construct()
    {
        //
    }

    public function confirmation(Request $request, $id)
    {
        try {
            $id = Crypt::decrypt($id);
        } catch (DecryptException $e) {
            $id = null;
        }

        if (is_null($id)) {
            return $this->res([trans('message.token_invalid')], 500);
        }

        $user = User::find($id);

        $dateNow = \Carbon\Carbon::now();
        $expired = new \Carbon\Carbon($user->expired_email_verified_at);

        if ($dateNow > $expired) {
            return $this->res([trans('message.error_verify')], 422);
        }

        if ($user->email_verified_at) {
            return $this->res([trans('message.email_verified')], 422);
        }

        $trx  = \DB::transaction(function () use ($user) {
            return $user->update([
                'email_verified_at' => date('Y-m-d H:i:s')
            ]);
        });

        if (!$trx) {
            return $this->res([trans('message.msg_error')], 500);
        }

        return $this->res([trans('message.msg_success')], $user);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->validationCustom($validator->errors()->toArray());
        }

        try {
            if ($user = User::where('email', $request->input('email'))->first()) {
                $check = $this->checkUser($request, $user);
                switch ($check['code']) {
                    case -1:
                        $message = trans('message.email_not_verify');

                        break;
                    case -2:
                        $message = trans('message.locked_account', ['minutes' => $check['minutes']]);
                        
                        break;
                    case -3:
                        $message = trans('message.wrong_password');

                        break;
                    case 1:
                        $message = trans('message.success');

                        break;
                }

                $authToken = $check['token'];
                return $this->res([$message], $authToken ? ['token' => $authToken] : 401);
            } else {
                return $this->res([trans('message.user_not_register')], 401);
            }
        } catch (Exception $e) {
            return $this->res($e->getMessage(), 401);
        }
    }

    protected function checkUser($request, $user)
    {
        $now = date('Y-m-d H:i:s');
        $minutes = 0;
        $token   = false;

        if (is_null($user->email_verified_at) || $user->email_verified_at > $now) {
            $code = -1;
        } else if (!$token = JWTAuth::attempt($request->only('email', 'password'))) {
            $minutes = 30;
            $login_attempt = $user->login_attempt ? $user->login_attempt : 0;
            if (!is_null($user->locked_at)) {
                $lockedAt = new \Carbon\Carbon($user->locked_at);
                $dateNow  = \Carbon\Carbon::now();

                $remainingMinute = $minutes - $lockedAt->diffInMinutes($dateNow);

                if ($remainingMinute > 0) {
                    $code = -2;
                    $minutes = $remainingMinute;
                } else {
                    User::where('id', $user->id)->update([
                        'login_attempt' => null,
                        'locked_at' => null
                    ]);

                    $code = -3;
                }
            } else {
                if ($login_attempt >= 5) {
                    User::where('id', $user->id)->update([
                        'locked_at' => $now,
                    ]);

                    $code = -2;
                    $minutes = $minutes;
                } else {
                    User::where('id', $user->id)->update([
                        'login_attempt' => $login_attempt + 1,
                        'login_attempt_at' => $now
                    ]);

                    $code = -3;
                }
            }
        } else {
            User::where('id', $user->id)->update([
                'last_login' => $now,
                'login_attempt' => null,
                'locked_at' => null
            ]);

            $code = 1;
        }

        return [
            'code'  => $code,
            'token' => $token,
            'minutes' => $minutes
        ];
    }
    
    public function logout(Request $request)
    {
        auth()->logout();
        return $this->res([trans('message.success')], null);
    }
}
