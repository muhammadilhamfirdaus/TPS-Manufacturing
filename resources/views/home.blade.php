@extends('layouts.app_simple')

@section('title', 'Executive Dashboard')

@section('content')
<div class="container-fluid">
    
    {{-- 1. KPI CARDS (RINGKASAN) --}}
    <div class="row mb-4">
        {{-- Card Total Plan --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">Total Plan ({{ $monthName }})</div>
                    <div class="d-flex align-items-center mt-2">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                            <i class="fas fa-bullseye fa-lg"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold text-dark">{{ number_format($grandTotalPlan) }}</h3>
                            <small class="text-muted">Unit</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card Total Actual --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm border-start border-success border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">Total Actual ({{ $monthName }})</div>
                    <div class="d-flex align-items-center mt-2">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                            <i class="fas fa-box-open fa-lg"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold text-dark">{{ number_format($grandTotalActual) }}</h3>
                            <small class="text-muted">Unit</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card Achievement --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm border-start border-warning border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">Achievement Rate</div>
                    <div class="d-flex align-items-center mt-2">
                        <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                            <i class="fas fa-chart-line fa-lg"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold {{ $achievementPct >= 100 ? 'text-success' : 'text-dark' }}">
                                {{ $achievementPct }}%
                            </h3>
                            <small class="text-muted">Pencapaian</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. GRAFIK UTAMA --}}
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2 text-primary"></i>Pencapaian Produksi per Line</h6>
                </div>
                <div class="card-body">
                    {{-- Canvas untuk Chart.js --}}
                    <canvas id="productionChart" height="120"></canvas>
                </div>
            </div>
        </div>

        {{-- 3. QUICK ACTIONS (Opsional) --}}
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-bolt me-2 text-warning"></i>Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <a href="{{ route('production.input') }}" class="btn btn-outline-primary py-3 text-start">
                            <i class="fas fa-edit me-2 fa-lg"></i> Input Produksi (Matrix)
                        </a>
                        <a href="{{ route('plans.index') }}" class="btn btn-outline-secondary py-3 text-start">
                            <i class="fas fa-calendar-plus me-2 fa-lg"></i> Buat Plan Baru
                        </a>
                        <a href="{{ route('plans.loading_report') }}" class="btn btn-outline-info py-3 text-start">
                            <i class="fas fa-chart-pie me-2 fa-lg"></i> Cek Loading Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- SCRIPT CHART.JS --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('productionChart').getContext('2d');
        
        // Data dari Controller (Blade to JS)
        const labels = @json($labels);
        const dataPlan = @json($dataPlan);
        const dataActual = @json($dataActual);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Target Plan',
                        data: dataPlan,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)', // Biru Muda
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Actual Produksi',
                        data: dataActual,
                        backgroundColor: 'rgba(75, 192, 192, 0.8)', // Hijau Tebal
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f0f0f0'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
</script>
@endsection