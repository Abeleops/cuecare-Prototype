<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Represents an alert raised by a security correlation rule. */
class SecurityAlert extends Model
{
    protected $guarded = [];

    public function rule()
    {
        return $this->belongsTo(SecurityRule::class, 'security_rule_id');
    }
}
