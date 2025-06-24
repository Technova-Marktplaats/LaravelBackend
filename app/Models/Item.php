<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'title', 'description', 'category_id', 'available', 'share_link'
    ];

    protected $appends = ['share_url'];

    public function user() {
        return $this->belongsTo(User::class);
    }
    
    public function images() {
        return $this->hasMany(ItemImage::class);
    }
    
    public function reservations() {
        return $this->hasMany(Reservation::class);
    }
    
    public function notifications() {
        return $this->hasMany(Notification::class);
    }
    
    public function watchlistedBy() {
        return $this->hasMany(Watchlist::class);
    }
    
    public function sharedLinks() {
        return $this->hasMany(SharedLink::class);
    }

    /**
     * Relatie naar de categorie van dit item
     */
    public function category() {
        return $this->belongsTo(Category::class);
    }

    /**
     * Accessor voor de volledige share URL
     */
    public function getShareUrlAttribute()
    {
        return $this->share_link ? url('/shared/' . $this->share_link) : null;
    }
}
