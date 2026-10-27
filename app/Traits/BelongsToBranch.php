<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * @req FR-AUTH-04, FR-BR-01, FR-BR-02
 */
trait BelongsToBranch
{
    public static function bootBelongsToBranch(): void
    {
        static::addGlobalScope('branch_isolation', function (Builder $builder) {
            if (! auth()->check()) {
                return;
            }

            /** @var User $user */
            $user = auth()->user();

            // Owner and Central Admin have global multi-branch access or context branch access
            if (in_array($user->role, ['owner', 'central_admin'])) {
                if (session()->has('context_branch_id') && session('context_branch_id')) {
                    $builder->where($builder->getModel()->getTable() . '.branch_id', session('context_branch_id'));
                }
                return;
            }

            // Branch managers, warehouse staff, and cashiers are strictly scoped to their assigned branch
            if ($user->branch_id) {
                $builder->where($builder->getModel()->getTable() . '.branch_id', $user->branch_id);
            }
        });

        static::creating(function (Model $model) {
            if (! isset($model->branch_id) && auth()->check()) {
                /** @var User $user */
                $user = auth()->user();
                if ($user->branch_id && ! in_array($user->role, ['owner', 'central_admin'])) {
                    $model->branch_id = $user->branch_id;
                }
            }
        });
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }
}
