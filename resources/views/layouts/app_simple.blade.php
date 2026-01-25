<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'TPS Manufacturing System')</title>

    {{-- Google Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    {{-- Bootstrap --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- FontAwesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --sidebar-bg: #020617;
            --sidebar-muted: #64748b;
            --sidebar-text: #cbd5f5;
            --sidebar-active: #6366f1;
            --sidebar-hover: rgba(99,102,241,0.08);
            --body-bg: #f1f5f9;
        }

        body {
            background: var(--body-bg);
            font-family: 'Poppins', sans-serif;
        }

        /* SIDEBAR */
        .sidebar {
            min-height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid rgba(255,255,255,0.05);
            padding-top: 1.25rem;
            z-index: 100;
        }

        .brand-section {
            padding: 0 1.25rem 1.25rem;
        }

        .sidebar-heading {
            font-size: 0.65rem;
            letter-spacing: 1.5px;
            color: var(--sidebar-muted);
            font-weight: 600;
            padding: 1rem 1.25rem 0.4rem;
        }

        .nav-link {
            color: var(--sidebar-text);
            padding: 0.6rem 1.25rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 12px;
            border-radius: 10px;
            margin: 2px 0.75rem;
            transition: 0.25s;
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        .nav-link:hover {
            background: var(--sidebar-hover);
            color: #fff;
        }

        .nav-link.active {
            background: linear-gradient(
                90deg,
                rgba(99,102,241,0.25),
                rgba(99,102,241,0.05)
            );
            color: #fff;
            box-shadow: inset 3px 0 0 var(--sidebar-active);
        }

        .nav-link.active i {
            color: var(--sidebar-active);
        }

        /* MAIN HEADER (TOP BAR) */
        .top-header {
            background-color: transparent;
            padding-top: 1.5rem;
            padding-bottom: 1rem;
        }

        .page-title {
            font-weight: 600;
            font-size: 1.5rem;
            color: #1e293b;
        }

        /* Dropdown User Profile */
        .user-dropdown-toggle {
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 8px;
            transition: 0.2s;
        }
        .user-dropdown-toggle:hover {
            background-color: rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

{{-- MOBILE HEADER --}}
<header class="navbar navbar-dark bg-dark sticky-top d-md-none">
    <a class="navbar-brand px-3" href="#">TPS MFG</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
        <span class="navbar-toggler-icon"></span>
    </button>
</header>

<div class="container-fluid">
<div class="row">

{{-- SIDEBAR --}}
<nav id="sidebarMenu"
     class="col-md-3 col-lg-2 sidebar collapse d-md-block position-fixed top-0 bottom-0 start-0 overflow-auto">

    <div class="d-flex flex-column h-100">

        {{-- BRAND --}}
        <div class="brand-section text-white">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-3 d-flex align-items-center justify-content-center"
                     style="width:34px;height:34px;background:#6366f1;">
                    <i class="fas fa-industry"></i>
                </div>
                <div>
                    <div class="fw-semibold">TPS MFG</div>
                    <small class="text-white" style="font-size:0.65rem;">Manufacturing System</small>
                </div>
            </div>
        </div>

        {{-- MENU LIST --}}
        <ul class="nav flex-column mb-auto">

            {{-- 
                =========================================
                MENU UMUM (SEMUA ROLE BISA LIHAT)
                =========================================
            --}}

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}"
                   href="{{ route('home') }}">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>
            </li>

            {{-- Matrix Produksi (Input Harian Operator) --}}
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('production.input') ? 'active' : '' }}"
                   href="{{ route('production.input') }}">
                    <i class="fas fa-dolly-flatbed"></i> Input Produksi
                </a>
            </li>

            {{-- 
                =========================================
                MENU KHUSUS ADMIN (PLANNER/MANAGER)
                =========================================
            --}}
            @if(auth()->user()->role === 'admin')

                <div class="sidebar-heading">PLANNING & CONTROL</div>

                {{-- Input Plan (Hanya Planner) --}}
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('plans.index','plans.create') ? 'active' : '' }}"
                       href="{{ route('plans.index') }}">
                        <i class="fas fa-calendar-plus"></i> Planning Schedule
                    </a>
                </li>

                {{-- [BARU] Sum Loading Report (Kapasitas Mesin) --}}
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('plans.sum_loading') ? 'active' : '' }}"
                       href="{{ route('plans.sum_loading') }}">
                        <i class="fas fa-tachometer-alt"></i> Machine Capacity
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('plans.loading_report') ? 'active' : '' }}"
                       href="{{ route('plans.loading_report') }}">
                        <i class="fas fa-chart-pie"></i> Loading Detail (Part)
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('mpp.index') ? 'active' : '' }}" href="{{ route('mpp.index') }}">
                        <i class="fas fa-users-cog"></i> Manpower (MPP)
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('kanban.index') ? 'active' : '' }}"
                       href="{{ route('kanban.index') }}">
                        <i class="fas fa-columns"></i> Kalkulasi Kanban
                    </a>
                </li>

                {{-- DATA CENTER --}}
                <div class="sidebar-heading">MASTER DATA</div>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('master-line.index','master-line.create','master-line.edit') ? 'active' : '' }}"
                       href="{{ route('master-line.index') }}">
                        <i class="fas fa-network-wired"></i> Line & Machine
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('master.*') ? 'active' : '' }}" href="{{ route('master.index') }}">
                        <i class="fas fa-cubes"></i> Part & Routing
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('bom.*') ? 'active' : '' }}" href="{{ route('bom.list') }}">
                        <i class="fas fa-sitemap"></i> Bill of Materials
                    </a>
                </li>
                
                {{-- SYSTEM --}}
                <div class="sidebar-heading">SYSTEM</div>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('logs.index') ? 'active' : '' }}"
                       href="{{ route('logs.index') }}">
                        <i class="fas fa-history"></i> Activity Logs
                    </a>
                </li>

            @endif {{-- END IF ADMIN --}}
        </ul>

        {{-- FOOTER SIDEBAR --}}
        <div class="mt-auto p-3">
             <small class="text-muted d-block text-center" style="font-size: 0.6rem;">v1.0.0 Stable</small>
        </div>

    </div>
</nav>

{{-- MAIN CONTENT --}}
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    
    {{-- NEW HEADER: Title Kiri, User Kanan --}}
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center top-header border-bottom mb-4">
        
        {{-- Kiri: Judul Halaman --}}
        <div>
            <h1 class="page-title">@yield('title', 'Dashboard')</h1>
            <p class="text-muted small mb-0">
                {{ date('l, d F Y') }}
            </p>
        </div>

        {{-- Kanan: User Profile Dropdown --}}
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle user-dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                
                {{-- Nama User & Role Dinamis --}}
                <div class="text-end me-3 d-none d-sm-block">
                    <div class="fw-bold text-dark small">{{ Auth::user()->name ?? 'User' }}</div>
                    <div class="text-muted" style="font-size: 0.7rem;">
                        {{-- Menampilkan Role Secara Dinamis (Admin / Production) --}}
                        {{ ucfirst(Auth::user()->role ?? 'Guest') }}
                    </div>
                </div>

                {{-- Avatar --}}
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white shadow-sm"
                     style="width:40px;height:40px;background:#6366f1;">
                    <i class="fas fa-user"></i>
                </div>
            </a>
            
            {{-- Dropdown Menu --}}
            <ul class="dropdown-menu dropdown-menu-end text-small shadow border-0" aria-labelledby="dropdownUser1">
                <li><h6 class="dropdown-header">User Menu</h6></li>
                <li><a class="dropdown-item" href="#"><i class="fas fa-id-card me-2 text-secondary"></i> My Profile</a></li>
                <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2 text-secondary"></i> Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="dropdown-item text-danger fw-bold">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>

    @yield('content')

    <footer class="py-4 text-center text-muted small mt-5">
        &copy; {{ date('Y') }} TPS Manufacturing System
    </footer>
</main>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>