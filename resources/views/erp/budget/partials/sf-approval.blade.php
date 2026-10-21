{{--
  Salesforce "Approval History" related list. Newest step on top, each step as
  a coloured header row with its overall status, then the detail row.
  $approvals: ErpApproval collection;
  $submittedAt / $submittedBy: shown as the "Approval Request Submitted" row;
  $submitForm: optional HTML for the Submit for Approval button (already escaped).
--}}
@php
  $statusBadge = fn ($s) => match ($s) {
      'Approved' => 'bg-success', 'Rejected' => 'bg-danger', 'Pending' => 'bg-warning',
      'Cancelled' => 'bg-secondary', default => 'bg-label-secondary',
  };
  $steps = $approvals->sortByDesc('level');
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
          @foreach($steps as $step)
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
              <td>{{ in_array($step->status, ['Approved', 'Rejected']) ? $step->status : ($step->status === 'Pending' ? 'Pending' : '') }}@if($step->is_override) <span class="badge bg-label-dark">Override</span>@endif</td>
              <td>{{ $step->assignedUser?->name ?? ($step->assignedRole?->name ?? '-') }}</td>
              <td>{{ $step->actualApprover?->name ?? '' }}</td>
              <td class="small">{{ $step->comments }}</td>
              <td></td>
            </tr>
          @endforeach
          <tr class="sf-step"><td colspan="7">Approval Request Submitted</td></tr>
          <tr>
            <td></td>
            <td>{{ $submittedAt?->format('Y/m/d H:i') }}</td>
            <td>Submitted</td>
            <td>{{ $submittedBy ?? '-' }}</td>
            <td>{{ $submittedBy ?? '-' }}</td>
            <td></td><td></td>
          </tr>
        </tbody>
      </table>
    </div>
  @endif
</div>
