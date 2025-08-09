<?php

namespace App\Models;

use Illuminate->Database\Eloquent\Factories\HasFactory;
use Illuminate->Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'file_path',
        'summary',
        'status',
        'category',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the document.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
