<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseItem extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = ['course_id', 'title', 'content', 'type', 'slug', 'info'];
    protected $casts = ['info' => 'json'];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title'
            ]
        ];
    }
}
