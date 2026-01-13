@extends('layouts.app_simple')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        
        {{-- Tombol Kembali (Floating Top Left) --}}
        <div class="mb-3">
            <a href="{{ route('plans.index') }}" class="text-decoration-none text-muted small">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke List Plan
            </a>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            {{-- Header Modern --}}
            <div class="card-header bg-white pt-4 pb-0 border-bottom-0 text-center">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-calendar-plus fa-2x"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">Input Plan Produksi</h4>
                <p class="text-muted small">Buat jadwal produksi baru dan kalkulasi beban kerja otomatis</p>
            </div>

            <div class="card-body p-4">

                {{-- Alert Error --}}
                @if ($errors->any())
                    <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger mb-4 rounded-3">
                        <div class="d-flex">
                            <i class="fas fa-exclamation-circle mt-1 me-2"></i>
                            <div>
                                <strong class="d-block mb-1">Terjadi Kesalahan:</strong>
                                <ul class="mb-0 ps-3 small">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                <form action="{{ route('plans.store') }}" method="POST">
                    @csrf
                    
                    {{-- SECTION 1: Waktu & Lokasi --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small text-uppercase fw-bold text-secondary">
                                <i class="far fa-calendar-alt me-1"></i> Tanggal Produksi
                            </label>
                            <input type="date" name="plan_date" class="form-control bg-light border-0 py-2 fw-bold text-dark" 
                                   value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-uppercase fw-bold text-secondary">
                                <i class="fas fa-network-wired me-1"></i> Line Produksi
                            </label>
                            <select name="production_line_id" class="form-select bg-light border-0 py-2" required>
                                <option value="">-- Pilih Line --</option>
                                @foreach($lines as $line)
                                    <option value="{{ $line->id }}">
                                        {{ $line->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- SECTION 2: Produk & Target --}}
                    <div class="mb-4">
                        <label class="form-label small text-uppercase fw-bold text-secondary">
                            <i class="fas fa-box me-1"></i> Pilih Part / Produk
                        </label>
                        <select name="product_id" class="form-select bg-light border-0 py-2" required>
                            <option value="">-- Cari Part Number / Nama --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">
                                    {{ $product->part_number }} - {{ $product->part_name }} (CT: {{ $product->cycle_time }}s)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small text-uppercase fw-bold text-secondary">
                            <i class="fas fa-bullseye me-1"></i> Target Qty (Pcs)
                        </label>
                        <div class="input-group">
                            <input type="number" name="qty_plan" class="form-control form-control-lg border-primary border-opacity-25 fw-bold text-primary" 
                                   placeholder="0" required>
                            <span class="input-group-text bg-primary bg-opacity-10 text-primary border-primary border-opacity-25 fw-bold">PCS</span>
                        </div>
                        <div class="form-text small mt-2">
                            <i class="fas fa-info-circle text-info me-1"></i> 
                            Sistem akan otomatis menghitung <b>Loading Mesin (%)</b> dan <b>Manpower</b>.
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-grid gap-2 mt-5">
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm py-3 rounded-3 fw-bold">
                            <i class="fas fa-save me-2"></i> Simpan & Kalkulasi
                        </button>
                    </div>

                </form>
            </div>
        </div>
        
        {{-- Footer Note --}}
        <div class="text-center mt-4 text-muted small">
            Pastikan data Cycle Time di Master Part sudah benar sebelum input plan.
        </div>

    </div>
</div>
@endsection