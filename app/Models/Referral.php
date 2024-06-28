<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;
    protected $fillable = ['corporate_id', 'code'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}