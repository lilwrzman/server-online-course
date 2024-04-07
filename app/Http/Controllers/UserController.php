<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function profile()
    {
        $user = Auth::user();
        $user->makeHidden(['id', 'verification_token', 'email_verified_at', 'updated_at', 'corporate_id']);

        return response()->json([
            'status' => true,
            'data' => $user
        ]);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if(!$user->role == 'Superadmin'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $role = $request->query('role');
        $accounts = [];

        if($role){
            $accounts = User::select('id', 'email', 'username', 'info', 'status')
                ->where('role', '=', $role)
                ->get();

            if($role == 'Student'){
                foreach ($accounts as $item) {
                    $item['fullname'] = $item['info']['fullname'];
                }

                $accounts->makeHidden(['info']);
            }
        }

        return response()->json([
            'status' => true,
            'data' => $accounts
        ]);
    }

    public function detail($id)
    {
        $user = Auth::user();
        if(!$user->role == 'Superadmin'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $detail = User::findOrFail($id);
        $detail->makeHidden(["password", "verification_token", "corporate_id", "email_verified_at", "updated_at"]);
        if($detail->role == 'Student'){
            $detail['fullname'] = $detail['info']['fullname'];

            $detail->makeHidden(['info']);
        }

        return response()->json(['status' => true, 'data' => $detail]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        if(!$user->role == 'Superadmin'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

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

        $role = $request->input('role');

        if($role == 'Student'){
            $user = User::create([
                'email' => $request->input('email'),
                'username' => $request->input('username'),
                'password' => 'student123',
                'role' => 'Student',
                'info' => ['fullname' => $request->input('fullname')],
                'status' => 'Active',
                'email_verified_at' => now()
            ]);

            if($user){
                return response()->json([
                    'status' => true,
                    'message' => 'Student berhasil ditambahkan!'
                ]);
            }
        }

        return response()->json([
            'status' => false,
            'message' => 'Gagal menambahkan data!'
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        if(!$user->role == 'Superadmin'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'fullname' => 'required|string|max:255',
            'username' => 'required|string|max:14|unique:users,username,' . $request->input('id'),
            'email' => 'required|string|email|max:255|unique:users,email,' . $request->input('id'),
            'referral' => 'nullable|string',
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()]);
        }

        $user = User::findOrFail($request->input('id'));
        if(!$user){
            return response()->json(['status' => false, 'message' => 'User tidak ditemukan.']);
        }

        if($user->role == 'Student'){
            $user->username = $request->input('username');
            $user->email = $request->input('email');
            $user->info = ['fullname' => $request->input('fullname')];
            $user->save();

            return response()->json(['status' => true, 'message' => 'Berhasil mengubah data.']);
        }
    }

    public function delete(Request $request)
    {
        $user = Auth::user();
        if(!$user->role == 'Superadmin'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()]);
        }

        $user = User::findOrFail($request->input('id'));
        if($user->delete()){
            return response()->json(['status' => true, 'message' => 'Berhasil menghapus data.']);
        }else{
            return response()->json(['status' => false, 'message' => 'Gagal menghapus data.']);
        }
    }
}
