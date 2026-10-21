<?php

namespace App\Models\Erp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ErpBudgetParent extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'budget_code',
        'name',
        'kam_user_id',
        'total_budget',
        'remaining_budget',
        'status',
    ];

    public function subProjects()
    {
        return $this->hasMany(ErpSubProject::class, 'budget_parent_id');
    }

    public function annualBudgetPlans()
    {
        return $this->hasMany(AnnualBudgetPlan::class, 'budget_parent_id');
    }

    public function kam()
    {
        return $this->belongsTo(User::class, 'kam_user_id');
    }

    // Sisa pagu = Total Budget dikurangi semua alokasi WID di bawahnya
    public function allocatedToWorkItems(): float
    {
        return (float) ErpWorkItem::whereHas('subProject', fn ($q) => $q->where('budget_parent_id', $this->id))
            ->sum('allocated_budget');
    }

    public static function recalculateRemaining(...$ids): void
    {
        foreach (static::whereIn('id', array_filter($ids))->get() as $parent) {
            $parent->update(['remaining_budget' => $parent->total_budget - $parent->allocatedToWorkItems()]);
            \App\Helpers\NotificationHelper::pushLive('budget_parent', $parent->id, 'updated');
        }
    }
}
