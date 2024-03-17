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
use App\Models\TicketSeatRelation;

class TicketController extends Controller
{
    public function index()
    {
        try {
            $tickets = Ticket::all()->sortBy('ticket_id');
            return response()->json(['tickets' => TicketResource::collection($tickets)], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            // Lấy ra một đối tượng Ticket thay vì câu truy vấn
            $ticket = Ticket::findOrFail($id);
            return response()->json(['ticket' => new TicketResource($ticket)]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Ticket not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    public function tickets($user_id)
    {
        try {
            // Tìm các vé mà user đã mua
            $tickets = Ticket::whereHas('purchases', function ($query) use ($user_id) {
                $query->where('user_id', $user_id);
            })
                ->orderBy('ticket_id')
                ->get();

            // Trả về dữ liệu dưới dạng JSON sử dụng TicketResource
            return response()->json(['tickets' => TicketResource::collection($tickets)]);
        } catch (Exception $e) {
            // Xử lý ngoại lệ nếu có
            return response()->json(['error' => 'Something went wrong'], 500);
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

        $seatsBooked = Seat::leftJoin('ticket_seat_relation as tsr', 'seats.seat_id', '=', 'tsr.seat_id')
            ->leftJoin('tickets as t', 't.ticket_id', '=', 'tsr.ticket_id')
            ->where('seats.stand', $request->stand)
            ->where('t.game_id', $request->game_id)
            ->whereIn('tsr.seat_id', $request->list_seats)
            ->orderBy('seats.seat_number')
            ->select('seats.*', 't.*', 'tsr.*')
            ->get();

        if (!$seatsBooked->isEmpty()) {
            $bookedSeatIds = $seatsBooked->pluck('seat_id')->unique()->implode(', ');
            return response()->json(['error' => 'Seat(s) ' . $bookedSeatIds . ' already booked'], 400);
        }

        // Start a database transaction
        \DB::beginTransaction();
        try {
            $totalPrice = 0;

            // Create a new ticket with the price from the seat
            $ticket = new Ticket([
                'game_id' => $request->input('game_id'),
                'price' => 100, // Assign the price from the seat
                'is_sold' => true, // Assuming the ticket is not sold initially
            ]);
            // Save the ticket to the database
            $ticket->save();
            // Create tickets for each seat in the list

            foreach ($request->input('list_seats') as $seatId) {
                // Get the seat information including the price
                $seat = Seat::where('seat_id', $seatId)
                    ->where('stand', $request->stand)
                    ->first();

                if (!$seat) {
                    \DB::rollback();
                    return response()->json(['error' => 'Seat ' . $seatId . ' not found or not available'], 400);
                } else {
                    $totalPrice += $seat->price;
                    $ticketSeatRelation = new TicketSeatRelation([
                        'ticket_id' => $ticket->ticket_id,
                        'seat_id' => $seatId
                    ]);
                    $ticketSeatRelation->save();
                }
            }

            $ticket->price = $totalPrice;
            $ticket->save();

            // Create a ticket purchase record
            $ticketPurchase = new TicketPurchase([
                'user_id' => $request->input('user_id'),
                'ticket_id' => $ticket->ticket_id, // Use the ID of the newly created ticket
                'purchase_date' => now(), // Assuming purchase date is the current date and time
            ]);
            $ticketPurchase->save(); // Save the ticket purchase to the database

            // Commit the transaction
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
