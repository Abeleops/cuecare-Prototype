<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Groups menu items into a category. */
class MenuCategory extends Model
{
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(MenuItem::class);
    }
}
