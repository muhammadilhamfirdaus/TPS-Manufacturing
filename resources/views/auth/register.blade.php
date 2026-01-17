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
   LEFT SIDE (BRANDING)
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
    font-size: 2.6rem;
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
   RIGHT SIDE (REGISTER)
===================== */
.right-side {
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem;
}

.register-box {
    width: 100%;
    max-width: 460px;
    background: #ffffff;
    border-radius: 20px;
    padding: 3rem;
    box-shadow:
        0 20px 40px rgba(0,0,0,.08),
        0 1px 0 rgba(0,0,0,.05);
    animation: fadeUp .6s ease;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.register-icon {
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

/* =====================
   FORM
===================== */
.form-label {
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    letter-spacing: .5px;
}

.form-control {
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    padding: 13px 15px;
    background: #f8fafc;
    font-size: .95rem;
}

.form-control:focus {
    background: #fff;
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
    letter-spacing: .5px;
    border-radius: 12px;
    transition: all .3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 35px rgba(15,23,42,.4);
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
<div class="col-lg-6 left-side">
    <div class="brand-content">
        <span class="badge-system">TPS SYSTEM v2.0</span>

        <h1 class="brand-title">
            Join Our Team
        </h1>

        <p class="brand-desc">
            Daftarkan akun Anda untuk mengakses sistem perencanaan produksi,
            monitoring mesin, dan kontrol pabrik secara real-time.
        </p>
    </div>

    <div class="brand-footer text-end">
        © {{ date('Y') }} PT. Toyota Production System
    </div>
</div>

{{-- RIGHT --}}
<div class="col-lg-6 right-side">
<div class="register-box">

    <div class="text-center mb-5">
        <div class="register-icon mb-3">
            <i class="fas fa-user-plus"></i>
        </div>
        <h3 class="fw-bold">Buat Akun Baru</h3>
        <p class="text-muted small">Lengkapi data di bawah ini</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="mb-4">
            <label class="form-label">Nama Lengkap</label>
            <div class="input-group">
                <span class="input-group-text"><i class="far fa-user"></i></span>
                <input type="text" name="name" class="form-control" required placeholder="John Doe">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Email Perusahaan</label>
            <div class="input-group">
                <span class="input-group-text"><i class="far fa-envelope"></i></span>
                <input type="email" name="email" class="form-control" required placeholder="user@company.com">
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" required>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Konfirmasi Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
            </div>
        </div>

        <div class="d-grid mb-4">
            <button class="btn btn-primary">
                DAFTAR SEKARANG <i class="fas fa-arrow-right ms-2"></i>
            </button>
        </div>

        <div class="text-center small">
            <span class="text-muted">Sudah punya akun?</span>
            <a href="{{ route('login') }}" class="fw-bold ms-1">Masuk</a>
        </div>
    </form>

</div>
</div>

</div>
</div>
@endsection
