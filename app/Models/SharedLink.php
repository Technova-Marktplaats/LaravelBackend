<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SharedLink extends Model
{
    public function item() {
        return $this->belongsTo(Item::class);
    }
    
}
