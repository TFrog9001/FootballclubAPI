<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'seat_id' => $this->seat_id,
            'stadium_id' => $this->stadium_id,
            'seat_number' => $this->seat_number,
            'stand' => $this->stand,
            'type' => $this->type,
            'price' => $this->price,
            'status' => $this->status,
            // 'ticket_id' => $this->ticket->ticket_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
