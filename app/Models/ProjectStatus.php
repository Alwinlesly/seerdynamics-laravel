<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectStatus extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'project_status';

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
     * Get the projects for this status.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'status');
    }
}
