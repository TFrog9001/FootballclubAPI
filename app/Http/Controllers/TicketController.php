<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Exception;

use App\Models\Ticket;
use App\Http\Resources\TicketResource;
use App\Models\Seat;
use App\Models\Game;

class TicketController extends Controller
{
    public function index()
    {
        try {
            $tickets = Ticket::all();
            return response()->json(['tickets' => TicketResource::collection($tickets)], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $ticket = Ticket::findOrFail($id);
            return response()->json(['ticket' => new TicketResource($ticket)]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Ticket not found'], 404);
        }
    }

    public function create(Request $request)
    {
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'game_id' => 'required|exists:games,game_id',
                'seat_id' => 'required|exists:seats,seat_id',
            ]);

            // Check if a ticket already exists for the provided game_id and seat_id
            $existingTicket = Ticket::where('game_id', $validatedData['game_id'])
                ->where('seat_id', $validatedData['seat_id'])
                ->exists();

            if ($existingTicket) {
                return response()->json(['error' => 'A ticket already exists for the specified game and seat'], 400);
            }

            // Find the game by game_id
            $game = Game::findOrFail($validatedData['game_id']);

            // Check if the game is upcoming and hosted by club with ID 1
            if ($game->state !== 'upcoming' || $game->host !== 1) {
                return response()->json(['error' => 'The specified game is not upcoming or not hosted by the specified club'], 400);
            }

            // Find the seat by seat_id
            $seat = Seat::where('seat_id',$validatedData['seat_id'])->first();

            // Check if the seat is available
            if ($seat->status !== 'available') {
                return response()->json(['error' => 'The specified seat is not available'], 400);
            }

            // Create the ticket
            $ticket = Ticket::create([
                'game_id' => $game->game_id,
                'seat_id' => $seat->seat_id,
                'price' => $seat->price,
                'is_sold' => false,
            ]);

            // Return success message along with the created ticket
            return response()->json(['message' => 'Ticket created successfully', 'ticket' => new TicketResource($ticket)], 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            // Xử lý logic cập nhật ticket
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            // Xử lý logic xóa ticket
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
