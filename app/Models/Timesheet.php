<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timesheet extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'timesheet';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'project_id',
        'task_id',
        'starting_time',
        'ending_time',
        'created',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'starting_time' => 'datetime',
        'ending_time' => 'datetime',
        'created' => 'datetime',
    ];

    /**
     * Get the user for this timesheet entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the project for this timesheet entry.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the task for this timesheet entry.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
