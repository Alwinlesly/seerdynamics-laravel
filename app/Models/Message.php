<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'messages';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'type',
        'from_id',
        'to_id',
        'message',
        'created',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created' => 'datetime',
    ];

    /**
     * Get the user who sent the message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_id');
    }
}
