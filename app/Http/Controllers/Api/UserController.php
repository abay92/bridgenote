<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use App\Models\User;
use App\Mail\Notification;
use Validator;
use Auth;

class UserController extends Controller
{
    public function show(Request $request)
    {
        return $this->res([trans('message.success')], Auth::user());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:8',
            'name'      => 'required'
        ]);

        if ($validator->fails()) {
            return $this->validationCustom($validator->errors()->toArray());
        }

        $data = $request->only('email', 'password', 'name');
        $data['password'] = Hash::make($data['password']);
        $data['expired_email_verified_at'] = date("Y-m-d H:i:s", strtotime("+30 minutes"));

        $trx  = \DB::transaction(function () use ($data) {
            $model = User::create($data);

            if ($model) {
                try {
                    Mail::to($data['email'])->send(new Notification(
                        'Konfirmasi Akun',
                        'Silahkan klik link ini ' . url('/api/auth/confirmation/' . Crypt::encrypt($model->id))
                    ));
                } catch (\Exception $e) {
                    //
                }
            }

            return $model;
        });

        if (!$trx) {
            return $this->res([trans('message.msg_error')], 500);
        }

        $trx->encrypt_id = Crypt::encrypt($trx->id);

        return $this->res([trans('message.msg_success')], $trx);
    }

    public function update(Request $request)
    {
        $user =  Auth::user();

        $validator = Validator::make($request->all(), [
            'email'     => 'required|email|unique:users,email,' . $user->id,
            'password'  => 'nullable|min:8',
            'name'      => 'required'
        ]);

        if ($validator->fails()) {
            return $this->validationCustom($validator->errors()->toArray());
        }

        $model = User::find($user->id);
        if (!$model) {
            return $this->res(trans('message.data_not_found'), 500);
        }

        $data = $request->only('email', 'name');

        if ($request->has('password') && ($request->password != '' || $request->password != null)) {
            $data['password'] = Hash::make($request->password);
        }

        $trx  = \DB::transaction(function () use ($model, $data) {
            return $model->update($data);
        });

        if (!$trx) {
            return $this->res([trans('message.msg_error')], 500);
        }

        return $this->res([trans('message.msg_success')], $trx);
    }
}
