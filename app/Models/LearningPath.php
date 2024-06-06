<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningPath extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = ['title', 'description', 'slug', 'thumbnail', 'color'];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title'
            ]
        ];
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
