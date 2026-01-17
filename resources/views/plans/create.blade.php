@extends('layouts.app_simple')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold text-dark m-0">Input Target Bulanan</h4>
                <a href="{{ route('plans.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
            </div>

            <div class="card shadow-sm border-0 rounded-3 p-4">
                <div class="alert alert-primary d-flex align-items-center mb-4">
                    <i class="fas fa-calendar-check fs-3 me-3"></i>
                    <div>
                        <strong>Satu Data per Bulan</strong><br>
                        Target yang Anda input di sini berlaku sebagai <strong>Total Target untuk 1 Bulan</strong>.
                    </div>
                </div>

                <form action="{{ route('plans.store') }}" method="POST">
                    @csrf

                    {{-- PERIODE --}}
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">PERIODE (BULAN & TAHUN)</label>
                        <input type="month" name="plan_month" class="form-control form-control-lg fw-bold" 
                               value="{{ date('Y-m') }}" required>
                    </div>

                    {{-- PRODUK --}}
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">PART NUMBER</label>
                        <select name="product_id" class="form-select form-select-lg" required>
                            <option value="">-- Pilih Part --</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->part_number }} - {{ $p->part_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- QTY --}}
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">TARGET QTY (1 BULAN)</label>
                        <div class="input-group input-group-lg">
                            <input type="number" name="qty_plan" class="form-control fw-bold text-primary" 
                                   placeholder="Contoh: 100" min="1" required>
                            <span class="input-group-text">PCS</span>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold">
                            SIMPAN TARGET BULANAN
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection