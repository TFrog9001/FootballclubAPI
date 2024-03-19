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
            'game_date' => $this->game->game_date,
            'game_time' => $this->game->game_time,
            'club_away' => $this->game->club,
            'stadium' => $this->game->stadium,
            'state' => $this->game->state,
            'price' => $this->price,
            'seats' => $this->seatRelations->map(function ($relation) {
                return [
                    'seat_id' => $relation->seat->seat_id,
                    'seat_number' => $relation->seat->seat_number,
                    'type' => $relation->seat->type,
                    'stand' => $relation->seat->stand,
                ];
            }) ?? null,
            'created_at' => $this->created_at,
        ];
    }
}
