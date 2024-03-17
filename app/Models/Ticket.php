<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $primaryKey = 'ticket_id'; // Assuming 'ticket_id' is the primary key
    public $timestamps = true; // If 'created_at' and 'updated_at' timestamps are being used

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'game_id',
        'price',
        'is_sold',
    ];

    /**
     * Get the game that the ticket belongs to.
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id', 'game_id');
    }

    public function seatRelations()
    {
        return $this->hasMany(TicketSeatRelation::class, 'ticket_id', 'ticket_id');
    }

    /**
     * Get the purchases associated with the ticket.
     */
    public function purchases()
    {
        return $this->hasMany(TicketPurchase::class, 'ticket_id', 'ticket_id');
    }
}
