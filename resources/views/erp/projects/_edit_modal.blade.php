{{-- Edit one project (tenant) = one PT: its name, status and the identity printed as the letterhead. Needs $p. --}}
<div class="modal fade" id="modalEditProject{{ $p->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="{{ route('erp.projects.update', $p->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Perusahaan: {{ $p->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nama Tampilan</label>
                            <input type="text" name="name" class="form-control" value="{{ $p->name }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1" {{ $p->is_active ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ !$p->is_active ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>

                        <div class="col-12"><hr class="my-1"><div class="fw-semibold">Identitas PT <small class="text-muted fw-normal">— jadi kop surat PO, GR dan dokumen lain di perusahaan ini</small></div></div>

                        <div class="col-md-8">
                            <label class="form-label">Nama Legal PT</label>
                            <input type="text" name="legal_name" class="form-control" value="{{ $p->legal_name }}" placeholder="Misal: PT Mandiri Daya Utama Nusantara">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">NPWP</label>
                            <input type="text" name="tax_number" class="form-control" value="{{ $p->tax_number }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" class="form-control" rows="2">{{ $p->address }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telepon</label>
                            <input type="text" name="phone" class="form-control" value="{{ $p->phone }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fax</label>
                            <input type="text" name="fax" class="form-control" value="{{ $p->fax }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ $p->email }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Website</label>
                            <input type="text" name="website" class="form-control" value="{{ $p->website }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Logo</label>
                            <div class="d-flex align-items-center gap-3">
                                @if($p->logoUrl())
                                    <img src="{{ $p->logoUrl() }}" alt="Logo" style="max-height:48px;max-width:140px;object-fit:contain">
                                @endif
                                <input type="file" name="logo" class="form-control" accept="image/png,image/jpeg,image/webp">
                            </div>
                            <small class="text-muted">PNG/JPG/WEBP, maks 2 MB. Kosongkan bila tidak diganti.</small>
                            @if($p->logo_path)
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="removeLogo{{ $p->id }}">
                                    <label class="form-check-label" for="removeLogo{{ $p->id }}">Hapus logo</label>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
