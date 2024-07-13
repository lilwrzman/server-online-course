<?php

namespace App\Http\Controllers;

use App\Models\BundleItem;
use App\Models\CourseBundle;
use App\Models\Notification;
use App\Models\RedeemCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CourseBundleController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $bundles = [];
        if(!$user->role === 'Superadmin' || !$user->role === 'Corporate Admin' ){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        if($user->role === 'Superadmin'){
            $bundles = CourseBundle::with(['redeemCode:id,code', 'bundleItems.course', 'redeemCode.redeemHistory'])->get();
        }else{
            $bundles = CourseBundle::where('corporate_id', $user->id)
                        ->with(['redeemCode:id,code', 'bundleItems.course', 'redeemCode.redeemHistory'])
                        ->get();
        }

        return response()->json(['status' => true, 'data' => $bundles], 200);
    }

    public function show($id)
    {
        $user = Auth::user();
        if(!$user->role === 'Superadmin' || !$user->role === 'Corporate Admin' ){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $bundle = CourseBundle::with([
            'redeemCode:id,code', 'bundleItems.course', 'redeemCode.redeemHistory.owner:id,info', 'corporate:id,info'
        ])->findOrFail($id);

        return response()->json(['data' => $bundle]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if(!$user->role === 'Superadmin'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'corporate' => 'required|int',
            'courses' => 'required',
            'price' => 'required|int',
            'quota' => 'required|int'
        ], [
            'corporate.required' => 'Silahkan pilih mitra.',
            'courses.required' => 'Harap pilih materi yang akan dimasukkan ke bundel.',
            'price.required' => 'Harap tentukan harga jual bundel.',
            'quota.int' => 'Harap tentukan kuota penukaran bundel.'
        ]);

        if($validator->fails()) { return response()->json(['error' => $validator->errors()]); }

        $corporate = User::findOrFail($request->input('corporate'));
        $bundle = CourseBundle::create([
            'bundle_code' => CourseBundle::generateBundleCode($corporate['info']['name']),
            'corporate_id' => $request->input('corporate'),
            'price' => $request->input('price'),
            'quota' => $request->input('quota')
        ]);

        if(!$bundle) { return response()->json(['status' => false, 'message' => 'Gagal membuat bundel!']); }

        $courses = $request->input('courses');
        foreach($courses as $course_id){
            BundleItem::create([ 'bundle_id' => $bundle->id, 'course_id' => $course_id ]);
        }

        $redeem_code = RedeemCode::create([ "code" => RedeemCode::generateUniqueCode(), "usage_count" => 0 ]);

        $bundle->redeem_code_id = $redeem_code->id;
        $bundle->save();

        $notification = Notification::create([
            'title' => 'Paket Kursus',
            'message' => 'Paket kursus baru telah ditambahkan, cek paket kursus sekarang!',
            'info' => [
                "target" => ["superadmin", "corporate admin"],
                "menu" => "bundle",
                "bundle_id" => $bundle->id
            ]
        ]);

        $users = User::whereIn('id', [$user->id, $request->input('corporate')])->get();
        $notification->assignToUsers($users);

        return response()->json(['status' => true, 'message' => 'Berhasil menambahkan bundel!']);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        if(!$user->role === 'Superadmin'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'id' => 'required|int',
            'price' => 'required|int',
            'quota' => 'required|int'
        ], [
            'id.required' => 'Oops, id data tidak disertakan.',
            'price.required' => 'Harap tentukan harga jual bundel.',
            'quota.int' => 'Harap tentukan kuota penukaran bundel.'
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()]);
        }

        $bundle = CourseBundle::findOrFail($request->input('id'))->update([
            'price' => $request->input('price'),
            'quota' => $request->input('quota'),
        ]);

        if(!$bundle){
            return response()->json(['false' => true, 'message' => 'Gagal mengubah data bundel!']);
        }

        return response()->json(['status' => true, 'message' => 'Berhasil mengubah data bundel!']);
    }

    public function changeAccess($id){
        $user = Auth::user();
        if(!$user->role === 'Superadmin'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $bundle = CourseBundle::findOrFail($id);
        $bundle->is_active = !$bundle->is_active;
        $bundle->save();

        return response()->json(['status' => true, 'message' => 'Berhasil mengubah akses bundel.']);
    }
}
