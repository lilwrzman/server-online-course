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
        if(!$user->role === 'Student' && !$user->corporate_id){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'redeem_code' => 'required'
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()], 402);
        }

        $redeem = RedeemCode::where('code', $request->input('redeem_code'))->with(['courseBundle.bundleItems.course'])->firstOrFail();

        return response()->json(['status' => true, 'data' => $redeem]);
    }

    public static function redeem(Request $request)
    {
        $user = Auth::user();
        if(!$user->role === 'Student' || $user->corporate_id === null){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'redeem_code' => 'required'
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()]);
        }

        $redeem = RedeemCode::where('code', $request->input('redeem_code'))->with(['courseBundle.bundleItems.course'])->firstOrFail();

        if($user->corporate_id != $redeem['courseBundle']['corporate_id']){
            return response()->json(['status' => false, 'message' => 'Kode tukar ini bukan milik perusahaan anda!']);
        }

        if($redeem->usage_count > $redeem['courseBundle']['quota']){
            return response()->json(['status' => false, 'message' => 'Penukaran kode melampaui kuota penukaran bundel!']);
        }

        foreach($redeem['courseBundle']['bundleItems'] as $item){
            CourseAccess::create([
                'user_id' => $user->id,
                'course_id' => $item->course->id,
                'type' => 'Corporate',
                'access_date' => now()
            ]);

            $course = Course::findOrFail($item->course->id);
            $course->enrolled = $course->enrolled + 1;
            $course->save();
        }

        $history = RedeemHistory::create([
            'redeem_code_id' => $redeem->id,
            'user_id' => $user->id
        ]);

        $redeem->usage_count = $redeem->usage_count + 1;
        $redeem->save();

        return response()->json(['status' => true, 'message' => 'Berhasil menukarkan bundel!']);
    }
}
