<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseAccess;
use App\Models\RedeemCode;
use App\Models\RedeemHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class RedeemCodeController extends Controller
{
    public static function show(Request $request)
    {
        $user = Auth::user();
        if(!$user->role === 'Student'){
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'redeem_code' => 'required'
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()], 402);
        }

        $redeem = RedeemCode::where('code', $request->input('redeem_code'))->with(['courseBundle.bundleItems.course'])->firstOrFail();
        $exist = RedeemHistory::where('redeem_code_id', $redeem->id)->where('user_id', $user->id)->exists();

        if(!$user->corporate_id){
            return response()->json(['error' => 'Anda tidak dapat akses ke kode tukar ini. Anda tidak/belum terhubung ke perusahaan mitra kami!'], 403);
        }

        if($user->corporate_id != $redeem['courseBundle']['corporate_id']){
            return response()->json(['error' => false, 'message' => 'Kode tukar ini bukan milik perusahaan anda!'], 402);
        }

        if($exist){
            return response()->json(['error' => 'Anda telah menukar kode ini sebelumnya.'], 402);
        }

        return response()->json(['status' => true, 'data' => $redeem], 200);
    }

    public static function redeem(Request $request)
    {
        $user = Auth::user();
        if(!$user->role === 'Student'){
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'redeem_code' => 'required'
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()], 402);
        }

        $redeem = RedeemCode::where('code', $request->input('redeem_code'))->with(['courseBundle.bundleItems.course'])->firstOrFail();
        $exist = RedeemHistory::where('redeem_code_id', $redeem->id)->where('user_id', $user->id)->exists();

        if(!$user->corporate_id){
            return response()->json(['error' => 'Anda tidak dapat akses ke kode tukar ini. Anda tidak/belum terhubung ke perusahaan mitra kami!'], 402);
        }

        if($user->corporate_id != $redeem['courseBundle']['corporate_id']){
            return response()->json(['error' => false, 'message' => 'Kode tukar ini bukan milik perusahaan anda!'], 402);
        }

        if($redeem->usage_count > $redeem['courseBundle']['quota']){
            return response()->json(['error' => false, 'message' => 'Penukaran kode melampaui kuota penukaran bundel!'], 402);
        }

        if($exist){
            return response()->json(['error' => 'Anda telah menukar kode ini sebelumnya.'], 402);
        }

        foreach($redeem['courseBundle']['bundleItems'] as $item){
            $is_exist = CourseAccess::where('user_id', $user->id)->where('course_id', $item->course->id)->exists();

            if(!$is_exist){
                CourseAccess::create([
                    'user_id' => $user->id,
                    'course_id' => $item->course->id,
                    'type' => 'Corporate',
                    'access_date' => now()
                ]);
            }
        }

        $history = RedeemHistory::create([
            'redeem_code_id' => $redeem->id,
            'user_id' => $user->id
        ]);

        $redeem->usage_count = $redeem->usage_count + 1;
        $redeem->save();

        return response()->json(['status' => true, 'message' => 'Berhasil menukarkan bundel!'], 200);
    }
}
