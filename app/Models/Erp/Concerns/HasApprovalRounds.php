<?php

namespace App\Models\Erp\Concerns;

use Illuminate\Support\Collection;

/**
 * Approval rounds: each submit creates a new set of erp_approvals rows with the next
 * `round` number. Earlier rounds (e.g. a rejected one) are kept as history; only the
 * latest round drives the workflow. Requires the model to define approvals().
 */
trait HasApprovalRounds
{
    /** Latest round number in the database (0 when never submitted). */
    public function latestApprovalRound(): int
    {
        return (int) $this->approvals()->max('round');
    }

    /** Query for the approval rows of the latest round only. */
    public function currentApprovals()
    {
        return $this->approvals()->where('round', $this->latestApprovalRound());
    }

    /** Latest round's rows from the loaded `approvals` relation (for views). */
    public function currentRoundApprovals(): Collection
    {
        $round = $this->approvals->max('round');

        return $this->approvals->where('round', $round)->sortBy('level')->values();
    }

    /**
     * All rounds for the history UI, newest first: each item has round, submitted_at,
     * submitter and the round's approvals sorted by level.
     */
    public function approvalRounds(): Collection
    {
        $submitterIds = $this->approvals->pluck('submitted_by_id')->filter()->unique();
        $submitters = $submitterIds->isEmpty()
            ? collect()
            : \App\Models\User::whereIn('id', $submitterIds)->get()->keyBy('id');

        return $this->approvals
            ->groupBy(fn ($a) => (int) ($a->round ?: 1))
            ->sortKeysDesc()
            ->map(function ($rows, $round) use ($submitters) {
                $first = $rows->sortBy('id')->first();

                return (object) [
                    'round' => (int) $round,
                    'submitted_at' => $rows->min('created_at'),
                    'submitter' => $first->submitted_by_id ? $submitters->get($first->submitted_by_id) : null,
                    'approvals' => $rows->sortBy('level')->values(),
                ];
            })
            ->values();
    }
}
