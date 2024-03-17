<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketSeatRelation extends Model
{
    use HasFactory;

    protected $table = "ticket_seat_relation";

    protected $primaryKey = 'relation_id'; // Assuming 'relation_id' is the primary key
    public $timestamps = false; // If 'created_at' and 'updated_at' timestamps are being used

    protected $fillable = [
        'ticket_id',
        'seat_id',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'ticket_id');
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class, 'seat_id', 'seat_id');
    }
}
