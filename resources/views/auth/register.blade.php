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

    /* --- KOLOM KIRI: GAMBAR INDUSTRI (Sama dengan Login) --- */
    .left-side {
        background: url('https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?q=80&w=2070&auto=format&fit=crop') no-repeat center center;
        background-size: cover;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 3rem;
    }

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
        color: #cbd5e1;
        font-weight: 300;
        max-width: 80%;
    }

    /* --- KOLOM KANAN: FORM REGISTER --- */
    .right-side {
        background-color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    .register-box {
        width: 100%;
        max-width: 500px; /* Lebih lebar sedikit dari login */
    }

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
    
    .input-group .form-control { border-left: none; }

    .btn-primary {
        background-color: #0f172a;
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

    @media (max-width: 991.98px) {
        .left-side { display: none; }
    }
</style>

<div class="container-fluid p-0">
    <div class="row g-0 row-full-height">
        
        {{-- BAGIAN KIRI: BRANDING --}}
        <div class="col-lg-6 left-side">
            <div class="brand-content">
                <div class="mb-4">
                    <span class="badge bg-blue-500 text-white px-3 py-2 rounded-pill fw-bold" style="background: #3b82f6;">TPS SYSTEM v2.0</span>
                </div>
                <h1 class="brand-title">Join Our Team</h1>
                <p class="brand-desc">
                    Daftarkan akun baru untuk mengakses sistem perencanaan produksi dan monitoring pabrik secara real-time.
                </p>
            </div>
            <div class="brand-content">
                <small class="text-muted" style="font-size: 0.7rem;">
                    &copy; {{ date('Y') }} PT. Toyota Production System. All Rights Reserved.
                </small>
            </div>
        </div>

        {{-- BAGIAN KANAN: FORM REGISTER --}}
        <div class="col-lg-6 right-side">
            <div class="register-box">
                
                <div class="text-center mb-5">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 60px; height: 60px;">
                        <i class="fas fa-user-plus fa-lg"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Buat Akun Baru</h3>
                    <p class="text-muted small">Lengkapi data diri Anda untuk melanjutkan.</p>
                </div>

                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    {{-- Nama Lengkap --}}
                    <div class="mb-4">
                        <label for="name" class="form-label">Nama Lengkap</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="far fa-user"></i></span>
                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" 
                                   name="name" value="{{ old('name') }}" required autocomplete="name" autofocus
                                   placeholder="John Doe">
                        </div>
                        @error('name')
                            <span class="text-danger small mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div class="mb-4">
                        <label for="email" class="form-label">Email Perusahaan</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="far fa-envelope"></i></span>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" 
                                   name="email" value="{{ old('email') }}" required autocomplete="email"
                                   placeholder="user@tps-manufacturing.com">
                        </div>
                        @error('email')
                            <span class="text-danger small mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Password Group --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" 
                                       name="password" required autocomplete="new-password" placeholder="••••••••">
                            </div>
                            @error('password')
                                <span class="text-danger small mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label for="password-confirm" class="form-label">Konfirmasi Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
                                <input id="password-confirm" type="password" class="form-control" 
                                       name="password_confirmation" required autocomplete="new-password" placeholder="••••••••">
                            </div>
                        </div>
                    </div>

                    {{-- Button Register --}}
                    <div class="d-grid mb-4">
                        <button type="submit" class="btn btn-primary shadow-lg">
                            DAFTAR SEKARANG <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>

                    {{-- Link ke Login --}}
                    <div class="text-center">
                        <p class="text-muted small mb-0">Sudah punya akun?</p>
                        <a href="{{ route('login') }}" class="fw-bold text-decoration-none" style="color: #3b82f6;">
                            Masuk di sini
                        </a>
                    </div>

                </form>

            </div>
        </div>

    </div>
</div>
@endsection