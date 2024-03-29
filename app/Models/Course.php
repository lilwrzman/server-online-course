<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'learning_path_id', 'teacher_id', 'title', 'description',
        'slug', 'thumbnail', 'price', 'status'
    ];

    public function learningPath()
    {
        return $this->belongsTo(LearningPath::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(CourseFeedback::class);
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title'
            ]
        ];
    }
}
