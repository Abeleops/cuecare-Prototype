<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Stores a security event, its metadata, and the associated account. */
class SecurityEvent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
