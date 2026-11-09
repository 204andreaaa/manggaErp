{{-- Empty state for an employee tab. Expects: $icon, $title, $text. --}}
<div class="card-body text-center py-5">
  <div class="avatar avatar-xl mx-auto mb-3"><span class="avatar-initial rounded-circle bg-label-secondary"><i class="bx {{ $icon }} fs-2"></i></span></div>
  <h6 class="mb-1">{{ $title }}</h6>
  <p class="text-muted small mb-0">{{ $text }}</p>
</div>
