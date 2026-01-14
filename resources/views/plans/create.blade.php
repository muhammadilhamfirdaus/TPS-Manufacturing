@extends('layouts.app_simple')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        
        <div class="mb-3">
            <a href="{{ route('plans.index') }}" class="text-decoration-none text-muted small">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke List Plan
            </a>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white pt-4 pb-0 border-bottom-0 text-center">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-calendar-plus fa-2x"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">Input Plan Produksi</h4>
                <p class="text-muted small">Sistem akan mendeteksi Line secara otomatis berdasarkan Routing Part</p>
            </div>

            <div class="card-body p-4">

                @if ($errors->any())
                    <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger mb-4 rounded-3">
                        <ul class="mb-0 ps-3 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('plans.store') }}" method="POST">
                    @csrf
                    
                    {{-- 1. INPUT TANGGAL (Full Width) --}}
                    <div class="mb-4">
                        <label class="form-label small text-uppercase fw-bold text-secondary">
                            <i class="far fa-calendar-alt me-1"></i> Tanggal Produksi
                        </label>
                        <input type="date" name="plan_date" class="form-control bg-light border-0 py-2 fw-bold text-dark" 
                               value="{{ date('Y-m-d') }}" required>
                    </div>

                    {{-- 2. INPUT PRODUK (Line Dihapus, Produk jadi Utama) --}}
                    <div class="mb-4">
                        <label class="form-label small text-uppercase fw-bold text-secondary">
                            <i class="fas fa-box me-1"></i> Pilih Part / Produk
                        </label>
                        <select name="product_id" class="form-select bg-light border-0 py-2 select2" required>
                            <option value="">-- Cari Part Number / Nama --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">
                                    {{ $product->part_number }} - {{ $product->part_name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small mt-1 text-primary">
                            <i class="fas fa-info-circle me-1"></i> Line Produksi akan otomatis dipilih berdasarkan Routing Master Data.
                        </div>
                    </div>

                    {{-- 3. TARGET QTY --}}
                    <div class="mb-4">
                        <label class="form-label small text-uppercase fw-bold text-secondary">
                            <i class="fas fa-bullseye me-1"></i> Target Qty (Pcs)
                        </label>
                        <div class="input-group">
                            <input type="number" name="qty_plan" class="form-control form-control-lg border-primary border-opacity-25 fw-bold text-primary" 
                                   placeholder="0" required min="1">
                            <span class="input-group-text bg-primary bg-opacity-10 text-primary border-primary border-opacity-25 fw-bold">PCS</span>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-5">
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm py-3 rounded-3 fw-bold">
                            <i class="fas fa-save me-2"></i> Simpan & Kalkulasi
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
@endsection