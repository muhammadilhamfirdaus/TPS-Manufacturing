@extends('layouts.app_simple')

@section('title', 'Executive Dashboard')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
    body { font-family: 'Poppins', sans-serif; background-color: #f4f7fe; }
    .card-modern { border: none; border-radius: 20px; background: #ffffff; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); transition: all 0.3s ease; }
    .card-modern:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); }
    .card-header-modern { background: transparent; border-bottom: 1px solid #f1f5f9; padding: 25px 30px; }
    .icon-box { width: 60px; height: 60px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; transition: transform 0.3s; }
    .card-modern:hover .icon-box { transform: scale(1.1); }
    
    /* Gradients */
    .icon-bg-primary { background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%); color: #fff; box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3); }
    .icon-bg-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3); }
    .icon-bg-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #fff; box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3); }
    .icon-bg-purple { background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: #fff; box-shadow: 0 4px 10px rgba(139, 92, 246, 0.3); }
    .icon-bg-danger { background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); color: #fff; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3); }

    .form-select-modern { border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 20px; font-size: 0.9rem; color: #475569; background-color: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.03); cursor: pointer; }
    .form-select-modern:focus { border-color: #4361ee; box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1); outline: none; }
    .text-label { color: #94a3b8; font-weight: 600; font-size: 0.75rem; letter-spacing: 0.8px; }
    .text-value { color: #1e293b; font-weight: 700; font-size: 2rem; letter-spacing: -1px; }
</style>

<div class="container-fluid px-4 py-4">
    
    {{-- HEADER & FILTER --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1" style="letter-spacing: -0.5px;">Production Overview</h3>
            <p class="text-muted mb-0" style="font-size: 0.95rem;">
                Monitoring performa produksi periode <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill">{{ $monthName }}</span>
            </p>
        </div>
        <form action="{{ route('home') }}" method="GET" class="d-flex flex-wrap gap-2">
            <select name="line_id" class="form-select-modern fw-bold" onchange="this.form.submit()" style="min-width: 220px;">
                <option value="">🏠 Semua Line (Global)</option>
                @foreach($lines as $l)
                    <option value="{{ $l->id }}" {{ $selectedLineId == $l->id ? 'selected' : '' }}>🏭 {{ $l->name }}</option>
                @endforeach
            </select>
            <select name="month" class="form-select-modern fw-bold" onchange="this.form.submit()" style="width: 150px;">
                @for($m=1; $m<=12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>📅 {{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                @endfor
            </select>
            <select name="year" class="form-select-modern fw-bold" onchange="this.form.submit()" style="width: 120px;">
                <option value="2025" {{ $year == 2025 ? 'selected' : '' }}>2025</option>
                <option value="2026" {{ $year == 2026 ? 'selected' : '' }}>2026</option>
            </select>
        </form>
    </div>

    {{-- KPI CARDS (UPDATED: 4 COLUMN) --}}
    <div class="row mb-4 g-4">
        {{-- Card 1: Plan --}}
        <div class="col-xl-3 col-md-6">
            <div class="card-modern h-100 p-4 position-relative overflow-hidden">
                <div class="d-flex justify-content-between align-items-start position-relative z-2">
                    <div>
                        <div class="text-label text-uppercase mb-2">Total Plan</div>
                        <h3 class="text-value mb-0">{{ number_format($grandTotalPlan) }} <span class="fs-6 text-muted fw-normal">Unit</span></h3>
                    </div>
                    <div class="icon-box icon-bg-success"><i class="fas fa-bullseye"></i></div>
                </div>
                <div class="mt-4 pt-2 border-top border-light"><span class="text-success fw-bold"><i class="fas fa-arrow-up me-1"></i>Target</span> <span class="text-muted small ms-1">produksi bulan ini</span></div>
            </div>
        </div>
        
        {{-- Card 2: Actual --}}
        <div class="col-xl-3 col-md-6">
            <div class="card-modern h-100 p-4 position-relative overflow-hidden">
                <div class="d-flex justify-content-between align-items-start position-relative z-2">
                    <div>
                        <div class="text-label text-uppercase mb-2">Total Actual (OK)</div>
                        <h3 class="text-value mb-0">{{ number_format($grandTotalActual) }} <span class="fs-6 text-muted fw-normal">Unit</span></h3>
                    </div>
                    <div class="icon-box icon-bg-primary"><i class="fas fa-cubes"></i></div>
                </div>
                <div class="mt-4 pt-2 border-top border-light"><span class="text-primary fw-bold"><i class="fas fa-check-circle me-1"></i>Good</span> <span class="text-muted small ms-1">hasil produksi bagus</span></div>
            </div>
        </div>

        {{-- Card 3: NG (BARU) --}}
        <div class="col-xl-3 col-md-6">
            <div class="card-modern h-100 p-4 position-relative overflow-hidden">
                <div class="d-flex justify-content-between align-items-start position-relative z-2">
                    <div>
                        <div class="text-label text-uppercase mb-2">Total Defect (NG)</div>
                        <h3 class="text-value mb-0 text-danger">{{ number_format($grandTotalNg) }} <span class="fs-6 text-muted fw-normal">Unit</span></h3>
                    </div>
                    <div class="icon-box icon-bg-danger"><i class="fas fa-times-circle"></i></div>
                </div>
                <div class="mt-4 pt-2 border-top border-light">
                    <span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle me-1"></i>{{ $ngRatePct }}%</span> <span class="text-muted small ms-1">NG Rate (Reject)</span>
                </div>
            </div>
        </div>

        {{-- Card 4: Achievement --}}
        <div class="col-xl-3 col-md-6">
            <div class="card-modern h-100 p-4 position-relative overflow-hidden">
                <div class="d-flex justify-content-between align-items-start position-relative z-2">
                    <div>
                        <div class="text-label text-uppercase mb-2">Achievement</div>
                        <h3 class="text-value mb-0 {{ $achievementPct >= 100 ? 'text-success' : 'text-warning' }}">{{ $achievementPct }}%</h3>
                    </div>
                    <div class="icon-box icon-bg-warning"><i class="fas fa-chart-pie"></i></div>
                </div>
                <div class="mt-4">
                    <div class="progress" style="height: 10px; border-radius: 10px; background-color: #f1f5f9;">
                        <div class="progress-bar {{ $achievementPct >= 100 ? 'bg-success' : 'bg-warning' }}" role="progressbar" style="width: {{ min($achievementPct, 100) }}%; border-radius: 10px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CHART 1: LINE PERFORMANCE (+ DATA NG) --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card-modern">
                <div class="card-header-modern d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">📊 Performance & Quality per Line</h5>
                        <p class="text-muted small mb-0">Komparasi Plan (Hijau) vs Actual (Biru) vs NG (Merah)</p>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div style="height: 350px;">
                        <canvas id="productionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CHART 2: LOADING ANALYSIS --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card-modern">
                <div class="card-header-modern d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">👥 Manpower Loading Analysis</h5>
                        <p class="text-muted small mb-0">Analisa beban kerja harian vs kapasitas Manpower</p>
                    </div>
                    <span class="badge bg-danger bg-opacity-10 text-danger px-4 py-2 border border-danger border-opacity-25 rounded-pill shadow-sm">
                        <i class="fas fa-user-clock me-2"></i> Satuan: ORANG (MP)
                    </span>
                </div>
                <div class="card-body p-4">
                    <div style="height: 450px;">
                        <canvas id="mppChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        {{-- CHART 3: PRODUCTIVITY PERCENTAGE --}}
        <div class="col-md-6">
            <div class="card-modern h-100">
                <div class="card-header-modern d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">📈 Productivity Rate (%)</h5>
                        <p class="text-muted small mb-0">Efisiensi Manpower Harian</p>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div style="height: 300px;">
                        <canvas id="productivityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- CHART 4: DAILY NG TREND (BARU) --}}
        <div class="col-md-6">
            <div class="card-modern h-100">
                <div class="card-header-modern d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">📉 Daily Defect (NG) Trend</h5>
                        <p class="text-muted small mb-0">Monitoring jumlah NG per hari</p>
                    </div>
                    <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-1 rounded-pill border border-danger border-opacity-25">
                        <i class="fas fa-times me-1"></i> NG Unit
                    </span>
                </div>
                <div class="card-body p-4">
                    <div style="height: 300px;">
                        <canvas id="ngChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- QUICK ACTION --}}
    <div class="row mb-5">
        <div class="col-12">
            <div class="card-modern p-4 bg-white border border-light">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="icon-box bg-primary bg-opacity-10 text-primary" style="width: 50px; height: 50px; font-size: 1.2rem;"><i class="fas fa-bolt"></i></div>
                        <div><h6 class="fw-bold text-dark mb-1">Quick Actions</h6><p class="text-muted mb-0 small">Pintasan menu operasional harian</p></div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('production.input') }}" class="btn btn-primary fw-bold px-4 py-2 rounded-pill shadow-sm"><i class="fas fa-edit me-2"></i> Input Matrix</a>
                        <a href="{{ route('kanban.daily_report') }}" class="btn btn-warning text-white fw-bold px-4 py-2 rounded-pill shadow-sm"><i class="fas fa-clipboard-check me-2"></i> Laporan Harian</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- SCRIPT CHART.JS --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        Chart.register(ChartDataLabels);

        // --- FUNGSI GRADIENT REUSABLE ---
        function createGradient(ctx, colorStart, colorEnd) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, colorStart);
            gradient.addColorStop(1, colorEnd);
            return gradient;
        }

        const colorPlanStart = 'rgb(16, 185, 129)';   
        const colorPlanEnd   = 'rgba(16, 185, 129, 0.6)'; 
        const colorActStart  = 'rgba(67, 97, 238, 1)';    
        const colorActEnd    = 'rgba(67, 97, 238, 0.6)';
        const colorNgStart   = 'rgba(239, 68, 68, 1)';    // Merah untuk NG
        const colorNgEnd     = 'rgba(239, 68, 68, 0.6)';
        
        const colorDelay     = '#f59e0b'; 
        const colorLimit     = '#ef4444'; 
        const colorPurpleStart = 'rgba(139, 92, 246, 0.8)'; 
        const colorPurpleEnd   = 'rgba(139, 92, 246, 0.1)';

        // ================================================================
        // CHART 1: PRODUKSI PER LINE + NG
        // ================================================================
        const ctxProd = document.getElementById('productionChart').getContext('2d');
        const gradPlan1 = createGradient(ctxProd, colorPlanStart, colorPlanEnd);
        const gradAct1  = createGradient(ctxProd, colorActStart, colorActEnd);
        const gradNg1   = createGradient(ctxProd, colorNgStart, colorNgEnd);

        // Ambil Data dari Controller
        const dataPct = @json($dataPctLine); 
        const dataNg  = @json($dataNgLine); // Data NG per Line

        new Chart(ctxProd, {
            type: 'bar',
            data: {
                labels: @json($labelsLine),
                datasets: [
                    {
                        label: 'Target Plan', data: @json($dataPlanLine), backgroundColor: gradPlan1, borderRadius: 6,
                        datalabels: { anchor: 'end', align: 'top', color: '#318e39', font: { weight: 'bold', family: 'Poppins' }, formatter: (val) => val > 0 ? new Intl.NumberFormat('id-ID').format(val) : '' }
                    },
                    {
                        label: 'Actual (OK)', data: @json($dataActualLine), backgroundColor: gradAct1, borderRadius: 6,
                        datalabels: { 
                            anchor: 'end', align: 'top', color: '#334155', font: { weight: 'bold', family: 'Poppins' }, 
                            formatter: (val, ctx) => {
                                if(val > 0) {
                                    let pct = dataPct[ctx.dataIndex];
                                    return pct + '%'; // Tampilkan persen saja biar rapi
                                }
                                return '';
                            }
                        }
                    },
                    // DATASET BARU: NG
                    {
                        label: 'NG (Defect)', data: dataNg, backgroundColor: gradNg1, borderRadius: 6,
                        datalabels: { anchor: 'end', align: 'top', color: '#ef4444', font: { weight: 'bold', size:11, family: 'Poppins' }, formatter: (val) => val > 0 ? val : '' }
                    }
                ]
            },
            options: { 
                responsive: true, maintainAspectRatio: false, 
                plugins: { legend: { position: 'top', labels: { usePointStyle: true, font: { family: 'Poppins' } } } }, 
                scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' }, border: { display: false } }, x: { grid: { display: false }, border: { display: false } } } 
            }
        });

        // ================================================================
        // CHART 2: LOADING ANALYSIS (TETAP)
        // ================================================================
        const ctxMpp = document.getElementById('mppChart').getContext('2d');
        const gradPlan2 = createGradient(ctxMpp, colorPlanStart, colorPlanEnd);
        const gradAct2  = createGradient(ctxMpp, colorActStart, colorActEnd);

        new Chart(ctxMpp, {
            type: 'bar',
            data: {
                labels: @json($chartDates),
                datasets: [
                    {
                        label: 'Kapasitas MPP', data: @json($valMppLimit), type: 'line', borderColor: colorLimit, borderWidth: 2, borderDash: [6, 4], pointRadius: 0, order: 0,
                        datalabels: { align: 'right', anchor: 'end', color: colorLimit, font: { weight: 'bold', size: 11 }, formatter: (val, ctx) => ctx.dataIndex === ctx.dataset.data.length - 1 ? val : '' }
                    },
                    {
                        label: 'Actual (Orang)', data: @json($valActLoad), backgroundColor: gradAct2, stack: 'act_group', borderRadius: 4, order: 1,
                        datalabels: { color: 'white', font: { weight: 'bold', size: 10 }, formatter: (v) => v > 0.1 ? v : '' }
                    },
                    {
                        label: 'Plan (Orang)', data: @json($valPlanLoad), backgroundColor: gradPlan2, stack: 'target_group', borderRadius: 4, order: 2,
                        datalabels: { color: 'white', font: { weight: 'bold', size: 10 }, formatter: (v) => v > 0.1 ? v : '' }
                    },
                    {
                        label: 'Delay (Orang)', data: @json($valDelayLoad), backgroundColor: colorDelay, stack: 'target_group', borderRadius: 4, order: 3,
                        datalabels: { color: 'white', font: { weight: 'bold', size: 10 }, formatter: (v) => v > 0.1 ? v : '' }
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8, padding: 20, font: { family: 'Poppins' } } }, tooltip: { backgroundColor: 'rgba(30, 41, 59, 0.95)', padding: 12, cornerRadius: 8 }, datalabels: { display: true } },
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { font: { family: 'Poppins', size: 11 }, color: '#64748b' }, border: { display: false } },
                    y: { stacked: true, grid: { color: '#f1f5f9', borderDash: [5, 5] }, ticks: { font: { family: 'Poppins' }, color: '#94a3b8' }, border: { display: false }, title: { display: true, text: 'Man Power (Orang)', color: '#cbd5e1', font: { size: 11 } } }
                }
            }
        });

        // ================================================================
        // CHART 3: PRODUCTIVITY PERCENTAGE (TETAP)
        // ================================================================
        const ctxPct = document.getElementById('productivityChart').getContext('2d');
        const gradPurple = createGradient(ctxPct, colorPurpleStart, colorPurpleEnd);

        new Chart(ctxPct, {
            type: 'line',
            data: {
                labels: @json($chartDates),
                datasets: [{
                    label: 'Productivity Rate (%)', data: @json($valActPct),
                    borderColor: '#8b5cf6', backgroundColor: gradPurple, borderWidth: 3,
                    pointBackgroundColor: '#fff', pointBorderColor: '#8b5cf6', pointBorderWidth: 2, pointRadius: 4, fill: true, tension: 0.4,
                    datalabels: { align: 'top', color: '#8b5cf6', font: { weight: 'bold', size: 10, family: 'Poppins' }, formatter: (v) => v > 0 ? v + '%' : '' }
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => ' ' + ctx.parsed.y + '%' } } },
                scales: { x: { grid: { display: false }, border: { display: false } }, y: { beginAtZero: true, grid: { color: '#f1f5f9', borderDash: [5, 5] }, ticks: { callback: (v) => v + '%' }, border: { display: false } } }
            }
        });

        // ================================================================
        // CHART 4: DAILY NG TREND (BARU)
        // ================================================================
        const ctxNg = document.getElementById('ngChart').getContext('2d');
        const gradNg2 = createGradient(ctxNg, colorNgStart, colorNgEnd);

        new Chart(ctxNg, {
            type: 'bar', // Bisa diganti 'line' kalau mau garis
            data: {
                labels: @json($chartDates),
                datasets: [{
                    label: 'NG (Unit)',
                    data: @json($valNgDaily), // Data dari Controller
                    backgroundColor: gradNg2,
                    borderRadius: 4,
                    datalabels: { 
                        anchor: 'end', align: 'top', color: '#ef4444', 
                        font: { weight: 'bold', size: 10, family: 'Poppins' }, 
                        formatter: (val) => val > 0 ? val : '' 
                    }
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    x: { grid: { display: false }, border: { display: false } }, 
                    y: { beginAtZero: true, grid: { color: '#f1f5f9', borderDash: [5, 5] }, border: { display: false } } 
                }
            }
        });

    });
</script>
@endsection