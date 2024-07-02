<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'course_id', 'price', 'status', 'snap_token'];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function student(){
        return $this->belongsTo(User::class, 'user_id');
    }
}
