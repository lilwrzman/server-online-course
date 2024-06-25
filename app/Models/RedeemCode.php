<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RedeemCode extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'usage_count'];

    public static function generateUniqueCode()
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function courseBundle()
    {
        return $this->hasOne(CourseBundle::class);
    }

    public function redeemHistory()
    {
        return $this->hasMany(RedeemHistory::class);
    }
}
