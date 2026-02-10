<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskEstimate extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'task_estimate';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'task_id',
        'user_id',
        'estimate_hours',
        'estimate_status',
        'estimate_approvedby',
        'estimate_approvedon',
        'created',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created' => 'datetime',
        'estimate_approvedon' => 'datetime',
        'estimate_status' => 'integer',
    ];

    /**
     * Get the task for this estimate.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * Get the user who created the estimate.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who approved the estimate.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'estimate_approvedby');
    }
}
