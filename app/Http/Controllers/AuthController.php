<?php

namespace App\Http\Controllers;

use App\Mail\VerificationSuccessEmail;
use App\Mail\VerifyEmail;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|max:255',
            'username' => 'required|string|max:14|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:16|confirmed',
            'referral' => 'nullable|string',
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()]);
        }

        $user = User::create([
            "email" => $request->input("email"),
            "username" => $request->input("username"),
            "password" => $request->input("password"),
            "role" => 'Student',
            "status" => "Non-Active"
        ]);

        if($request->input('referral')){
            $corporate_id = Referral::where('code', '=', $request->input('referral'))->get(['corporate_id'])->first();
            $corporate_id = $corporate_id['corporate_id'];
            $user->corporate_id = $corporate_id;
            $user->save();
        }

        $user->info = ["fullname" => $request->input("fullname")];
        $user->verification_token = Str::random(40);
        $user->save();

        Mail::to($user->email)->send(new VerifyEmail($user));

        return response()->json(['status' => true, 'msg' => 'Registered successfully.'], 201);
    }

    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if(!hash_equals($hash, $user->verification_token)){
            return response()->json(['error' => 'Invalid verification link'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 200);
        }

        if ($user->markEmailAsVerified()) {
            $user->status = 'Active';
            $user->save();
            Mail::to($user->email)->send(new VerificationSuccessEmail($user));
        }

        return view('verification_response', ['user' => $user]);
    }

    public function verifySuccess()
    {
        return view('verification_success');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()], 401);
        }

        $credentials = $request->only('email', 'password');
        if(Auth::attempt($credentials)){
            $user = Auth::user();

            if($user->role != $request->input('role')){
                return response()->json([
                    'status' => false,
                    'msg' => 'Unauthorized!'
                ]);
            }

            if ($user->role == "Student" && !$user->hasVerifiedEmail()) {
                return response()->json([
                    'status' => false,
                    'msg' => 'Silakan verifikasi email Anda terlebih dahulu.'
                ]);
            }

            if($user->status != 'Active'){
                return response()->json([
                    'status' => false,
                    'msg' => 'Akun anda tidak aktif, hubungi admin untuk mengaktifkan akun anda!'
                ]);
            }

            if($user->role == 'Student'){
                $userData = json_encode([
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                    'username' => $user->username,
                    'fullname' => $user->info['fullname'],
                    'token' => $user->createToken('Personal Access Client')->accessToken
                ]);
            }else{
                $userData = json_encode([
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                    'username' => $user->username,
                    'token' => $user->createToken('Personal Access Client')->accessToken
                ]);
            }

            return response()->json(['status' => true, 'userData' => $userData]);
        }

        if(User::where('email', $request->email)->first()){
            return response()->json([
                'status' => false,
                'error' => [
                    'password' => 'Password yang dimasukkan salah.'
                ]
            ]);
        }

        return response()->json([
            'status' => false,
            'error' => [
                'password' => 'Password yang dimasukkan salah.',
                'email' => 'Email yang dimasukkan salah.'
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        $user->token()->revoke();

        return response()->json([
            'status' => true,
            'msg' => 'User logged out successfully.'
        ]);
    }
}
