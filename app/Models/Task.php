<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'tasks';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'description',
        'project_id',
        'status',
        'priority',
        'due_date',
        'created',
        'created_by',
        'issue_type',
        'ticket_id',
        'service',
        'attachment',
        'additional_mail',
        'estimate',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'due_date' => 'date',
        'created' => 'datetime',
    ];

    /**
     * Get the project that owns the task.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the task status.
     */
    public function taskStatus(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class, 'status');
    }

    /**
     * Get the task priority.
     */
    public function taskPriority(): BelongsTo
    {
        return $this->belongsTo(Priority::class, 'priority');
    }

    /**
     * Get the user who created the task.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the issue type.
     */
    public function issueType(): BelongsTo
    {
        return $this->belongsTo(IssueType::class, 'issue_type', 'issue_type_id');
    }

    /**
     * Get the users assigned to the task.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_users', 'task_id', 'user_id');
    }

    /**
     * Get the files for the task.
     */
    public function files(): HasMany
    {
        return $this->hasMany(MediaFile::class, 'type_id')->where('type', 'task');
    }

    /**
     * Get the comments for the task.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'task_id')->orderBy('created_at', 'desc');
    }

    /**
     * Get the timesheet entries for the task.
     */
    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class, 'task_id');
    }

    /**
     * Get the estimates for the task.
     */
    public function estimates(): HasMany
    {
        return $this->hasMany(TaskEstimate::class, 'task_id');
    }
}
