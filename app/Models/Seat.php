<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    use HasFactory;

    protected $table = 'seats';
    // protected $primaryKey = 'seat_id';
    public $timestamps = true; // If 'created_at' and 'updated_at' timestamps are being used

    protected $fillable = [
        'stadium_id',
        'seat_number',
        'type',
        'price',
        'stand',
        'status',
    ];

    public function stadium()
    {
        return $this->belongsTo(Stadium::class, 'stadium_id', 'stadium_id');
    }

    public function ticketSeatRelations()
    {
        return $this->hasMany(TicketSeatRelation::class, 'seat_id', 'seat_id');
    }
}
