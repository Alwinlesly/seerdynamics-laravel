<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Group extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description'];
    
    public $timestamps = false;
    public function users()
    {
        return $this->belongsToMany(User::class, 'users_groups', 'group_id', 'user_id');
    }
}