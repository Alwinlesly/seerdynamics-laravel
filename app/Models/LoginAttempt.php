<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LoginAttempt extends Model
{
    protected $fillable = ['ip_address', 'login', 'time'];
    
    public $timestamps = false;
}