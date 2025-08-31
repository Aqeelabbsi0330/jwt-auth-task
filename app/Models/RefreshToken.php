<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefreshToken extends Model
{
    use HasFactory;
    protected $table = 'refresh_tokens';
    protected $fillable = [
        'user_id',
        'token',
        'jti',
        'device_type',
        'ip_address',
        'expire_at',
        'created_by',
        'updated_by',
    ];

    public $timestamps = true;
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
