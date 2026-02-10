<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskStatus extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'task_status';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'class',
    ];

    /**
     * Get the tasks for this status.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'status');
    }
}
