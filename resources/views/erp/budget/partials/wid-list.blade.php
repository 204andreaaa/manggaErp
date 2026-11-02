{{-- Works (WID) related list. Expects: $workItems, $money; optional $showProject. --}}
@php $showProject = $showProject ?? true; @endphp
<div class="sf-card" id="rel-works" style="--sf-accent: #4bc076">
  <div class="sf-card-head">
    <h6><i class="bx bx-task me-1"></i>Works (WID)</h6>
    @if(auth()->user()->hasPermission('work_items.view') || auth()->user()->hasPermission('budgets.view'))
      <div class="sf-actions"><a href="{{ route('erp.work-items.index') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>New Work</a></div>
    @endif
  </div>
  @if($workItems->isEmpty())
    <div class="sf-empty">No records to display</div>
  @else
    <div class="table-responsive">
      <table class="sf-table">
        <thead><tr><th>Work Id</th><th>Work Name</th>@if($showProject)<th>Project</th>@endif<th class="text-end">Allocated</th><th class="text-end">Remaining</th></tr></thead>
        <tbody>
          @foreach($workItems as $w)
            <tr>
              <td><a class="sf-link fw-semibold" href="{{ route('erp.work-items.show', $w) }}">{{ $w->wid_code }}</a></td>
              <td>{{ $w->name }}</td>
              @if($showProject)
                <td>@if($w->subProject)<a class="sf-link" href="{{ route('erp.sub-projects.show', $w->subProject) }}">{{ $w->subProject->name }}</a>@else - @endif</td>
              @endif
              <td class="text-end">{{ $money($w->allocated_budget) }}</td>
              <td class="text-end {{ (float) $w->remaining_budget <= 0 ? 'text-danger' : 'text-success' }} fw-semibold">{{ $money($w->remaining_budget) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
