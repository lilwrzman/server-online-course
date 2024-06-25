<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentQuestion extends Model
{
    use HasFactory;
    protected $fillable = ['item_id', 'question', 'options', 'correct_answer', 'order'];
    protected $casts = ['options' => 'json'];
    protected $hidden = ['created_at', 'updated_at'];

    public function quiz()
    {
        return $this->belongsTo(CourseItem::class)->where('type', 'Quiz');
    }

    public function exam()
    {
        return $this->belongsTo(CourseItem::class)->where('type', 'Exam');
    }
}
