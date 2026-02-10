<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskComment extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'task_comments';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'task_id',
        'user_id',
        'message',
        'attachment',
    ];

    /**
     * Get the task that owns the comment.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * Get the user who created the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
