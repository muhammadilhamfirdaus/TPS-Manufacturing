@extends('layouts.app_simple')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold text-dark mb-0">Input Planning Manual</h5>
                <small class="text-muted">Buat target produksi bulanan baru</small>
            </div>
            <div class="card-body p-4">
                
                <form action="{{ route('plans.store') }}" method="POST">
                    @csrf

                    {{-- INPUT BULAN --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">Bulan Produksi</label>
                        <input type="month" name="plan_month" class="form-control" value="{{ date('Y-m') }}" required>
                    </div>

                    {{-- INPUT PILIH PART (REVISI DISINI) --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">Pilih Part / Material</label>
                        <select name="product_id" class="form-select select2" required>
                            <option value="">-- Cari Part Number / Code / Name --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">
                                    {{-- FORMAT TAMPILAN: [CODE] PART NO - PART NAME --}}
                                    [{{ $product->code_part }}] {{ $product->part_number }} - {{ $product->part_name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small text-muted">
                            Format: [Code Part] Part Number - Part Name
                        </div>
                    </div>

                    {{-- INPUT QTY --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">Target Qty (1 Bulan)</label>
                        <div class="input-group">
                            <input type="number" name="qty_plan" class="form-control fw-bold text-primary" placeholder="Contoh: 1000" min="1" required>
                            <span class="input-group-text bg-light text-muted">PCS</span>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary fw-bold py-2">
                            <i class="fas fa-save me-2"></i> SIMPAN TARGET BULANAN
                        </button>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="{{ route('plans.index') }}" class="text-decoration-none text-muted small">
                            <i class="fas fa-arrow-left me-1"></i> Batal & Kembali
                        </a>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

{{-- SCRIPT SELECT2 (Agar bisa search di dropdown) --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'classic',
            width: '100%',
            placeholder: "-- Cari Part Number / Code / Name --",
            allowClear: true
        });
    });
</script>

<style>
    /* Custom Style untuk Select2 agar mirip Bootstrap */
    .select2-container--classic .select2-selection--single {
        height: 38px;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 5px;
    }
    .select2-container--classic .select2-selection--single .select2-selection__arrow {
        height: 36px;
        border-left: none;
        background: transparent;
    }
    .select2-container--classic .select2-selection--single .select2-selection__rendered {
        color: #212529;
        line-height: 24px;
    }
</style>
@endsection