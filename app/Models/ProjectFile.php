<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectFile extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'file_name',
        'file_type',
        'file_size',
    ];
    
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function getFileSizeFormattedAttribute()
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
