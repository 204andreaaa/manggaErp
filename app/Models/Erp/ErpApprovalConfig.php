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
        'min_amount',
        'max_amount',
        'is_project'
    ];

    /** Steps whose amount range (each end optional) contains $amount. */
    public function scopeForAmount($query, float $amount)
    {
        return $query
            ->where(fn ($q) => $q->whereNull('min_amount')->orWhere('min_amount', '<=', $amount))
            ->where(fn ($q) => $q->whereNull('max_amount')->orWhere('max_amount', '>=', $amount));
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
