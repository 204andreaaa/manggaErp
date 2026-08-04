<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Role;

class ErpApprovalConfig extends Model
{
    protected $connection = 'tenant';
    
    protected $table = 'erp_approval_configs';

    protected $fillable = [
        'record_type',
        'level',
        'name',
        'role_id',
        'user_id',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
