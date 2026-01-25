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
    background: #f1f5f9;
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

.brand-footer {
    position: relative;
    z-index: 2;
    font-size: .7rem;
    color: #cbd5f5;
}

/* =====================
   RIGHT SIDE (RESET AREA)
===================== */
.right-side {
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem;
}

.reset-box {
    width: 100%;
    max-width: 450px;
    background: #ffffff;
    border-radius: 24px;
    padding: 3.5rem;
    
    /* SHADOW & BORDER */
    box-shadow: 
        0 20px 25px -5px rgba(0, 0, 0, 0.1), 
        0 8px 10px -6px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(226, 232, 240, 0.8);
    
    animation: fadeUp .6s ease;
    position: relative;
}

.reset-box::before {
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

.reset-icon {
    width: 64px;
    height: 64px;
    border-radius: 18px;
    background: linear-gradient(135deg, #0f172a, #334155);
    color: #fff;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: auto;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.25);
}

/* =====================
   FORM
===================== */
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
    .reset-box { box-shadow: none; padding: 1rem; border: none; }
    .reset-box::before { display: none; }
}
</style>

<div class="container-fluid p-0">
<div class="row g-0 row-full-height">

{{-- LEFT --}}
<div class="col-lg-7 left-side">
    <div class="brand-content">
        <span class="badge-system">TPS SYSTEM v2.0</span>
        <h1 class="brand-title">Secure Reset</h1>
        <p class="brand-desc">
            Amankan akun Anda dengan kata sandi baru yang kuat. 
            Pastikan kombinasi huruf, angka, dan simbol untuk keamanan maksimal.
        </p>
    </div>
    <div class="brand-footer text-end">
        © {{ date('Y') }} PT. CNK NUGERAH KARYA
    </div>
</div>

{{-- RIGHT --}}
<div class="col-lg-5 right-side">
<div class="reset-box">

    <div class="text-center mb-5">
        <div class="reset-icon mb-4">
            <i class="fas fa-key"></i>
        </div>
        <h3 class="fw-bold text-dark mb-1">Reset Password</h3>
        <p class="text-muted small">Masukkan kata sandi baru Anda</p>
    </div>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-4">
            <label class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="far fa-envelope"></i></span>
                <input type="email" name="email" class="form-control" value="{{ $email ?? old('email') }}" required readonly>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">New Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" name="password" class="form-control" required autofocus placeholder="New Password">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Confirm Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
                <input type="password" name="password_confirmation" class="form-control" required placeholder="Confirm Password">
            </div>
        </div>

        <div class="d-grid mb-4">
            <button class="btn btn-primary text-white">
                RESET PASSWORD NOW
            </button>
        </div>
        
    </form>

</div>
</div>

</div>
</div>
@endsection