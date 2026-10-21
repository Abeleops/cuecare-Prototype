<?php

namespace App\Models;

use App\Observers\MenuItemObserver;
use Illuminate\Database\Eloquent\Model;

/** Represents a menu item and registers its audit observer. */
class MenuItem extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::observe(MenuItemObserver::class);
    }

    public function category()
    {
        return $this->belongsTo(MenuCategory::class, 'menu_category_id');
    }

    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }
}
