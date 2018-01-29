<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\UserUpdateRequest;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends ApiController
{

    /**
     * @return User
     */
    public function show()
    {
        return Auth::user();
    }

    /**
     * @param UserUpdateRequest $request
     * @return User
     * @throws \Throwable
     */
    public function update(UserUpdateRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $attributes = $request->only([
                'name',
                'email',
                'country_code',
            ]);

            if (!empty($password = $request->input('password'))) {
                $attributes['password'] = Hash::make($password);
            }

            $user = Auth::user();

            if (!$user->update($attributes)) {
                abort(500);
            }

            return $user;
        });
    }

//    /**
//     * @param Request $request
//     *
//     * @return Collection|ClientImage[]
//     */
//    public function images(Request $request)
//    {
//        /** @var User $user */
//        $user = $request->user();
//
//        return response()->json([
//            'status'   => $this->SUCCESS,
//            'response' => $user->images
//        ], $this->SUCCESS);
//    }
}