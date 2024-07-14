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
        'slug', 'thumbnail', 'price', 'order', 'items', 'isPublished',
        'facilities'
    ];

    protected $casts = [
        'isPublished' => 'boolean',
        'facilities' => 'json'
    ];

    public function learningPath()
    {
        return $this->belongsTo(LearningPath::class, 'learning_path_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CourseItem::class, 'course_id');
    }

    public function feedbacks()
    {
        return $this->hasMany(CourseFeedback::class);
    }

    public function courseAccesses()
    {
        return $this->hasMany(CourseAccess::class, 'course_id');
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'course_accesses');
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title'
            ]
        ];
    }

    public function assessmentHistories()
    {
        return $this->hasManyThrough(AssessmentHistory::class, CourseItem::class, 'course_id', 'item_id', 'id', 'id');
    }

    public function transaction()
    {
        return $this->hasMany(Transaction::class, 'course_id');
    }

    public function discussions()
    {
        return $this->hasMany(Discussion::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class, 'course_id');
    }
}
