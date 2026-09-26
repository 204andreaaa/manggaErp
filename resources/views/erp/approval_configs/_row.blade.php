<tr>
  <td><span class="badge {{ $badgeClass }}">Step {{ $config->level }}</span></td>
  <td class="fw-semibold">{{ $config->name }}</td>
  <td>
    @if($config->role_id)
      Role: <span class="fw-bold">{{ $config->role->name ?? '-' }}</span>
    @else
      User: <span class="fw-bold">{{ $config->user->name ?? "-" }}</span>
    @endif
  </td>
  <td>
    @if(!is_null($config->is_project))
      <span class="badge bg-label-secondary me-1">
        {{ $config->is_project ? 'Project Only' : 'Non-Project Only' }}
      </span>
    @else
      <span class="badge bg-label-secondary me-1">All Types</span>
    @endif
    @if($config->min_amount || $config->max_amount)
      <span class="text-muted small d-block mt-1">
        Amount:
        {{ $config->min_amount ? number_format($config->min_amount, 0, ',', '.') : '0' }}
        -
        {{ $config->max_amount ? number_format($config->max_amount, 0, ',', '.') : '∞' }}
      </span>
    @endif
  </td>
  <td class="text-end">
    <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick='openConfigModal("edit", @json($config))'><i class="bx bx-edit-alt"></i></button>
    <form action="{{ route('erp.approval-configs.destroy', $config) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus konfigurasi ini?');">
      @csrf
      @method('DELETE')
      <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bx bx-trash"></i></button>
    </form>
  </td>
</tr>
