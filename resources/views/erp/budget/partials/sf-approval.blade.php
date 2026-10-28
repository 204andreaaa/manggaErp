{{--
  Salesforce "Approval History" related list. Grouped by approval round in the order
  they happened (round 1 on top); each round starts with its "Approval Request Submitted" row (who
  submitted it and when), followed by its steps by level.
  $approvals: ErpApproval collection (all rounds);
  $submittedBy: fallback submitter name for rows that predate submitted_by_id;
  $submitForm: optional HTML for the Submit for Approval button (already escaped).
--}}
@php
  $statusBadge = fn ($s) => match ($s) {
      'Approved' => 'bg-success', 'Rejected' => 'bg-danger', 'Pending' => 'bg-warning',
      'Cancelled' => 'bg-secondary', default => 'bg-label-secondary',
  };
  $submitterIds = $approvals->pluck('submitted_by_id')->filter()->unique();
  $submitters = $submitterIds->isEmpty() ? collect() : \App\Models\User::whereIn('id', $submitterIds)->pluck('name', 'id');
  $rounds = $approvals->groupBy(fn ($a) => (int) ($a->round ?: 1))->sortKeys();
@endphp
<div class="sf-card">
  <div class="sf-card-head">
    <h6>Approval History</h6>
    <div class="sf-actions">{!! $submitForm ?? '' !!}</div>
  </div>
  @if($approvals->isEmpty())
    <div class="sf-empty">No records to display</div>
  @else
    <div class="table-responsive">
      <table class="sf-table sf-approval">
        <thead>
          <tr><th>Action</th><th>Date</th><th>Status</th><th>Assigned To</th><th>Actual Approver</th><th>Comments</th><th>Overall Status</th></tr>
        </thead>
        <tbody>
          @foreach($rounds as $roundNo => $roundSteps)
            @php
              $roundSubmitterId = $roundSteps->pluck('submitted_by_id')->filter()->first();
              $roundSubmitter = ($roundSubmitterId ? $submitters->get($roundSubmitterId) : null) ?? ($submittedBy ?? '-');
              $roundSubmittedAt = $roundSteps->min('created_at');
            @endphp
            <tr class="sf-step"><td colspan="7">Approval Request Submitted <span class="badge bg-label-primary ms-1">Round {{ $roundNo }}</span></td></tr>
            <tr>
              <td></td>
              <td>{{ $roundSubmittedAt?->format('Y/m/d H:i') }}</td>
              <td>Submitted</td>
              <td>{{ $roundSubmitter }}</td>
              <td>{{ $roundSubmitter }}</td>
              <td></td><td></td>
            </tr>
            @foreach($roundSteps->sortBy('level') as $step)
              @php
                $canAct = $step->status === 'Pending' && (auth()->user()->hasRole('superadmin') || $step->assigned_to_user_id == auth()->id());
              @endphp
              <tr class="sf-step">
                <td colspan="6">Step {{ $step->level }}: Approve or Reject by {{ $step->assignedUser?->name ?? ($step->assignedRole?->name ?? '-') }}</td>
                <td class="sf-overall"><span class="badge {{ $statusBadge($step->status) }}">{{ $step->status }}</span></td>
              </tr>
              <tr>
                <td>
                  @if($canAct)
                    <div class="d-flex gap-1">
                      <button type="button" class="btn btn-success btn-xs btn-sm py-0 px-2" onclick="openApprove({{ $step->id }})">Approve</button>
                      <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="openReject({{ $step->id }})">Reject</button>
                    </div>
                  @endif
                </td>
                <td>{{ $step->approved_at?->format('Y/m/d H:i') ?? '' }}</td>
                <td>{{ $step->status }}@if($step->is_override) <span class="badge bg-label-dark">Override</span>@endif</td>
                <td>{{ $step->assignedUser?->name ?? ($step->assignedRole?->name ?? '-') }}</td>
                <td>{{ $step->actualApprover?->name ?? '' }}</td>
                <td class="small">{{ $step->comments }}</td>
                <td></td>
              </tr>
            @endforeach
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
