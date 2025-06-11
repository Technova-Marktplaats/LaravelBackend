<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug', 
        'description',
        'icon',
        'active',
        'sort_order'
    ];

    protected $casts = [
        'active' => 'boolean'
    ];

    /**
     * Relatie naar items die tot deze categorie behoren
     */
    public function items()
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Scope voor alleen actieve categorieën
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope voor sorteren op sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Automatisch slug genereren bij opslaan
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }
}
