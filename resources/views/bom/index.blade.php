@extends('layouts.app_simple')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Bill of Materials (BOM)</h4>
                <p class="text-muted small mb-0">Kelola komposisi material untuk produk jadi.</p>
            </div>
            <a href="{{ route('bom.list') }}" class="btn btn-light border shadow-sm text-secondary btn-sm px-3">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>

        <div class="row">
            {{-- KOLOM KIRI: INFO PARENT PRODUCT --}}
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-body text-center p-4">
                        
                        {{-- FOTO / ICON --}}
                        <div class="mb-3">
                            @if($parent->photo)
                                <img src="{{ asset('storage/' . $parent->photo) }}" class="rounded-circle shadow-sm" style="width: 100px; height: 100px; object-fit: cover;">
                            @else
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px;">
                                    <i class="fas fa-cube fa-3x"></i>
                                </div>
                            @endif
                        </div>

                        <h5 class="fw-bold text-dark mb-1">{{ $parent->part_name }}</h5>
                        <span class="badge bg-dark px-3 py-2 rounded-pill mb-3">{{ $parent->part_number }}</span>

                        {{-- DETAIL INFORMASI --}}
                        <div class="text-start bg-light p-3 rounded-3 mt-3">
                            <h6 class="small fw-bold text-secondary text-uppercase border-bottom pb-2 mb-2">Detail Informasi</h6>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Code Part</span>
                                <span class="fw-bold text-primary">{{ $parent->code_part }}</span>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Customer</span>
                                <span class="fw-bold text-dark">{{ $parent->customer ?? '-' }}</span>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Kategori</span>
                                <span class="fw-bold text-dark">{{ $parent->category }}</span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: FORM & LIST KOMPONEN --}}
            <div class="col-md-8">
                
                {{-- CARD 1: FORM TAMBAH --}}
                <div class="card shadow-sm border-0 rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <h6 class="fw-bold text-success mb-0"><i class="fas fa-plus me-2"></i>Tambah Komponen</h6>
                    </div>
                    <div class="card-body pt-0">
                        <form action="{{ route('bom.store', $parent->id) }}" method="POST" class="row g-3 align-items-end">
                            @csrf
                            
                            <div class="col-md-7">
                                <label class="form-label small fw-bold text-muted">Pilih Part / Material</label>
                                <select name="child_product_id" class="form-select select2" required>
                                    <option value="">-- Cari Part Number / Name --</option>
                                    @foreach($allProducts as $p)
                                        <option value="{{ $p->id }}">
                                            {{ $p->code_part }} - {{ $p->part_name }} ({{ $p->part_number }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted">Qty Usage</label>
                                <div class="input-group">
                                    {{-- PERBAIKAN: step="any" agar bisa input desimal panjang --}}
                                    <input type="number" step="any" name="quantity" class="form-control" placeholder="0.00000001" required>
                                    <span class="input-group-text bg-light text-muted small">Pcs</span>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <button type="submit" class="btn btn-success w-100 fw-bold">Add</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- CARD 2: LIST KOMPONEN --}}
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <h6 class="fw-bold text-dark mb-0">Daftar Komponen (Child Parts)</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-secondary small text-uppercase">
                                    <tr>
                                        <th class="ps-4">Code Part</th>
                                        <th>Part Name</th>
                                        <th>Part Number</th>
                                        <th class="text-center">Qty Usage</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($parent->bomComponents as $child)
                                        <tr>
                                            <td class="ps-4 fw-bold text-primary">{{ $child->code_part }}</td>
                                            <td>{{ $child->part_name }}</td>
                                            <td class="text-muted small">{{ $child->part_number }}</td>
                                            
                                            <td class="text-center">
                                                <span class="badge bg-warning text-dark border border-warning bg-opacity-25 px-3">
                                                    {{-- PERBAIKAN: Menampilkan angka asli tanpa pembulatan --}}
                                                    {{ $child->pivot->quantity + 0 }} 
                                                </span>
                                            </td>
                                            
                                            <td class="text-center">
                                                <form action="{{ route('bom.destroy', ['id' => $parent->id, 'childId' => $child->id]) }}" method="POST" onsubmit="return confirm('Hapus komponen ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-link text-danger p-0" title="Hapus">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="fas fa-box-open fa-3x mb-3 opacity-25"></i><br>
                                                Belum ada komponen dalam BOM ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

{{-- Style Tambahan untuk Select2 --}}
<style>
    .select2-container .select2-selection--single {
        height: 38px; border: 1px solid #dee2e6;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px; padding-left: 12px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
</style>
@endsection