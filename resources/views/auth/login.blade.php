@extends('layouts.app')

@section('content')
<style>
    /* Reset & Layout */
    body, html {
        height: 100%;
        margin: 0;
        overflow-x: hidden;
        font-family: 'Poppins', sans-serif;
    }

    .row-full-height {
        min-height: 100vh;
    }

    /* --- KOLOM KIRI: GAMBAR INDUSTRI --- */
    .left-side {
        background: url('https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?q=80&w=2070&auto=format&fit=crop') no-repeat center center;
        background-size: cover;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 3rem;
    }

    /* Overlay Gelap di atas gambar agar teks terbaca */
    .left-side::before {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.8) 100%);
        z-index: 1;
    }

    .brand-content {
        position: relative;
        z-index: 2;
        color: white;
    }

    .brand-title {
        font-weight: 700;
        font-size: 2.5rem;
        letter-spacing: -1px;
        margin-bottom: 0.5rem;
    }

    .brand-desc {
        font-size: 1.1rem;
        color: #cbd5e1; /* Slate 300 */
        font-weight: 300;
        max-width: 80%;
    }

    /* Feature List di Kiri */
    .feature-list {
        display: flex;
        gap: 20px;
        margin-top: 2rem;
    }
    .feature-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
        color: #94a3b8;
        background: rgba(255,255,255,0.1);
        padding: 8px 16px;
        border-radius: 50px;
        backdrop-filter: blur(5px);
    }

    /* --- KOLOM KANAN: FORM LOGIN --- */
    .right-side {
        background-color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    .login-box {
        width: 100%;
        max-width: 400px;
    }

    /* Styling Input */
    .form-label {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: 0.5px;
    }

    .form-control {
        padding: 12px 15px;
        border: 2px solid #e2e8f0;
        background-color: #f8fafc;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: all 0.2s;
    }

    .form-control:focus {
        border-color: #3b82f6;
        background-color: #fff;
        box-shadow: none;
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

    .btn-primary {
        background-color: #0f172a; /* Warna Industri Gelap */
        border: none;
        padding: 14px;
        font-weight: 600;
        border-radius: 8px;
        letter-spacing: 0.5px;
        transition: transform 0.2s;
    }

    .btn-primary:hover {
        background-color: #1e293b;
        transform: translateY(-2px);
    }

    /* Mobile Responsive */
    @media (max-width: 991.98px) {
        .left-side { display: none; }
    }
</style>

<div class="container-fluid p-0">
    <div class="row g-0 row-full-height">
        
        {{-- BAGIAN KIRI: GAMBAR PABRIK --}}
        <div class="col-lg-7 left-side">
            <div class="brand-content">
                <div class="mb-4">
                    <span class="badge bg-blue-500 text-white px-3 py-2 rounded-pill fw-bold" style="background: #3b82f6;">TPS SYSTEM v2.0</span>
                </div>
                <h1 class="brand-title">Production Planning<br>& Control System</h1>
                <p class="brand-desc">
                    Sistem terintegrasi untuk pengelolaan jadwal produksi, monitoring beban mesin (Loading), dan perencanaan tenaga kerja (MPP) secara efisien.
                </p>
                
                <div class="feature-list">
                    <div class="feature-item"><i class="fas fa-robot"></i> Smart Loading</div>
                    <div class="feature-item"><i class="fas fa-chart-line"></i> Real-time MPP</div>
                    <div class="feature-item"><i class="fas fa-shield-alt"></i> Secure Data</div>
                </div>
            </div>

            <div class="brand-content text-end">
                <small class="text-muted" style="font-size: 0.7rem;">&copy; {{ date('Y') }} PT. Toyota Production System. All Rights Reserved.</small>
            </div>
        </div>

        {{-- BAGIAN KANAN: FORM LOGIN --}}
        <div class="col-lg-5 right-side">
            <div class="login-box">
                
                <div class="text-center mb-5">
                    <div class="bg-dark text-white rounded-3 d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 50px; height: 50px;">
                        <i class="fas fa-industry fa-lg"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Login Administrator</h3>
                    <p class="text-muted small">Silakan masuk untuk mengakses dashboard produksi.</p>
                </div>

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    {{-- Email --}}
                    <div class="mb-4">
                        <label for="email" class="form-label">Email Perusahaan</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="far fa-envelope"></i></span>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" 
                                   name="email" value="{{ old('email') }}" required autofocus
                                   placeholder="user@tps-manufacturing.com">
                        </div>
                        @error('email')
                            <span class="text-danger small mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="mb-4">
                        <div class="d-flex justify-content-between">
                            <label for="password" class="form-label">Password</label>
                            @if (Route::has('password.request'))
                                <a class="text-decoration-none small fw-bold" href="{{ route('password.request') }}" style="color: #3b82f6;">Lupa Password?</a>
                            @endif
                        </div>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" 
                                   name="password" required placeholder="••••••••">
                        </div>
                        @error('password')
                            <span class="text-danger small mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Remember Me --}}
                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label small text-muted" for="remember">Ingat Saya</label>
                    </div>

                    {{-- Button --}}
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary shadow-lg">
                            MASUK SISTEM <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>

                </form>
                
                {{-- Footer Text --}}
                <div class="text-center mt-5">
                    <p class="text-muted" style="font-size: 0.75rem;">
                        <i class="fas fa-lock me-1"></i> Akses Terbatas & Terproteksi.
                    </p>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection