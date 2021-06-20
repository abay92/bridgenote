<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\{
    User,
    UserPosition
};
use Validator;

class UserPositionController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string',
            'sort_type' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer',
        ]);
        
        if ($validator->fails()) {
            return $this->validationCustom($validator->errors()->toArray());
        }

        $query = UserPosition::latest('created_at')
            ->onSearch($request->search)
            ->onSort($request->sort, $request->sort_type)
            ->onPaginate($request->page, $request->per_page);

        return $this->res(trans('message.success'), $query);
    }

    public function show(Request $request, $id)
    {
        $data = UserPosition::find($id);
        
        if (!$data) {
            return $this->res(trans('message.data_not_found'), 500);
        }

        return $this->res(trans('message.success'), $data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());
        if ($validator->fails()) {
            return $this->validationCustom($validator->errors()->toArray());
        }

        $data = $request->only('user_id', 'status', 'position');
        
        $trx  = \DB::transaction(function () use ($data) {
            return UserPosition::create($data);
        });

        if (!$trx) {
            return $this->res(trans('message.msg_error'), 500);
        }

        return $this->res(trans('message.msg_success'), $trx);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), $this->rules($id));

        if ($validator->fails()) {
            return $this->validationCustom($validator->errors()->toArray());
        }
        
        $model = UserPosition::where('user_id', $id)->first();
        
        if (!$model) {
            return $this->res(trans('message.data_not_found'), 500);
        }
        
        $data = $request->only('status', 'position');

        $trx  = \DB::transaction(function () use ($id, $data) {
            return UserPosition::where('user_id', $id)->update($data);
        });

        if (!$trx) {
            return $this->res(trans('message.msg_error'), 500);
        }

        $model = UserPosition::where('user_id', $id)->first();

        return $this->res(trans('message.msg_success'), $model);
    }

    public function destroy(Request $request, $id)
    {
        $model = UserPosition::where('user_id', $id)->first();
        
        if (!$model) {
            return $this->res(trans('message.data_not_found'), 500);
        }

        $trx = \DB::transaction(function () use ($id) {
            return UserPosition::where('user_id', $id)->delete();
        });

        if (!$trx) {
            return $this->res(trans('message.msg_error'), 500);
        }

        return $this->res(trans('message.delete_success'));
    }

    public static function rules($id = 0)
    {
        $validate = [
            'status' => 'required|in:active,inactive',
            'position' => 'required'
        ];

        if (!$id) {
            $validate['user_id'] = [
                'required',
                'integer',
                Rule::in(User::all()->pluck('id')),
                Rule::unique('user_positions')
            ];
        }

        return $validate;
    }
}
