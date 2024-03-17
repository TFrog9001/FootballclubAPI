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
            'club_away' => $this->game->club,
            'seat' => $this->seat,
            'stadium' => $this->game->stadium,
            'created_at' => $this->created_at,
        ];
    }
}
