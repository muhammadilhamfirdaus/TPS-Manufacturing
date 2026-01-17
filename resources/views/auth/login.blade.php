@extends('layouts.app')

@section('content')
<style>
/* =====================
   GLOBAL
===================== */
html, body {
    height: 100%;
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: #f8fafc;
}

.row-full-height {
    min-height: 100vh;
}

/* =====================
   LEFT SIDE
===================== */
.left-side {
    position: relative;
    background: url('https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?q=80&w=2070&auto=format&fit=crop')
        center / cover no-repeat;
    padding: 4rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.left-side::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(
        135deg,
        rgba(2,6,23,0.65),
        rgba(15,23,42,0.55)
    );
    z-index: 1;
}

.left-side::after {
    content: "";
    position: absolute;
    inset: 0;
    background: radial-gradient(
        circle at top left,
        rgba(255,255,255,0.08),
        transparent 60%
    );
    z-index: 1;
}

.brand-content {
    position: relative;
    z-index: 2;
    color: #fff;
}

.badge-system {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    padding: 10px 18px;
    border-radius: 999px;
    font-weight: 600;
    letter-spacing: .5px;
    box-shadow: 0 10px 30px rgba(59,130,246,.35);
}

.brand-title {
    font-size: 2.8rem;
    font-weight: 800;
    line-height: 1.15;
    margin-top: 1.5rem;
}

.brand-desc {
    color: #e2e8f0;
    max-width: 85%;
    font-size: 1.05rem;
}

.feature-list {
    display: flex;
    gap: 16px;
    margin-top: 2.5rem;
    flex-wrap: wrap;
}

.feature-item {
    background: rgba(255,255,255,0.18);
    backdrop-filter: blur(3px);
    border: 1px solid rgba(255,255,255,0.18);
    padding: 10px 18px;
    border-radius: 999px;
    font-size: .85rem;
    color: #f8fafc;
    display: flex;
    align-items: center;
    gap: 8px;
}

.brand-footer {
    position: relative;
    z-index: 2;
    font-size: .7rem;
    color: #cbd5f5;
}

/* =====================
   RIGHT SIDE
===================== */
.right-side {
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem;
}

.login-box {
    width: 100%;
    max-width: 420px;
    background: #fff;
    border-radius: 20px;
    padding: 3rem;
    box-shadow: 0 20px 40px rgba(0,0,0,.08);
    animation: fadeUp .6s ease;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.login-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: linear-gradient(135deg, #0f172a, #1e293b);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: auto;
    box-shadow: 0 15px 35px rgba(15,23,42,.4);
}

.form-label {
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
}

.form-control {
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    padding: 13px 15px;
    background: #f8fafc;
}

.form-control:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37,99,235,.12);
}

.input-group-text {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-right: none;
    color: #94a3b8;
}

.input-group .form-control {
    border-left: none;
}

/* =====================
   BUTTON
===================== */
.btn-primary {
    background: linear-gradient(135deg, #0f172a, #1e293b);
    border: none;
    padding: 14px;
    font-weight: 700;
    border-radius: 12px;
}

.btn-primary:hover {
    transform: translateY(-2px);
}

/* =====================
   RESPONSIVE
===================== */
@media (max-width: 991px) {
    .left-side { display: none; }
}
</style>

<div class="container-fluid p-0">
<div class="row g-0 row-full-height">

{{-- LEFT --}}
<div class="col-lg-7 left-side">
    <div class="brand-content">
        <span class="badge-system">TPS SYSTEM v2.0</span>

        <h1 class="brand-title">
            Production Planning<br>& Control System
        </h1>

        <p class="brand-desc">
            Sistem terintegrasi untuk perencanaan produksi, monitoring beban mesin,
            dan pengendalian MPP secara real-time.
        </p>

        <div class="feature-list">
            <div class="feature-item"><i class="fas fa-cogs"></i> Smart Planning</div>
            <div class="feature-item"><i class="fas fa-chart-line"></i> Real-time Control</div>
            <div class="feature-item"><i class="fas fa-shield-alt"></i> Secure Data</div>
        </div>
    </div>

    <div class="brand-footer text-end">
        © {{ date('Y') }} PT. CNK NUGERAH KARYA
    </div>
</div>

{{-- RIGHT --}}
<div class="col-lg-5 right-side">
<div class="login-box">

    <div class="text-center mb-5">
        <div class="login-icon mb-3">
            <i class="fas fa-industry"></i>
        </div>
        <h3 class="fw-bold">Administrator Login</h3>
        <p class="text-muted small">Authorized access only</p>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-4">
            <label class="form-label">Corporate Email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="far fa-envelope"></i></span>
                <input type="email" name="email" class="form-control" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" name="password" class="form-control" required>
            </div>
        </div>

        {{-- REMEMBER & FORGOT --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="remember">
                <label class="form-check-label small text-muted">Ingat Saya</label>
            </div>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                   class="small fw-bold text-decoration-none"
                   style="color:#2563eb">
                    Lupa Password?
                </a>
            @endif
        </div>

        <div class="d-grid mb-4">
            <button class="btn btn-primary">
                LOGIN SYSTEM <i class="fas fa-arrow-right ms-2"></i>
            </button>
        </div>

        <div class="text-center small">
            <span class="text-muted">Belum punya akun?</span>
            <a href="{{ route('register') }}" class="fw-bold ms-1">Daftar</a>
        </div>
    </form>

</div>
</div>

</div>
</div>
@endsection
