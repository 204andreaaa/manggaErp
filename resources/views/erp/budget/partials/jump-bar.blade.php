{{-- Jump links to the related lists, like Salesforce's related-list bar. Expects: $sections [id => [label, count]]. --}}
<div class="text-center small mb-3 rel-jump">
  @foreach($sections as $id => [$label, $count])
    <a href="#rel-{{ $id }}" class="sf-link">{{ $label }} [{{ $count }}]</a>@if(!$loop->last) <span class="text-muted mx-1">|</span> @endif
  @endforeach
</div>

@once
@push('styles')
<style>
  .rel-jump a { font-size: .8rem; }
  .sf-card[id] { scroll-margin-top: 90px; }
</style>
@endpush
@endonce
