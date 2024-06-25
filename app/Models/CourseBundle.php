<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseBundle extends Model
{
    use HasFactory;
    protected $fillable = ['bundle_code', 'corporate_id', 'redeem_code_id', 'price', 'quota'];
    protected $casts = [
        'is_active' => 'boolean'
    ];

    public static function makeCode($name)
    {
        $code = strtoupper($name) . "_" . str_replace("-", "", Carbon::now()->timestamp) . rand(1000, 9999);
        $code = str_replace(' ', '', $code);

        return $code;
    }

    public static function generateBundleCode($name)
    {
        $code = CourseBundle::makeCode($name);
        while(self::where('bundle_code', $code)->exists()){
            $code = CourseBundle::makeCode($name);
        }

        return "#" . $code;
    }

    public function redeemCode() { return $this->belongsTo(RedeemCode::class); }

    public function bundleItems()
    {
        return $this->hasMany(BundleItem::class, 'bundle_id')
            ->select(['id', 'bundle_id', 'course_id']);
    }

    public function corporate() { return $this->belongsTo(User::class); }
}
