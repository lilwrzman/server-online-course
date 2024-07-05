<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseAccess;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
        if(!$role){
            return response()->json(['status' => false, 'message' => 'Tidak memilih role!'], 401);
        }

        $accounts = User::where('role', '=', $role)->get();

        foreach ($accounts as $item) {
            foreach ($item->info as $key => $value) {
                $item[$key] = $value;
            }

            if($role == 'Corporate Admin'){
                $item['referral_code'] = $item->getReferralCode();
                $item['student_count'] = $item->corporateStudentCount();
            }else if($role == 'Teacher'){
                $item['course_count'] = $item->teacherCourseCount();
            }else if($role == 'Student'){
                if($item->corporate_id){
                    $corporate = User::where('role', '=', 'Corporate Admin')
                        ->where('id', $item->corporate_id)->get(['info'])->first();
                }
                $item['type'] = $item->corporate_id ? 'Mitra ' . $corporate['info']['name'] : 'Umum';
                $item['course_count'] = $item->courseAccessCount();
            }
        }

        $accounts->makeHidden(['corporate_id']);

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

        if($detail->role == 'Student'){
            $detail['my_courses'] = $detail->myCourses()->get(['slug', 'thumbnail', 'title', 'description']);
            $detail['type'] = $detail->corporate_id ? 'Mitra' : 'Umum';
            $detail['corporates'] = User::where('role', '=', 'Corporate Admin')->get(['id', 'info'])->makeVisible(['info']);
        }else if($detail->role == 'Teacher'){
            $detail['my_courses'] = $detail->courses()->get(['slug', 'thumbnail', 'title', 'description', 'items']);
        }else if($detail->role == 'Corporate Admin'){
            $detail['referral'] = $detail->getReferralCode();
            $detail['my_students'] = $detail->corporateStudents()->get();
            foreach($detail['my_students'] as $student){
                foreach($student->info as $key => $value){
                    $student[$key] = $value;
                }

                $student->makeHidden(['info']);
            }
        }

        foreach ($detail->info as $key => $value) {
            $detail[$key] = $value;
        }

        $detail->makeHidden(['info']);

        return response()->json(['status' => true, 'data' => $detail]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        if(!$user->role == 'Superadmin'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $role = $request->input('role');
        $info = [];
        $validate_rules = [];
        $validate_message = [];

        if($role == 'Corporate Admin'){
            $validate_rules = [
                'name' => 'required|string|max:255',
                'address' => 'required|string|max:255',
                'contact' => 'required|string|max:255',
                'username' => 'required|string|unique:users,username',
                'email' => 'required|string|email|max:255|unique:users,email',
                'avatar_file' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
            ];

            $validate_message = [
                'fullname.required' => 'Mohon masukkan nama lengkap.',
                'username.required' => 'Mohon masukkan username.',
                'username.unique' => 'Username sudah digunakan.',
                'email.required' => 'Mohon masukkan email.',
                'email.unique' => 'Email sudah digunakan.'
            ];
        }else if($role == "Student"){
            $validate_rules = [
                'fullname' => 'required|string|max:255',
                'username' => 'required|string|max:14|unique:users,username',
                'email' => 'required|string|email|max:255|unique:users,email',
                'avatar_file' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
            ];

            $validate_message = [
                'fullname.required' => 'Mohon masukkan nama lengkap.',
                'username.required' => 'Mohon masukkan username.',
                'username.unique' => 'Username sudah digunakan.',
                'email.required' => 'Mohon masukkan email.',
                'email.unique' => 'Email sudah digunakan.'
            ];
        }else if($role == "Teacher"){
            $validate_rules = [
                'fullname' => 'required|string|max:255',
                'username' => 'required|string|unique:users,username',
                'email' => 'required|string|email|max:255|unique:users,email',
                'facebook_name' => 'nullable|string|max:255',
                'facebook_url' => 'nullable|url|required_with:facebook_name',
                'instagram_name' => 'nullable|string|max:255',
                'instagram_url' => 'nullable|url|required_with:instagram_name',
                'avatar_file' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
            ];

            $validate_message = [
                'fullname.required' => 'Mohon masukkan nama lengkap.',
                'username.required' => 'Mohon masukkan username.',
                'username.unique' => 'Username sudah digunakan.',
                'email.required' => 'Mohon masukkan email.',
                'email.unique' => 'Email sudah digunakan.'
            ];
        }

        $validator = Validator::make($request->all(), $validate_rules, $validate_message);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()]);
        }

        $field = [
            'email' => $request->input('email'),
            'username' => $request->input('username'),
            'password' => 'User1234',
            'role' => $role,
            'status' => 'Active',
            'corporate_id' => $request->input('corporate_id') ? $request->input('corporate_id') : null,
            'email_verified_at' => now()
        ];

        if($request->hasFile('avatar_file')){
            $avatar = $request->file('avatar_file');
            $avatarPath = $avatar->storeAs(
                'avatars',
                uniqid() . '_' . time() . '.' . $avatar->getClientOriginalExtension(), 'public'
            );

            $field['avatar'] = $avatarPath;
        }

        $user = User::create($field);

        if(!$user){
            return response()->json([
                'status' => false,
                'message' => 'Gagal menambahkan data!'
            ]);
        }

        if($role == 'Corporate Admin'){
            $info = [
                'name' => $request->input('name'),
                'address' => $request->input('address'),
                'contact' => $request->input('contact')
            ];

            $code = User::generateReferralCode();
            $referral = Referral::create([
                'corporate_id' => $user->id,
                'code' => $code
            ]);

            if(!$referral){
                $user->delete();
                return response()->json([
                    'status' => false,
                    'message' => 'Terjadi kesalahan saat pembuatan referral, mohon coba lagi!'
                ]);
            }
        }else if($role == 'Student'){
            $info = ['fullname' => $request->input('fullname')];
        }else if($role == 'Teacher'){
            $info = ['fullname' => $request->input('fullname')];
            $info['social_media'] = [
                [
                    "type" => 'Facebook',
                    "username" => $request->input('facebook_name') ?? '' ,
                    "url" => $request->input('facebook_url') ?? ''
                ], [
                    "type" => 'Instagram',
                    "username" => $request->input('instagram_name') ?? '',
                    "url" => $request->input('instagram_url')?? ''
                ]
            ];
        }

        $user->info = $info;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Akun berhasil ditambahkan!'
        ]);
    }

    public function update(Request $request)
    {
        $user = User::findOrFail($request->input('id'));
        $info = [];
        $role = $user->role;
        $validate_rules = [];
        $validate_message = [];

        if($role == 'Corporate Admin'){
            $validate_rules = [
                'name' => 'required|string|max:255',
                'address' => 'required|string|max:255',
                'contact' => 'required|string|max:255',
                'username' => 'required|string|unique:users,username,' . $user->id,
                'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            ];

            $validate_message = [
                'name.required' => 'Mohon masukkan nama lengkap.',
                'address.required' => 'Mohon masukkan alamat perusahaan.',
                'contact.required' => 'Mohon masukkan kontak perusahaan.',
                'username.required' => 'Mohon masukkan username.',
                'username.unique' => 'Username sudah digunakan.',
                'email.required' => 'Mohon masukkan email.',
                'email.unique' => 'Email sudah digunakan.'
            ];
        }else if($role == 'Student'){
            $validate_rules = [
                'fullname' => 'required|string|max:255',
                'username' => 'required|string|unique:users,username,' . $user->id,
                'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            ];

            $validate_message = [
                'fullname.required' => 'Mohon masukkan nama lengkap.',
                'username.required' => 'Mohon masukkan username.',
                'username.unique' => 'Username sudah digunakan.',
                'email.required' => 'Mohon masukkan email.',
                'email.unique' => 'Email sudah digunakan.'
            ];
        }else if($role == 'Teacher'){
            $validate_rules = [
                'fullname' => 'required|string|max:255',
                'username' => 'required|string|unique:users,username,' . $user->id,
                'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
                'facebook_name' => 'nullable|string|max:255',
                'instagram_name' => 'nullable|string|max:255',
                'facebook_url' => 'nullable|url|required_with:facebook_name',
                'instagram_url' => 'nullable|url|required_with:instagram_name',
            ];

            $validate_message = [
                'fullname.required' => 'Mohon masukkan nama lengkap.',
                'username.required' => 'Mohon masukkan username.',
                'username.unique' => 'Username sudah digunakan.',
                'email.required' => 'Mohon masukkan email.',
                'email.unique' => 'Email sudah digunakan.'
            ];
        }

        $validator = Validator::make($request->all(), $validate_rules, $validate_message);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()]);
        }

        if($user->role == 'Corporate Admin'){
            $info = [
                'name' => $request->input('name'),
                'address' => $request->input('address'),
                'contact' => $request->input('contact')
            ];
        }else if($user->role == 'Student'){
            $info = ['fullname' => $request->input('fullname')];
        }else if($user->role == 'Teacher'){
            $info = ['fullname' => $request->input('fullname')];
            $info['social_media'] = [
                [
                    "type" => 'Facebook',
                    "username" => $request->input('facebook_name') ?? '' ,
                    "url" => $request->input('facebook_url') ?? ''
                ], [
                    "type" => 'Instagram',
                    "username" => $request->input('instagram_name') ?? '',
                    "url" => $request->input('instagram_url')?? ''
                ]
            ];
        }

        $field = [
            'corporate_id' => $request->input('corporate_id') == null ||
                $request->input('corporate_id') == 'null' ? null : $request->input('corporate_id'),
            'email' => $request->input('email'),
            'username' => $request->input('username'),
            'info' => $info
        ];

        $user->update($field);

        return response()->json(['status' => true, 'message' => 'Berhasil mengubah data akun.']);
    }

    public function updateAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'avatar_file' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ], [
            'avatar_file.required' => 'Mohon pilih foto profil.',
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()]);
        }

        $user = User::findOrFail($request->input('id'));

        if(Storage::exists('public/' . $user->avatar) && !str_contains($user->avatar, 'default.png')){
            Storage::delete('public/' . $user->avatar);
        }

        $avatar = $request->file('avatar_file');
        $avatarPath = $avatar->storeAs(
            'avatars',
            uniqid() . '_' . time() . '.' . $avatar->getClientOriginalExtension(), 'public'
        );

        $user->avatar = $avatarPath;
        $user->save();

        return response()->json(['status' => true, 'message' => 'Berhasil mengubah foto profil.']);
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

    public function changeStatus($id)
    {

    }

    public function teacherStudent(Request $request)
    {
        $user = Auth::user();

        if($user->role != "Teacher"){
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $courses = Course::where('teacher_id', $user->id)->get();
        $students = User::whereHas('myCourses', function($query) use ($courses) {
            $query->whereIn('course_id', $courses->pluck('id'));
        })->distinct()->get(['id', 'email', 'info']);

        foreach($students as $student ){
            $student->course_count = CourseAccess::where('user_id', $student->id)
                                            ->whereIn('course_id', $courses->pluck('id'))
                                            ->count();
        }

        return response()->json(['status' => true, 'data' => $students], 200);
    }

    public function corporateStudentList(Request $request)
    {
        $user = Auth::user();

        if($user->role != "Corporate Admin"){
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $students = $user->corporateStudents()->get();

        foreach ($students as $student){
            $student->corporate_course = $student->courseAccessesCorporate()->get();
        }

        return response()->json(['status' => true, 'data' => $students], 200);
    }

    public function checkByEmail(Request $request)
    {
        $user = Auth::user();

        if($user->role != "Corporate Admin"){
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()], 402);
        }

        $student = User::where('email', $request->input('email'))->first();

        if(!$student){
            return response()->json(['error' => "Akun dengan email {$request->input('email')} tidak ditemukan!"], 402);
        }

        return response()->json(['status' => true, 'data' => $student], 200);
    }

    public function addToCorporate(Request $request)
    {
        $user = Auth::user();

        if($user->role != "Corporate Admin"){
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|int'
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()]);
        }

        $student = User::where('id', $request->input('student_id'))
                        ->where('role', 'Student')
                        ->update([
                            "corporate_id" => $user->id
                        ]);

        if(!$student){
            return response()->json(['error' => "Gagal menambahkan akun karyawan ke mitra!"], 402);
        }

        return response()->json(['status' => true, 'message' => "Berhasil menambahkan karyawan ke mitra!"], 200);
    }
}
