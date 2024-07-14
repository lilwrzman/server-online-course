<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseItem extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = ['course_id', 'title', 'description', 'type', 'slug', 'info', 'order'];
    protected $casts = ['info' => 'json'];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title'
            ]
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function progresses()
    {
        return $this->hasMany(StudentProgress::class);
    }

    public function questions()
    {
        return $this->hasMany(AssessmentQuestion::class, 'item_id');
    }

    public function questionsCount()
    {
        return $this->questions()->count();
    }

    public function assessmentHistories()
    {
        return $this->hasMany(AssessmentHistory::class, 'item_id');
    }
}
