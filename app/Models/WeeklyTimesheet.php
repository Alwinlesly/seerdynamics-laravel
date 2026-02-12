<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyTimesheet extends Model
{
    protected $table = 'weekly_timesheet';
    
    public $timestamps = false;
    
    protected $fillable = [
        'user_id',
        'work_week',
        'start_date',
        'end_date',
        'billable_hours',
        'non_billable_hours',
        'submit_or_draft',
        'created',
        'status',
    ];
    
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'created' => 'datetime',
        'billable_hours' => 'decimal:2',
        'non_billable_hours' => 'decimal:2',
    ];
    
    /**
     * Get the user (consultant) who owns this timesheet
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    /**
     * Get all task entries for this timesheet
     */
    public function tasks()
    {
        return $this->hasMany(TimesheetTask::class, 'timesheet_id');
    }
    
    /**
     * Check if timesheet is in draft status
     */
    public function isDraft()
    {
        return $this->submit_or_draft === 'draft';
    }
    
    /**
     * Check if timesheet is submitted
     */
    public function isSubmitted()
    {
        return $this->submit_or_draft === 'submit';
    }
}
