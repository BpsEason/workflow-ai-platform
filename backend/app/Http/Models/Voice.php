<?php

namespace App\Models;

use Illuminate->Database\Eloquent\Factories\HasFactory;
use Illuminate->Database->Eloquent\Model;

class Voice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'speaker', // 'user' 或 'assistant'
        'text',
        'audio_path', // 僅對用戶語音有意義
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the voice record.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
