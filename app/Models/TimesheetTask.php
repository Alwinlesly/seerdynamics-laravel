<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimesheetTask extends Model
{
    protected $table = 'timesheet_tasks';
    
    public $timestamps = false;
    
    protected $fillable = [
        'timesheet_id',
        'project_id',
        'task_description',
        'monday_hours',
        'tuesday_hours',
        'wednesday_hours',
        'thursday_hours',
        'friday_hours',
        'saturday_hours',
        'sunday_hours',
        'total_hours',
        'is_billable',
    ];
    
    protected $casts = [
        'monday_hours' => 'decimal:2',
        'tuesday_hours' => 'decimal:2',
        'wednesday_hours' => 'decimal:2',
        'thursday_hours' => 'decimal:2',
        'friday_hours' => 'decimal:2',
        'saturday_hours' => 'decimal:2',
        'sunday_hours' => 'decimal:2',
        'total_hours' => 'decimal:2',
        'is_billable' => 'boolean',
    ];
    
    /**
     * Get the timesheet that owns this task
     */
    public function timesheet()
    {
        return $this->belongsTo(WeeklyTimesheet::class, 'timesheet_id');
    }
    
    /**
     * Get the project associated with this task
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
