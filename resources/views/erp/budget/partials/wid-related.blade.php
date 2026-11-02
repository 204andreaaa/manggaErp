{{--
  Related lists of everything charged to a set of WIDs (see App\Services\WorkItemUsage).
  Expects: $usage, $money; optional $showWid (false on a single WID's page).
--}}
@php
  $showWid = $showWid ?? true;
  $user = auth()->user();
  $statusBadge = fn ($s) => match ($s) {
      'Approved', 'Completed', 'Paid' => 'bg-label-success',
      'Rejected', 'Cancelled' => 'bg-label-danger',
      'Submitted', 'Pending' => 'bg-label-warning',
      default => 'bg-label-secondary',
  };
@endphp

{{-- Annual Budget Plans --}}
<div class="sf-card" id="rel-annual" style="--sf-accent: #1798c1">
  <div class="sf-card-head">
    <h6><i class="bx bx-calendar me-1"></i>Annual Budget Plans</h6>
    @if($user->hasPermission('annual_budget_plans.view'))
      <div class="sf-actions"><a href="{{ route('erp.annual-budget-plans.index') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>New Annual Budget Plan</a></div>
    @endif
  </div>
  @if($usage['annual']->isEmpty())
    <div class="sf-empty">No records to display</div>
  @else
    <div class="table-responsive">
      <table class="sf-table">
        <thead>
          <tr>
            <th>Anual Budget ID</th><th>Year</th>@if($showWid)<th>WID</th>@endif<th>Area</th><th>Work Type</th><th>Work Subtype</th><th>Currency</th>
            <th class="text-end">Budget Amount</th><th class="text-end">Total Expense Plan</th><th class="text-end">Total Actual Expense</th>
          </tr>
        </thead>
        <tbody>
          @foreach($usage['annual'] as $plan)
            <tr>
              <td><a class="sf-link" href="{{ route('erp.annual-budget-plans.show', $plan) }}">{{ $plan->code }}</a></td>
              <td>{{ $plan->year }}</td>
              @if($showWid)
                <td>@if($plan->workItem)<a class="sf-link" href="{{ route('erp.work-items.show', $plan->workItem) }}">{{ $plan->workItem->wid_code }}</a>@else - @endif</td>
              @endif
              <td>{{ $plan->area }}</td>
              <td>{{ $plan->work_type }}</td>
              <td>{{ $plan->work_subtype }}</td>
              <td>{{ $plan->currency ?: 'IDR' }}</td>
              <td class="text-end">{{ $money($plan->budget_amount) }}</td>
              <td class="text-end">{{ $money($plan->total_expense_plan) }}</td>
              <td class="text-end">{{ $money($plan->total_actual_expense) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

{{-- Request Forms --}}
<div class="sf-card" id="rel-rf" style="--sf-accent: #9b6bd3">
  <div class="sf-card-head"><h6><i class="bx bx-file me-1"></i>Request Forms</h6></div>
  @if($usage['requestForms']->isEmpty())
    <div class="sf-empty">No records to display</div>
  @else
    <div class="table-responsive">
      <table class="sf-table">
        <thead>
          <tr><th>RF No</th><th>Date</th><th>Record Type</th>@if($showWid)<th>WID</th>@endif<th>Requestor</th><th class="text-end">Total Amount</th><th>Status</th></tr>
        </thead>
        <tbody>
          @foreach($usage['requestForms'] as $rf)
            <tr>
              <td><a class="sf-link" href="{{ route('erp.request-form.show', $rf) }}">{{ $rf->rf_no }}</a></td>
              <td>{{ $rf->rf_date?->format('Y/m/d') }}</td>
              <td>{{ $rf->record_type_label }}</td>
              @if($showWid)<td>{{ $rf->wid_codes->implode(', ') ?: '-' }}</td>@endif
              <td>{{ $rf->requestor ?: '-' }}</td>
              <td class="text-end">{{ $money($rf->total_amount) }}</td>
              <td><span class="badge {{ $statusBadge($rf->status) }}">{{ $rf->status }}</span></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

{{-- Purchase Orders --}}
<div class="sf-card" id="rel-po" style="--sf-accent: #3a8ee0">
  <div class="sf-card-head"><h6><i class="bx bx-cart me-1"></i>Purchase Orders</h6></div>
  @if($usage['purchaseOrders']->isEmpty())
    <div class="sf-empty">No records to display</div>
  @else
    <div class="table-responsive">
      <table class="sf-table">
        <thead>
          <tr>
            <th>PO No</th><th>Date</th><th>Supplier</th><th>RF No</th>@if($showWid)<th>WID</th>@endif
            <th class="text-end" title="Nilai baris PO yang dibebankan ke WID ini">Amount to WID</th><th class="text-end">PO Total (incl. tax)</th><th>Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach($usage['purchaseOrders'] as $po)
            <tr>
              <td><a class="sf-link" href="{{ route('erp.purchase-orders.show', $po) }}">{{ $po->po_no }}</a></td>
              <td>{{ $po->date ? \Carbon\Carbon::parse($po->date)->format('Y/m/d') : '-' }}</td>
              <td>{{ $po->supplier->name ?? '-' }}</td>
              <td>@if($po->requestForm)<a class="sf-link" href="{{ route('erp.request-form.show', $po->requestForm) }}">{{ $po->requestForm->rf_no }}</a>@else - @endif</td>
              @if($showWid)<td>{{ $po->wid_codes->implode(', ') }}</td>@endif
              <td class="text-end fw-semibold">{{ $money($po->charged_amount) }}</td>
              <td class="text-end">{{ $money($po->total_po_amount_with_tax) }}</td>
              <td><span class="badge {{ $statusBadge($po->status) }}">{{ $po->status }}</span></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

{{-- Advance Request --}}
<div class="sf-card" id="rel-advance" style="--sf-accent: #d27d5b">
  <div class="sf-card-head">
    <h6><i class="bx bx-wallet me-1"></i>Advance Request</h6>
    @if($user->hasPermission('advance_requests.create'))
      <div class="sf-actions"><a href="{{ route('erp.advance-requests.create') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>New Advance Request</a></div>
    @endif
  </div>
  @if($usage['advanceRequests']->isEmpty())
    <div class="sf-empty">No records to display</div>
  @else
    <div class="table-responsive">
      <table class="sf-table">
        <thead>
          <tr><th>Advance Request Id</th><th>Date</th><th>Expense Type</th>@if($showWid)<th>Work Id</th>@endif<th class="text-end">Amount</th><th class="text-end">Outstanding</th><th>Status</th><th>Payment</th></tr>
        </thead>
        <tbody>
          @foreach($usage['advanceRequests'] as $a)
            <tr>
              <td><a class="sf-link" href="{{ route('erp.advance-requests.show', $a) }}">{{ $a->code }}</a></td>
              <td>{{ $a->advance_request_date?->format('Y/m/d') }}</td>
              <td>{{ $a->budgetPlanDetail->expense_type ?? '-' }}</td>
              @if($showWid)
                <td>@if($a->work)<a class="sf-link" href="{{ route('erp.work-items.show', $a->work) }}">{{ $a->work->wid_code }}</a>@else - @endif</td>
              @endif
              <td class="text-end">{{ $money($a->total_amount_advance) }}</td>
              <td class="text-end">{{ $money($a->advance_outstanding) }}</td>
              <td><span class="badge {{ $statusBadge($a->status) }}">{{ $a->status }}</span></td>
              <td>{{ $a->payment_status }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

{{-- Expense Declaration --}}
<div class="sf-card" id="rel-declaration" style="--sf-accent: #6fb1ad">
  <div class="sf-card-head">
    <h6><i class="bx bx-receipt me-1"></i>Expense Declaration</h6>
    @if($user->hasPermission('expense_declarations.create'))
      <div class="sf-actions"><a href="{{ route('erp.expense-declarations.create') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>New Expense Declaration</a></div>
    @endif
  </div>
  @if($usage['declarations']->isEmpty())
    <div class="sf-empty">No records to display</div>
  @else
    <div class="table-responsive">
      <table class="sf-table">
        <thead>
          <tr><th>Expense Declaration Id</th><th>Record Type</th><th>Advance Request</th><th>Expense Type</th>@if($showWid)<th>Work Id</th>@endif<th class="text-end">Amount</th><th>Status</th></tr>
        </thead>
        <tbody>
          @foreach($usage['declarations'] as $d)
            <tr>
              <td><a class="sf-link" href="{{ route('erp.expense-declarations.show', $d) }}">{{ $d->code }}</a></td>
              <td>{{ str_replace('_', ' ', $d->record_type) }}</td>
              <td>@if($d->advanceRequest)<a class="sf-link" href="{{ route('erp.advance-requests.show', $d->advanceRequest) }}">{{ $d->advanceRequest->code }}</a>@else - @endif</td>
              <td>{{ $d->expense_type }}</td>
              @if($showWid)
                <td>@if($d->work)<a class="sf-link" href="{{ route('erp.work-items.show', $d->work) }}">{{ $d->work->wid_code }}</a>@else - @endif</td>
              @endif
              <td class="text-end">{{ $money($d->converted_amount_idr) }}</td>
              <td><span class="badge {{ $statusBadge($d->status) }}">{{ $d->status }}</span></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
