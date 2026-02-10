<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable
{
    use HasFactory, Notifiable;
    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'company',
        'phone',
        'profile',
        'contact_person_desg',
        'address',
        'country',
        'cuser_customer',
        'customer_code',
        'is_company',
        'active',
        'ip_address',
        'created_on',
        'last_login',
    ];
    protected $hidden = [
        'password',
        'remember_code',
        'activation_code',
        'forgotten_password_code',
    ];
    protected $casts = [
        'active' => 'boolean',
        'is_company' => 'boolean',
        'created_on' => 'integer',
        'last_login' => 'integer',
    ];
    public $timestamps = false;
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'users_groups', 'user_id', 'group_id');
    }
    public function isAdmin()
    {
        return $this->groups()->where('name', 'admin')->exists();
    }
    public function inGroup($groupId)
    {
        return $this->groups()->where('groups.id', $groupId)->exists();
    }
    public function getRememberTokenName()
    {
        return 'remember_code';
    }
}