<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Rules\Password;

class UserController extends Controller
{
    public function register(Request $request)
    {
        try {
            $rules = [
                'name' =>   ['required', 'string', 'max:255'],
                'phone' =>   ['nullable', 'string', 'max:255'],
                'username' =>   ['required', 'string', 'max:255', 'unique:users'],
                'email' =>   ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' =>   ['required', 'string', new Password],
            ];

            $validated = Validator::make($request->all(), $rules);

            if ($validated->fails()) {
                return ResponseFormatter::error(
                    $validated->errors(),
                    'Permintaan Data tidak sesuai.',
                    422
                );
            }

            User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password)
            ]);

            $user = User::where('email', $request->email)->first();

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success(
                [
                    'access_token' => $tokenResult,
                    'token_type' => 'Bearer',
                    'user' => $user
                ],
                'Proser pendaftaran user berhasil'
            );
        } catch (Exception $e) {
            return ResponseFormatter::error(
                null,
                (env('APP_DEBUG')) ?  $e->getMessage() : 'Terjadi Kesalahan',
                500
            );
        }
    }

    public function login(Request $request)
    {
        try {
            $rules = [
                'email' =>   ['required', 'email'],
                'password' =>   ['required'],
            ];

            $validated = Validator::make($request->all(), $rules);
            if ($validated->fails()) {
                return ResponseFormatter::error(
                    $validated->errors(),
                    'Permintaan Data tidak sesuai.',
                    422
                );
            }

            $crendentials = request(['email', 'password']);
            if (!Auth::attempt($crendentials)) {
                return ResponseFormatter::error(
                    null,
                    "Authenticated Failed!",
                    401
                );
            }

            $user = User::where('email', $request->email)->first();

            if (!Hash::check($request->password, $user->password, [])) {
                return ResponseFormatter::error(
                    null,
                    "Password salah!",
                    401
                );
            }

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success(
                [
                    'access_token' => $tokenResult,
                    'token_type' => 'Bearer',
                    'user' => $user
                ],
                'Login berhasil!'
            );
        } catch (Exception $e) {
            return ResponseFormatter::error(
                null,
                (env('APP_DEBUG')) ?  $e->getMessage() : 'Terjadi Kesalahan',
                500
            );
        }
    }

    public  function fetch(Request $request)
    {
        try {
            $user = $request->user();

            return $request->user();
            if (!$user) {
                return ResponseFormatter::error(
                    null,
                    "Authenticated Failed!",
                    401
                );
            }

            return ResponseFormatter::success(
                $request->user(),
                'Data user berhasil diambil.'
            );
        } catch (Exception $e) {
            return ResponseFormatter::error(
                null,
                (env('APP_DEBUG')) ?  $e->getMessage() : 'Terjadi Kesalahan',
                500
            );
        }
    }

    public function update(Request $request)
    {
        try {
            $rules = [
                'name' =>   ['required', 'string', 'max:255'],
                'phone' =>   ['nullable', 'string', 'max:255']
            ];

            $validated = Validator::make($request->all(), $rules);

            if ($validated->fails()) {
                return ResponseFormatter::error(
                    $validated->errors(),
                    'Permintaan Data tidak sesuai.',
                    422
                );
            }

            $data = $request->all();

            $user = User::find(Auth::user()->id);
            $user->update($data);

            return ResponseFormatter::success(
                $user,
                'Data user berhasil di update.'
            );
        } catch (Exception $e) {
            return ResponseFormatter::error(
                null,
                (env('APP_DEBUG')) ?  $e->getMessage() : 'Terjadi Kesalahan',
                500
            );
        }
    }

    public function logout(Request $request)
    {

        try {
            $token = $request->user()->currentAccessToken()->delete();
            return ResponseFormatter::success(
                null,
                'Logout berhasil!'
            );
        } catch (Exception $e) {
            return ResponseFormatter::error(
                null,
                (env('APP_DEBUG')) ?  $e->getMessage() : 'Terjadi Kesalahan',
                500
            );
        }
    }
}
