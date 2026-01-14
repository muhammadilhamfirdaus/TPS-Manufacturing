@extends('layouts.app_simple')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        
        {{-- 1. ALERT MESSAGES --}}
        @if(session('success'))
            <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success d-flex align-items-center mb-4">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger mb-4">
                <ul class="mb-0 ps-3 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- 2. HEADER PAGE --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Bill of Materials (BOM)</h4>
                <p class="text-muted small mb-0">Kelola komposisi material untuk produk jadi.</p>
            </div>
            
            {{-- PERBAIKAN DI SINI: Gunakan route('master.index') --}}
            <a href="{{ route('master.index') }}" class="btn btn-light border text-secondary shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Master Part
            </a>
        </div>

        <div class="row">
            {{-- 3. KOLOM KIRI: INFO PARENT --}}
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-body text-center pt-5 pb-4">
                        {{-- Icon Produk Besar --}}
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-4 shadow-sm" style="width: 100px; height: 100px;">
                            <i class="fas fa-cube fa-4x"></i>
                        </div>
                        
                        <h5 class="fw-bold text-dark mb-1">{{ $parent->part_name }}</h5>
                        <div class="badge bg-dark px-3 py-2 mb-4 rounded-pill">{{ $parent->part_number }}</div>
                        
                        <div class="card bg-light border-0 rounded-3 text-start p-3">
                            <small class="text-muted text-uppercase fw-bold d-block mb-2" style="font-size: 0.7rem;">Detail Informasi</small>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Customer</span>
                                <span class="fw-bold text-dark small">{{ $parent->customer_name ?? '-' }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Material</span>
                                <span class="fw-bold text-dark small">{{ $parent->material ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. KOLOM KANAN: DAFTAR KOMPONEN & FORM --}}
            <div class="col-md-8 mb-4">
                
                {{-- Form Tambah Child --}}
                <div class="card shadow-sm border-0 rounded-4 mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                <i class="fas fa-plus"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-0">Tambah Komponen</h6>
                        </div>
                        
                        <form action="{{ route('bom.store', $parent->id) }}" method="POST" class="row g-3 align-items-end">
                            @csrf
                            <div class="col-md-7">
                                <label class="form-label small text-muted fw-bold text-uppercase">Pilih Part / Material</label>
                                <select name="child_product_id" class="form-select border-0 bg-light fw-bold" required>
                                    <option value="">-- Cari Part Number / Name --</option>
                                    @foreach($allProducts as $p)
                                        <option value="{{ $p->id }}">
                                            {{ $p->part_number }} - {{ $p->part_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted fw-bold text-uppercase">Qty Usage</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" name="quantity" class="form-control border-0 bg-light fw-bold" placeholder="1.0" required>
                                    <span class="input-group-text border-0 bg-light text-muted small">Pcs</span>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-success w-100 fw-bold shadow-sm">
                                    Add
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Tabel Komponen --}}
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <h6 class="fw-bold text-dark mb-0 ps-2">Daftar Komponen (Child Parts)</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 text-secondary small text-uppercase fw-bold">Part Number</th>
                                    <th class="py-3 text-secondary small text-uppercase fw-bold">Part Name</th>
                                    <th class="text-center py-3 text-secondary small text-uppercase fw-bold">Qty Usage</th>
                                    <th class="text-end pe-4 py-3 text-secondary small text-uppercase fw-bold">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($parent->bomComponents as $child)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-primary">{{ $child->part_number }}</div>
                                    </td>
                                    <td>
                                        <div class="text-dark">{{ $child->part_name }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 px-3 py-2">
                                            {{ number_format($child->pivot->quantity, 4) }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form action="{{ route('bom.destroy', ['id' => $parent->id, 'childId' => $child->id]) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus komponen ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-light text-danger border-0 rounded-circle p-2" title="Hapus" style="width: 32px; height: 32px;">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <div class="text-muted opacity-50">
                                            <i class="fas fa-sitemap fa-3x mb-3"></i>
                                            <p class="mb-0 fw-bold">Belum ada komponen.</p>
                                            <small>Gunakan form di atas untuk menambahkan material.</small>
                                        </div>
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
@endsection