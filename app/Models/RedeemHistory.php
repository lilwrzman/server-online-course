<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RedeemHistory extends Model
{
    use HasFactory;

    protected $fillable = ['redeem_code_id', 'user_id'];

    public function code()
    {
        return $this->belongsTo(RedeemCode::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
