<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'item_id',
        'message',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
    
    public function item() {
        return $this->belongsTo(Item::class);
    }
    
}
