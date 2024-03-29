<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'title', 'content', 'thumbnail',
        'quota', 'type', 'status', 'info'
    ];

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
