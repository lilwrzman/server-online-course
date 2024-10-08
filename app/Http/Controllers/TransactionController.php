<?php

namespace App\Http\Controllers;

use App\Exports\TransactionExport;
use App\Models\Course;
use App\Models\CourseAccess;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class TransactionController extends Controller
{
    public function process(Request $request)
    {
        $user = Auth::user();

        if($user->role != 'Student'){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'course_id' => 'required|int'
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()], 422);
        }

        $course = Course::with(['items:id,course_id,type'])->findOrFail($request->input('course_id'));
        $existingTransaction = Transaction::where('user_id', $user->id)
                                          ->where('course_id', $request->input('course_id'))
                                          ->orderBy('created_at', 'desc')
                                          ->first();

        \Midtrans\Config::$serverKey = config('midtrans.serverKey');
        \Midtrans\Config::$isProduction = config('midtrans.isProduction');
        \Midtrans\Config::$isSanitized = config('midtrans.isSanitized');
        \Midtrans\Config::$is3ds = config('midtrans.is3ds');

        if ($existingTransaction) {
            if($existingTransaction->status == 'success'){
                $access = CourseAccess::where('user_id', $user->id)
                            ->where('course_id', $request->input('course_id'))
                            ->first();

                if(!$access){
                    $access = CourseAccess::create([
                        "user_id" => $existingTransaction->user_id,
                        "course_id" => $existingTransaction->course_id,
                        "status" => "On-Progress",
                        "type" => "Personal",
                        "access_date" => now()
                    ]);
                }

                return response()->json(['error' => 'You have already purchased this course'], 422);
            }elseif($existingTransaction->status == 'pending'){
                if (is_null($existingTransaction->snap_token)) {
                    $params = [
                        "transaction_details" => [
                            "order_id" => "#" . hash('sha1', $existingTransaction->id),
                            "gross_amount" => $course->price
                        ],
                        "customer_details" => [
                            "first_name" => $user->info['fullname'],
                            "email" => $user->email
                        ]
                    ];

                    $snapToken = \Midtrans\Snap::getSnapToken($params);
                    $existingTransaction->snap_token = $snapToken;
                    $existingTransaction->save();
                }

                return response()->json(['status' => true, 'transaction' => $existingTransaction, 'course' => $course, 'new' => false], 200);
            }
        }

        $transaction = Transaction::create([
            "user_id" => $user->id,
            "course_id" => $request->input('course_id'),
            "price" => $course->price,
            "status" => 'pending'
        ]);

        $params = array(
            "transaction_details" => array(
                "order_id" => "#" . hash('sha1', $existingTransaction->id),
                "gross_amount" => $course->price
            ),
            "customer_details" => array(
                "first_name" => $user->info['fullname'],
                "email" => $user->email
            )
        );

        $snapToken = \Midtrans\Snap::getSnapToken($params);
        $transaction->snap_token = $snapToken;
        $transaction->save();

        return response()->json(['status' => true, 'transaction' => $transaction, 'course' => $course, 'new' => true], 200);
    }

    public function success($id)
    {
        $user = Auth::user();
        $transaction = Transaction::findOrFail($id);
        $course = Course::findOrFail($transaction->course_id);
        $transaction->status = 'success';
        $transaction->save();

        $access = CourseAccess::create([
            "user_id" => $transaction->user_id,
            "course_id" => $transaction->course_id,
            "status" => "On-Progress",
            "type" => "Personal",
            "access_date" => now()
        ]);

        if(!$access){
            return response()->json(['status' => false, 'message' => 'Failed to create access'], 422);
        }

        $notification_student = Notification::create([
            'title' => 'Pembelian Materi',
            'message' => 'Anda telah berhasil melakukan pembelian Materi: ' . $course->title . '! Mulai belajar sekarang',
            'info' => [
                "target" => ["student"],
                "menu" => "transactions",
                "course_id" => $course->id
            ]
        ]);

        $notification_admin = Notification::create([
            'title' => 'Pembelian Materi',
            'message' => 'Akun dengan username: ' . $user->username . ' telah berhasil melakukan pembelian Materi: ' . $course->title . '! Cek transaksi sekarang!',
            'info' => [
                "target" => ["superadmin"],
                "menu" => "transaction",
                "course_id" => $course->id
            ]
        ]);

        $admins = User::where('role', 'Superadmin')->get();
        $notification_student->assignToUsers($user);
        $notification_admin->assignToUsers($admins);

        return response()->json(['status' => true, 'message' => 'Berhasil membeli materi ' . $course->title . '!'], 200);
    }

    public function pending($id)
    {
        $user = Auth::user();
        $transaction = Transaction::findOrFail($id);
        $course = Course::findOrFail($transaction->course_id);
        $transaction->status = 'pending';
        $transaction->save();

        return response()->json(['status' => true, 'message' => 'Pembelian materi ' . $course->title . ' pending!'], 200);
    }

    public function failed($id)
    {
        $user = Auth::user();
        $transaction = Transaction::findOrFail($id);
        $course = Course::findOrFail($transaction->course_id);
        $transaction->status = 'failed';
        $transaction->save();

        return response()->json(['status' => true, 'message' => 'Pembelian materi ' . $course->title . ' gagal! Mohon coba kembali!'], 200);
    }

    public function transactionHistory(Request $request){
        $user = Auth::user();

        if($user->role != 'Student' && $user->role != 'Superadmin'){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if($user->role == 'Superadmin'){
            $histories = Transaction::with([
                'course:id,title',
                'student:id,username,email,info'
            ])->orderBy('created_at', 'desc')->get(['id', 'user_id', 'status', 'price', 'course_id', 'created_at']);
        }else{
            $histories = $user->myTransaction()->with([
                'course:id,title,price,thumbnail',
                'course.items:id,course_id,type'
            ])->orderBy('created_at', 'desc')->get();
        }

        return response()->json(['status' => true, 'data' => $histories], 200);
    }

    public function export()
    {
        $user = Auth::user();

        if($user->role != 'Superadmin'){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $fileName = uniqid() . '_transactions.xlsx';
        $filePath = storage_path('app/' . $fileName);
        Excel::store(new TransactionExport(), $fileName);

        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment;filename="'.$fileName.'"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => 0,
            'Access-Control-Expose-Headers' => '*'
        ])->deleteFileAfterSend(true);
    }
}
