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
use App\Models\TicketPurchase;

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
            $ticket = Ticket::where("ticket_id",$id);
            return response()->json(['ticket' => new TicketResource($ticket)]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Ticket not found'], 404);
        }
    }

    public function create(Request $request)
    {
        // Validate incoming request data
        $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'game_id' => 'required|exists:games,game_id',
            'stand' => 'required',
            'list_seats' => 'required|array',
            'list_seats.*' => 'exists:seats,seat_id',
        ]);

        $result = DB::table('seats as s')
            ->leftJoin('tickets as t', 's.seat_id', '=', 't.seat_id')
            ->where('t.game_id', $request->game_id)
            ->where('s.stand', $request->stand)
            ->whereIn('s.seat_id', $request->list_seats)
            ->whereNotNull('t.ticket_id')
            ->get();
        if(!$result->isEmpty()){
            return response()->json(['error' => 'Seat(s) already booked'], 400);
        }

        // Start a database transaction
        \DB::beginTransaction();
        try {
            // Check if any of the seats are already booked for the given game_id

            // Create tickets for each seat in the list
            foreach ($request->input('list_seats') as $seatId) {
                // Get the seat information including the price
                $seat = Seat::where('seat_id', $seatId)
                    ->firstOrFail();

                // Create a new ticket with the price from the seat
                $ticket = new Ticket([
                    'game_id' => $request->input('game_id'),
                    'seat_id' => $seatId,
                    'price' => $seat->price, // Assign the price from the seat
                    'is_sold' => true, // Assuming the ticket is not sold initially
                ]);

                // Save the ticket to the database
                $ticket->save();

                // Create a ticket purchase record
                $ticketPurchase = new TicketPurchase([
                    'user_id' => $request->input('user_id'),
                    'ticket_id' => $ticket->ticket_id, // Use the ID of the newly created ticket
                    'purchase_date' => now(), // Assuming purchase date is the current date and time
                ]);
                $ticketPurchase->save(); // Save the ticket purchase to the database
            }

            \DB::commit();

            return response()->json(['message' => 'Tickets purchased successfully'], 201);
        } catch (Exception $e) {
            // Rollback the transaction if any operation fails
            \DB::rollback();

            return response()->json(['message' => 'Failed to purchase tickets. Please try again.'], 500);
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
