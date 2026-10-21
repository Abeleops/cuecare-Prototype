<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Stores a sale line and any custom recipe usage associated with it. */
class SaleItem extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'custom_recipe_usage' => 'array',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }
}
