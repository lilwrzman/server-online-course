<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $notifications = UserNotification::where('user_id', $user->id)
            ->with(["notification"])->get();

        return response()->json(['status' => true, 'data' => $notifications], 200);
    }
}
