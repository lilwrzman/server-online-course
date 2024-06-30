<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentProgress extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'item_id', 'is_done'];
    protected $casts = ['is_done' => 'boolean'];

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function item()
    {
        return $this->belongsTo(CourseItem::class, 'item_id');
    }
}
