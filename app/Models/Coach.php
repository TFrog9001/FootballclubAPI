<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coach extends Model
{
    use HasFactory;

    protected $primaryKey = 'user_id'; // Thiết lập khóa chính

    protected $fillable = [
        'user_id', // Khóa chính của người dùng
        'position',
        'wins',
        'losses',
        'draws',
    ];
    public $incrementing = false; // Sử dụng ID không tự tăng

    public $timestamps = false; // Không sử dụng timestamps

    // Định nghĩa mối quan hệ với bảng Users
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'user_id', 'user_id');
    }
}
