<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BundleItem extends Model
{
    use HasFactory;

    protected $fillable = ['bundle_id', 'course_id'];

    public function courseBundle()
    {
        return $this->belongsTo(CourseBundle::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class)
            ->select(['id', 'title', 'thumbnail', 'price', 'items']);
    }
}
