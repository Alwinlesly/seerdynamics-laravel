<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'projects';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'project_id',
        'title',
        'description',
        'client_id',
        'status',
        'starting_date',
        'ending_date',
        'actual_starting_date',
        'actual_ending_date',
        'budget',
        'contract',
        'ptype',
        'is_visible',
        'created',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'starting_date' => 'date',
        'ending_date' => 'date',
        'actual_starting_date' => 'date',
        'actual_ending_date' => 'date',
        'created' => 'datetime',
        'is_visible' => 'integer',
    ];

    /**
     * Get the client that owns the project.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Alias for client relationship (for backward compatibility).
     */
    public function customer(): BelongsTo
    {
        return $this->client();
    }

    /**
     * Get the project status.
     */
    public function projectStatus(): BelongsTo
    {
        return $this->belongsTo(ProjectStatus::class, 'status');
    }

    /**
     * Get the users assigned to the project.
     */
   public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_users', 'project_id', 'user_id');
    }

    /**
     * Get the tasks for the project.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    /**
     * Get the files for the project.
     */
    public function files(): HasMany
    {
        return $this->hasMany(MediaFile::class, 'type_id')->where('type', 'project');
    }

    /**
     * Get the timesheet entries for the project.
     */
    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class, 'project_id');
    }

    /**
     * Scope a query to only include projects for a specific client.
     */
    public function scopeForClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope a query to only include visible projects.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', 0);
    }

    /**
     * Get the total number of tasks.
     */
    public function getTotalTasksAttribute()
    {
        return $this->tasks()->count();
    }

    /**
     * Get the number of completed tasks.
     */
    public function getCompletedTasksAttribute()
    {
        return $this->tasks()->where('status', 4)->count(); // Status 4 = Completed
    }

    /**
     * Get the project progress percentage.
     */
    public function getProgressAttribute()
    {
        $total = $this->total_tasks;
        if ($total == 0) {
            return 0;
        }
        return round(($this->completed_tasks / $total) * 100);
    }
}
