<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    public function index()
    {
        $articles = Article::with(['author:id,username,email,avatar'])->orderBy('created_at', 'desc')->get();

        return response()->json(['status' => true, 'data' => $articles], 200);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if($user->role !== "Superadmin"){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'content' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $field = $request->all();
        $field['author_id'] = $user->id;

        if($request->hasFile('thumbnail_file')){
            $thumbnail = $request->file('thumbnail_file');
            $thumbnailPath = $thumbnail->storeAs(
                'articles',
                uniqid() . '_' . time() . '.' . $thumbnail->getClientOriginalExtension(), 'public'
            );

            $field['thumbnail'] = $thumbnailPath;
        }

        $article = Article::create($field);

        if(!$article){
            return response()->json(['error' => 'Gagal menyimpan artikel!'], 500);
        }

        $notification = Notification::create([
            'title' => 'Artikel Baru',
            'message' => 'Ada artikel baru yang terbit nih, penasaran? Yuk cek sekarang!',
            'info' => [
                "menu" => "articles",
                "article_id" => $article->id
            ]
        ]);

        $users = User::all();

        $notification->assignToUsers($users);

        return response()->json(['status' => true, 'message' => 'Data artikel berhasil dibuat!'], 201);
    }

    public function show($id)
    {
        $article = Article::findOrFail($id);

        return response()->json(['status' => true, 'data' => $article], 200);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        if(!$user->role === 'Superadmin'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'id' => 'required|int',
            'title' => 'required|string|unique:articles,title,' . $request->input('id'),
            'content' => 'required|string'
        ]);

        if($validator->fails()){
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $article = Article::findOrFail($request->input('id'));
        $article->slug = null;
        $article->update($request->all());

        $notification = Notification::create([
            'title' => 'Update Artikel',
            'message' => 'Anda telah telah melakukan update data artikel. Yuk cek sekarang!',
            'info' => [
                "menu" => "articles",
                "article_id" => $article->id
            ]
        ]);

        $notification->assignToUsers($user);

        return response()->json(['status' => true, 'message' => 'Data artikel berhasil diubah!'], 201);
    }

    public function destroy(Request $request)
    {
        $user = Auth::user();
        if(!$user->role === 'Superadmin'){
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        DB::beginTransaction();

        try {
            $article = Article::findOrFail($request->input('id'));

            if (Storage::exists('public/' . $article->thumbnail) && !str_contains($article->thumbnail, 'thumbnail.png')) {
                Storage::delete('public/' . $article->thumbnail);
            }

            $article->delete();

            DB::commit();

            return response()->json(['status' => true, 'message' => 'Berhasil menghapus Artikel.'], 200);
        } catch (QueryException $e) {
            DB::rollBack();

            return response()->json(['error' => 'Gagal menghapus Artikel.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal menghapus Artikel.']);
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
            'thumbnail_file.required' => 'Mohon pilih foto thumbnail artikel.',
        ]);

        if($validator->fails()){
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $article = Article::findOrFail($request->input('id'));

        if(Storage::exists('public/' . $article->thumbnail) && !str_contains($article->thumbnail, 'default.png')){
            Storage::delete('public/' . $article->thumbnail);
        }

        $avatar = $request->file('thumbnail_file');
        $avatarPath = $avatar->storeAs(
            'articles',
            uniqid() . '_' . time() . '.' . $avatar->getClientOriginalExtension(), 'public'
        );

        $article->thumbnail = $avatarPath;
        $article->save();

        return response()->json(['status' => true, 'message' => 'Berhasil mengubah foto thumbnail artikel.'], 200);
    }
}
