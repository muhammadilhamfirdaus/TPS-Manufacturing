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
    background: #f1f5f9; /* Ganti warna background agar kontras dengan box */
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
        rgba(2,6,23,0.85),
        rgba(15,23,42,0.75)
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
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(5px);
    border: 1px solid rgba(255,255,255,0.2);
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
   RIGHT SIDE (AREA LOGIN)
===================== */
.right-side {
    background: #f1f5f9; /* Background luar abu-abu terang */
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem;
}

.login-box {
    width: 100%;
    max-width: 450px; /* Lebarkan sedikit */
    background: #ffffff;
    border-radius: 24px; /* Sudut lebih bulat */
    padding: 3.5rem; /* Padding lebih lega */
    
    /* SHADOW YANG KUAT AGAR "POP UP" */
    box-shadow: 
        0 20px 25px -5px rgba(0, 0, 0, 0.1), 
        0 8px 10px -6px rgba(0, 0, 0, 0.1);
        
    /* BORDER HALUS UNTUK DEFINISI */
    border: 1px solid rgba(226, 232, 240, 0.8);
    
    animation: fadeUp .6s ease;
    position: relative;
}

/* Dekorasi kecil di belakang box (opsional, biar estetik) */
.login-box::before {
    content: '';
    position: absolute;
    top: -10px;
    left: -10px;
    right: -10px;
    bottom: -10px;
    background: linear-gradient(135deg, #e2e8f0, #ffffff);
    z-index: -1;
    border-radius: 30px;
    opacity: 0.5;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.login-icon {
    width: 64px; /* Perbesar icon */
    height: 64px;
    border-radius: 18px;
    background: linear-gradient(135deg, #0f172a, #334155);
    color: #ffffff;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: auto;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.25);
}

.form-label {
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #475569;
    margin-bottom: 0.5rem;
    display: block;
}

.form-control {
    border-radius: 12px;
    border: 2px solid #e2e8f0;
    padding: 14px 16px;
    background: #f8fafc;
    font-size: 0.95rem;
    transition: all 0.2s;
}

.form-control:focus {
    border-color: #3b82f6;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.input-group-text {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-right: none;
    color: #94a3b8;
    border-top-left-radius: 12px;
    border-bottom-left-radius: 12px;
}

.input-group .form-control {
    border-left: none;
}

.input-group:focus-within .input-group-text {
    border-color: #3b82f6;
    background: #fff;
    color: #3b82f6;
}

/* =====================
   BUTTON
===================== */
.btn-primary {
    background: linear-gradient(135deg, #0f172a, #1e293b);
    border: none;
    padding: 16px;
    font-weight: 700;
    font-size: 0.9rem;
    border-radius: 12px;
    letter-spacing: 0.5px;
    box-shadow: 0 10px 20px rgba(15, 23, 42, 0.15);
    transition: transform 0.2s, box-shadow 0.2s;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 25px rgba(15, 23, 42, 0.25);
    background: linear-gradient(135deg, #1e293b, #334155);
}

/* =====================
   RESPONSIVE
===================== */
@media (max-width: 991px) {
    .left-side { display: none; }
    .right-side { padding: 1.5rem; background: #fff; }
    .login-box { 
        box-shadow: none; 
        padding: 1rem; 
        border: none;
    }
    .login-box::before { display: none; }
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
        <div class="login-icon mb-4">
            <i class="fas fa-industry"></i>
        </div>
        <h3 class="fw-bold text-dark mb-1">Welcome Back!</h3>
        <p class="text-muted small">Please login to access your dashboard.</p>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-4">
            <label class="form-label">Corporate Email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="far fa-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="name@company.com" required>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
        </div>

        {{-- REMEMBER & FORGOT --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="remember" id="remember">
                <label class="form-check-label small text-muted cursor-pointer" for="remember">Remember me</label>
            </div>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                   class="small fw-bold text-decoration-none"
                   style="color:#3b82f6">
                    Forgot Password?
                </a>
            @endif
        </div>

        <div class="d-grid mb-4">
            <button class="btn btn-primary text-white">
                LOGIN TO DASHBOARD
            </button>
        </div>

        <div class="text-center small text-muted">
            Don't have an account? 
            <a href="{{ route('register') }}" class="fw-bold text-dark text-decoration-none">Sign Up</a>
        </div>
    </form>

</div>
</div>

</div>
</div>
@endsection