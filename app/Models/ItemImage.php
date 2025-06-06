<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id', 'url'
    ];

    public function item() {
        return $this->belongsTo(Item::class);
    }
}
