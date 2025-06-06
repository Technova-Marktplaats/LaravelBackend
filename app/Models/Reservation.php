<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id', 'borrower_id', 'start_date', 'end_date', 'status'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function item() {
        return $this->belongsTo(Item::class);
    }
    
    public function borrower() {
        return $this->belongsTo(User::class, 'borrower_id');
    }
    
}
