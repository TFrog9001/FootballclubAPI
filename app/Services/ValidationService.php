<?php

namespace App\Services;

use Illuminate\Validation\Rule;


class ValidationService
{
    public function getUserValidationRules($request)
    {
        return [
            'name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,Email',
            'password' => 'required|string|min:8|max:50',
            'date_of_birth' => [
                'date',
                'before_or_equal:' . now()->format('Y-m-d'),
            ],
            'nationality' => 'string|max:50',
            'flag' => 'string|max:10',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp,PNG,JPG|max:2048',
        ];
    }

    public function getCoachValidationRules($request)
    {
        return [
            'position' => 'in:head,assistant,fitness,goalkeeping,youth,tactical,rehabilitation,consultant',
            'wins' => 'integer',
            'losses' => 'integer',
            'draws' => 'integer',
            'detail' => 'string',
        ];
    }

    public function getPlayerValidationRules($request)
    {
        return [
            'position' => 'string|max:50',
            'jersey_number' => 'unique:players,jersey_number|integer|min:1|max:99',
            'detail' => 'nullable|string',
        ];
    }

    public function getStadiumValidationRules($request)
    {
        return [
            'name' => 'required|unique:stadiums,name|max:100',
            'address' => 'required|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp,PNG,JPG|max:2048',
            'capacity' => 'required|integer|min:0'
        ];
    }

    public function getClubValidationRules($request)
    {
        return [
            'name' => 'required|unique:clubs,name|max:100',
            'image' => 'image|mimes:jpeg,png,jpg,webp,PNG,JPG|max:2048',
        ];
    }

    public function getGameValidationRules($request)
    {
        return [
            'club_id' => 'required|exists:clubs,club_id',
            'stadium_id' => 'required|exists:stadiums,stadium_id',
            'game_date' => 'required|date',
            'game_time' => 'required|date_format:H:i:s',
            'goals_scored' => 'integer|min:0',
            'goals_conceded' => 'integer|min:0',
            'result' => 'string|max:5',
            'state' => 'string|max:100',
            'host' => 'integer',
            'remaining_seats' => 'integer',
        ];
    }

    public function getGameDetailValidationRules($request)
    {
        return [
            'game_id' => 'required|exists:games,game_id',
            'user_id' => 'nullable|exists:users,user_id',
            'player_name' => 'required|string|max:50',
            'jersey_number' => 'required|integer|min:1|max:99',
            'is_away' => 'required|boolean',
            'type' => 'required|string|max:100',
            'time' => 'required|string|max:10',
        ];
    }

    public function getContractValidationRules($request)
    {
        return [
            'user_id' => 'required|exists:users,user_id',
            'date_created' => 'required|date',
            'expiration_date' => 'required|date|after_or_equal:date_created',
            'salary' => 'required|numeric|min:0',
            'pdf' => 'required|file|mimes:pdf|max:10240',
            'type' => ['required', Rule::in(['advertisement', 'individual', 'sponsorship', 'rental'])], // Thêm quy tắc cho trường "type"
        ];
    }

}
