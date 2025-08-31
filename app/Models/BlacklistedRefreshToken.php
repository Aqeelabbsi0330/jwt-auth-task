<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlacklistedRefreshToken extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'refresh_token',
        'jti',
        'device_type',
        'ip_address',
        'expire_at',
        'created_by',
        'updated_by',
    ];
}
