<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ticket_id' => $this->ticket_id,
            'game_id' => $this->game_id,
            'club_away' =>$this->game->club->name,
            'seat_id' => $this->seat_id,
            'stand' => $this->seat->stand,
            'type' => $this->seat->type, // Loại ghế
            'seat_number' => $this->seat->seat_number,
            'price' => $this->price,
            'is_sold' => $this->is_sold,
            'stadium_name' => $this->game->stadium->name,
            'stadium_address' => $this->game->stadium->address,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
