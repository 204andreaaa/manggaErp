@extends('layouts.home')

@section('title', 'Payment Terms (TOP)')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger alert-dismissible" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <div class="text-muted small">Master Data ERP</div>
      <h4 class="mb-0 fw-bold">Payment Terms (TOP)</h4>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
      <i class="bx bx-plus me-1"></i> Create TOP
    </button>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Name</th>
            <th>Schedule</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($terms as $term)
            <tr>
              <td class="fw-bold">{{ $term->name }}</td>
              <td>
                @if($term->term_schedule)
                  <ul class="mb-0 ps-3">
                  @foreach($term->term_schedule as $sch)
                    <li><span class="fw-semibold">{{ $sch['name'] }}</span> : {{ $sch['percentage'] }}%</li>
                  @endforeach
                  </ul>
                @else
                  <span class="text-muted">No schedule defined (defaults to 100% full payment)</span>
                @endif
              </td>
              <td>
                @if($term->is_active)
                  <span class="badge bg-label-success">Active</span>
                @else
                  <span class="badge bg-label-secondary">Inactive</span>
                @endif
              </td>
              <td>
                <button class="btn btn-sm btn-icon btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $term->id }}">
                  <i class="bx bx-edit"></i>
                </button>
                <form action="{{ route('erp.payment-terms.destroy', $term) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this term?');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-icon btn-outline-danger">
                    <i class="bx bx-trash"></i>
                  </button>
                </form>
              </td>
            </tr>

            <!-- Edit Modal -->
            <div class="modal fade" id="editModal{{ $term->id }}" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-lg">
                <div class="modal-content">
                  <form action="{{ route('erp.payment-terms.update', $term) }}" method="POST" class="term-form">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                      <h5 class="modal-title">Edit Payment Term</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <div class="mb-3">
                        <label class="form-label">Term Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $term->name }}" required>
                      </div>
                      <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="is_active" id="active{{ $term->id }}" {{ $term->is_active ? 'checked' : '' }}>
                        <label class="form-check-label" for="active{{ $term->id }}">Active</label>
                      </div>
                      
                      <hr>
                      <h6 class="fw-bold">Billing Schedule (Must total 100%)</h6>
                      
                      <div class="schedule-container">
                         <!-- dynamic rows go here -->
                      </div>
                      <button type="button" class="btn btn-sm btn-outline-secondary add-row-btn mt-2"><i class="bx bx-plus"></i> Add Termin</button>
                      
                      <div class="mt-3 text-end fw-bold">
                        Total: <span class="total-percentage text-danger">0</span>%
                      </div>
                      <input type="hidden" name="term_schedule" class="term-schedule-input" value="{{ json_encode($term->term_schedule ?: [['name'=>'100% Payment', 'percentage'=>100]]) }}">
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="submit" class="btn btn-primary btn-save">Save Changes</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="{{ route('erp.payment-terms.store') }}" method="POST" class="term-form">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Create Payment Term</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Term Name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. DP 30%, Termin 2 50%, Pelunasan 20%" required>
          </div>
          
          <hr>
          <h6 class="fw-bold">Billing Schedule (Must total 100%)</h6>
          
          <div class="schedule-container">
             <!-- dynamic rows go here -->
          </div>
          <button type="button" class="btn btn-sm btn-outline-secondary add-row-btn mt-2"><i class="bx bx-plus"></i> Add Termin</button>
          
          <div class="mt-3 text-end fw-bold">
            Total: <span class="total-percentage text-danger">0</span>%
          </div>
          <input type="hidden" name="term_schedule" class="term-schedule-input" value="[{&quot;name&quot;:&quot;100% Payment&quot;,&quot;percentage&quot;:100}]">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary btn-save">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    function renderRows($form) {
        let jsonStr = $form.find('.term-schedule-input').val();
        let data = [];
        try { data = JSON.parse(jsonStr); } catch(e) { data = []; }
        
        let html = '';
        data.forEach((row, i) => {
            html += `
            <div class="row align-items-center mb-2 schedule-row" data-idx="${i}">
                <div class="col-7">
                    <input type="text" class="form-control row-name" placeholder="Termin Name (e.g. DP)" value="${row.name}">
                </div>
                <div class="col-4">
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" max="100" class="form-control row-pct" placeholder="Percentage" value="${row.percentage}">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-1 text-end">
                    <button type="button" class="btn btn-icon btn-sm btn-outline-danger remove-row-btn"><i class="bx bx-trash"></i></button>
                </div>
            </div>`;
        });
        $form.find('.schedule-container').html(html);
        recalc($form);
    }

    function recalc($form) {
        let total = 0;
        let data = [];
        $form.find('.schedule-row').each(function() {
            let name = $(this).find('.row-name').val();
            let pct = parseFloat($(this).find('.row-pct').val()) || 0;
            total += pct;
            data.push({name: name, percentage: pct});
        });
        
        let $totalEl = $form.find('.total-percentage');
        $totalEl.text(total);
        if(total === 100) {
            $totalEl.removeClass('text-danger').addClass('text-success');
            $form.find('.btn-save').prop('disabled', false);
        } else {
            $totalEl.removeClass('text-success').addClass('text-danger');
            $form.find('.btn-save').prop('disabled', true);
        }
        
        $form.find('.term-schedule-input').val(JSON.stringify(data));
    }

    $('.term-form').each(function() {
        renderRows($(this));
    });

    $(document).on('click', '.add-row-btn', function() {
        let $form = $(this).closest('form');
        let data = JSON.parse($form.find('.term-schedule-input').val() || '[]');
        data.push({name: '', percentage: 0});
        $form.find('.term-schedule-input').val(JSON.stringify(data));
        renderRows($form);
    });

    $(document).on('click', '.remove-row-btn', function() {
        let $form = $(this).closest('form');
        $(this).closest('.schedule-row').remove();
        recalc($form);
    });

    $(document).on('input', '.row-name, .row-pct', function() {
        let $form = $(this).closest('form');
        recalc($form);
    });
});
</script>
@endpush