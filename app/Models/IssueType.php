<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IssueType extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'issue_types';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'issue_type_id';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'issue_type',
    ];

    /**
     * Get the tasks for this issue type.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'issue_type', 'issue_type_id');
    }
}
