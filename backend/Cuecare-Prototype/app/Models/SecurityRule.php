<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Stores a security correlation rule and links it to generated alerts. */
class SecurityRule extends Model
{
    protected $guarded = [];

    public function alerts()
    {
        return $this->hasMany(SecurityAlert::class);
    }
}
