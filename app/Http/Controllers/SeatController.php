<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Models\Seat;
use App\Http\Resources\SeatResource;
use Exception;

class SeatController extends Controller
{
    public function index()
    {
        try {
            $seats = Seat::orderBy('stand')->orderBy('seat_number')->get();

            return response()->json(['seats' => SeatResource::collection($seats)], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($game_id, $stand)
    {
        try {
            // Thực hiện truy vấn để lấy thông tin của các ghế
            $seats = Seat::select('seats.*', 't.ticket_id')
                ->leftJoin('ticket_seat_relation AS tsr', 'seats.seat_id', '=', 'tsr.seat_id')
                ->leftJoin('tickets AS t', function ($join) use ($game_id) {
                    $join->on('t.ticket_id', '=', 'tsr.ticket_id')
                        ->where('t.game_id', '=', $game_id);
                })
                ->where('seats.stand', $stand)
                ->orderBy('seats.seat_number')
                ->get();


            // Trả về dữ liệu
            return response()->json(['seats' => $seats]);
        } catch (Exception $e) {
            // Xử lý lỗi nếu có
            return response()->json(['error' => 'Seat not found'], 404);
        }
    }


    public function create(Request $request)
    {
        try {
            $request->validate([
                'stadium_id' => 'required|integer',
                'stand' => 'required|string',
                'quantity' => 'required|integer|min:1',
                'type' => 'required|in:Normal,VIP,VVIP',
                'price' => 'required|numeric|min:0',
            ]);

            $stand = $request->input('stand');
            $quantity = $request->input('quantity');
            $type = $request->input('type');
            $price = $request->input('price');
            $seats = [];

            $lastSeat = Seat::where('stand', $stand)->orderBy('seat_number', 'desc')->first(); // Lấy ghế cuối cùng của dãy stand

            $lastSeatNumber = $lastSeat ? $lastSeat->seat_number : 0;

            // Tạo các ghế dựa trên dãy stand và số lượng ghế đã nhập
            for ((int) $i = 1; $i <= $quantity; $i++) {
                $seatNumber = $lastSeatNumber + $i; // Tạo số ghế với số thứ tự tăng dần từ ghế cuối cùng
                $seatId = $stand . $seatNumber; // Tạo seat_id
                $seat = [
                    'seat_id' => $seatId,
                    'stadium_id' => $request->input('stadium_id'),
                    'seat_number' => $seatNumber,
                    'stand' => $stand,
                    'type' => $type,
                    'price' => $price,
                    'status' => 'available',
                    'created_at' => now(),
                ];
                $seats[] = $seat;
            }

            // Sử dụng transaction để đảm bảo tính toàn vẹn của dữ liệu
            DB::transaction(function () use ($seats) {
                Seat::insert($seats);
            });

            return response()->json(['message' => 'Seats created successfully'], 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $request->validate([
                'stadium_id' => 'required|integer|exists:stadiums,stadium_id',
                'stand' => 'required|string',
                'type' => 'required|string|in:Normal,VIP,VVIP',
                'price' => 'required|numeric|min:0',
                'status' => 'string|in:available,reserved,repairing',
                'start_seat' => 'required|integer|min:1',
                'end_seat' => 'required|integer|gte:start_seat',
            ], [
                'stadium_id.exists' => 'No found stadium',
                'type.in' => 'The type field must be one of: Normal, VIP, VVIP.',
            ]);

            $stadiumId = $request->input('stadium_id');
            $stand = $request->input('stand');
            $type = $request->input('type');
            $price = $request->input('price');
            $status = $request->input('status');
            $startSeat = $request->input('start_seat');
            $endSeat = $request->input('end_seat');

            if (Seat::where('stadium_id', $stadiumId)->where('stand', $stand)->count() == 0) {
                return response()->json([
                    'error' => 'No found any seats'
                ], 400);
            }

            // Lấy seat_number lớn nhất và nhỏ nhất trong dãy ghế tương ứng
            $minSeatNumber = Seat::where('stadium_id', $stadiumId)
                ->where('stand', $stand)
                ->min('seat_number');

            $maxSeatNumber = Seat::where('stadium_id', $stadiumId)
                ->where('stand', $stand)
                ->max('seat_number');

            // Kiểm tra xem start_seat và end_seat có hợp lệ không
            if ($startSeat < $minSeatNumber || $endSeat > $maxSeatNumber) {
                $availableSeatsRange = "Available seat range: $minSeatNumber - $maxSeatNumber";
                return response()->json([
                    'error' => 'Invalid seat range: ' . $availableSeatsRange,
                ], 400);
            }

            // Cập nhật giá và loại cho các ghế trong phạm vi từ start_seat đến end_seat
            Seat::where('stadium_id', $stadiumId)
                ->where('stand', $stand)
                ->where('seat_number', '>=', $startSeat)
                ->where('seat_number', '<=', $endSeat)
                ->update(['type' => $type, 'price' => $price, 'status' => $status], );

            return response()->json(['message' => 'Seats updated successfully'], 200);

        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $request->validate([
                'stadium_id' => 'required|integer|exists:stadiums,stadium_id',
                'stand' => 'required|string|in:E,S,W,N', // Thay E, S, W, N bằng các giá trị thực tế
                'start_seat' => 'required|integer|min:1',
                'end_seat' => 'required|integer|gte:start_seat',
            ], [
                'stand.in' => 'The stand field must be one of: E, S, W, N.', // Thông báo lỗi cho trường stand
            ]);

            $stadiumId = $request->input('stadium_id');
            $stand = $request->input('stand');
            $startSeat = $request->input('start_seat');
            $endSeat = $request->input('end_seat');

            // Lấy seat_number lớn nhất và nhỏ nhất trong dãy ghế tương ứng
            $minSeatNumber = Seat::where('stadium_id', $stadiumId)
                ->where('stand', $stand)
                ->min('seat_number');

            $maxSeatNumber = Seat::where('stadium_id', $stadiumId)
                ->where('stand', $stand)
                ->max('seat_number');

            // Kiểm tra xem start_seat và end_seat có hợp lệ không
            if ($startSeat < $minSeatNumber || $endSeat > $maxSeatNumber) {
                $availableSeatsRange = "Available seat range: $minSeatNumber - $maxSeatNumber";
                return response()->json([
                    'error' => 'Invalid seat range: ' . $availableSeatsRange,
                ], 400);
            }

            // Xóa các ghế trong phạm vi từ start_seat đến end_seat
            Seat::where('stadium_id', $stadiumId)
                ->where('stand', $stand)
                ->where('seat_number', '>=', $startSeat)
                ->where('seat_number', '<=', $endSeat)
                ->delete();

            return response()->json(['message' => 'Seats deleted successfully'], 200);

        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
