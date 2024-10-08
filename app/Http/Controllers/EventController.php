<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::orderBy('created_at', 'desc')->get();

        return response()->json(['status' => true, 'data' => $events], 200);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if($user->role !== "Superadmin"){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'place' => 'required|string',
            'date' => 'required|date',
            'start' => 'required|date_format:H:i',
            'end' => 'required|date_format:H:i',
            'link' => 'nullable|url'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $field = $request->all();

        if($request->hasFile('thumbnail_file')){
            $thumbnail = $request->file('thumbnail_file');
            $thumbnailPath = $thumbnail->storeAs(
                'events',
                uniqid() . '_' . time() . '.' . $thumbnail->getClientOriginalExtension(), 'public'
            );

            $field['thumbnail'] = $thumbnailPath;
        }

        $event = Event::create($field);

        if(!$event){
            return response()->json(['error' => 'Gagal menyimpan acara!'], 500);
        }

        $notification = Notification::create([
            'title' => 'Acara',
            'message' => 'Ada acara yang baru nih, penasaran? Yuk cek sekarang!',
            'info' => [
                "target" => ["all"],
                "menu" => "events",
                "event_id" => $event->id
            ]
        ]);

        $users = User::all();

        $notification->assignToUsers($users);

        return response()->json(['status' => true, 'message' => 'Data acara berhasil dibuat!'], 201);
    }

    public function show($id)
    {
        $event = Event::findOrFail($id);

        return response()->json(['status' => true, 'data' => $event], 200);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        if(!$user->role === 'Superadmin'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'id' => 'required|int',
            'title' => 'required|string|unique:events,title,' . $request->input('id'),
            'place' => 'required|string',
            'date' => 'required|date',
            'start' => 'required|date_format:H:i',
            'end' => 'required|date_format:H:i',
            'link' => 'nullable|url'
        ]);

        if($validator->fails()){
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $event = Event::findOrFail($request->input('id'));
        $event->slug = null;
        $event->update($request->all());

        $notification = Notification::create([
            'title' => 'Acara',
            'message' => 'Anda telah telah melakukan update data acara. Yuk cek sekarang!',
            'info' => [
                "target" => ["superadmin"],
                "menu" => "events",
                "event_id" => $event->id
            ]
        ]);

        $notification->assignToUsers($user);

        return response()->json(['status' => true, 'message' => 'Data acara berhasil diubah!'], 201);
    }

    public function destroy(Request $request)
    {
        $user = Auth::user();
        if(!$user->role === 'Superadmin'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        DB::beginTransaction();

        try {
            $event = Event::findOrFail($request->input('id'));

            if (Storage::exists('public/' . $event->thumbnail) && !str_contains($event->thumbnail, 'thumbnail.png')) {
                Storage::delete('public/' . $event->thumbnail);
            }

            $event->delete();

            DB::commit();

            return response()->json(['status' => true, 'message' => 'Berhasil menghapus Acara.'], 200);
        } catch (QueryException $e) {
            DB::rollBack();

            return response()->json(['error' => 'Gagal menghapus Acara.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal menghapus Acara.']);
        }
    }

    public function changeThumbnail(Request $request)
    {
        $user = Auth::user();
        if(!$user->role === 'Superadmin'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'thumbnail_file' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ], [
            'thumbnail_file.required' => 'Mohon pilih foto thumbnail acara.',
        ]);

        if($validator->fails()){
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $event = Event::findOrFail($request->input('id'));

        if(Storage::exists('public/' . $event->thumbnail) && !str_contains($event->thumbnail, 'thumbnail.png')){
            Storage::delete('public/' . $event->thumbnail);
        }

        $avatar = $request->file('thumbnail_file');
        $avatarPath = $avatar->storeAs(
            'events',
            uniqid() . '_' . time() . '.' . $avatar->getClientOriginalExtension(), 'public'
        );

        $event->thumbnail = $avatarPath;
        $event->save();

        return response()->json(['status' => true, 'message' => 'Berhasil mengubah foto thumbnail acara.']);
    }
}
