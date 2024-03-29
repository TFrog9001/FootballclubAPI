<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $primaryKey = 'game_id'; // Primary key field name

    protected $fillable = [
        'club_id',
        'stadium_id',
        'game_date',
        'game_time',
        'goals_scored',
        'goals_conceded',
        'result',
        'state',
        'host',
        'remaining_seats',
    ];

    public $timestamps = false; // Không sử dụng timestamps

    // Relationships
    public function club()
    {
        return $this->belongsTo(Club::class, 'club_id', 'club_id');
    }

    public function stadium()
    {
        return $this->belongsTo(Stadium::class, 'stadium_id', 'stadium_id');
    }

    public function gameDetail()
    {
        return $this->hasMany(GameDetail::class, 'game_id', 'game_id');
    }

    public function team_lineup()
    {
        return $this->hasMany(TeamLineup::class, 'game_id', 'game_id');
    }
}
