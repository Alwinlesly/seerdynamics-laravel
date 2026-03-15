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
        'username',
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
        'forgotten_password_selector',
        'forgotten_password_code',
        'forgotten_password_time',
        'activation_selector',
        'activation_code',
        'remember_selector',
        'remember_code',
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

    /**
     * Get parent company ID for customer admin users.
     * Mirrors CI: Users_model::get_cuser_parent_company_id()
     */
    public function getCuserParentCompanyId()
    {
        return $this->cuser_customer ?: null;
    }

    /**
     * Get all client IDs that this customer user should see data for.
     * For group 3 users: returns [own_id, parent_company_id]
     * This matches CI logic: WHERE (p.client_id=$user_id or p.client_id=$cuser_customer_id)
     */
    public function getCustomerClientIds()
    {
        $ids = [$this->id];
        $parentId = $this->getCuserParentCompanyId();
        if ($parentId && $parentId != $this->id) {
            $ids[] = $parentId;
        }
        return $ids;
    }

    public function getRememberTokenName()
    {
        return 'remember_code';
    }
}
