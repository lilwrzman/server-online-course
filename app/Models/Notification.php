<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Notification extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'message', 'info'];

    protected $casts = ['info' => 'json'];

    public function user()
    {
        return $this->belongsToMany(User::class, 'user_notifications')->withPivot('is_seen')->withTimestamps();
    }

    public function assignToUsers($users): void
    {
        if($users instanceof User) {
            $users->notifications()->attatch($this->id);
        } elseif($users instanceof Collection) {
            foreach($users as $user){
                $user->notifications()->attatch($this->id);
            }
        } else {
            throw new \InvalidArgumentException("Parameter mush be instance of User or Collection");
        }
    }
}
