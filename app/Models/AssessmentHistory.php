<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentHistory extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'item_id', 'answer', 'score', 'is_pass'];
    protected $casts = [
        'answer' => 'json',
        'is_pass' => 'boolean'
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assessment()
    {
        return $this->belongsTo(CourseItem::class, 'item_id');
    }
}
