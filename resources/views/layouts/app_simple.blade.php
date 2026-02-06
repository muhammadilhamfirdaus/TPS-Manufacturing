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



    <link rel="icon" href="{{ asset('images/image.png') }}" type="image/png">
    <link rel="shortcut icon" href="{{ asset('images/image.png') }}" type="image/png">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <style>
        :root {
            /* Warna Sidebar */
            --sidebar-bg: #020617;
            /* Dark Blue/Black */
            --sidebar-muted: #94a3b8;
            /* Text Abu Terang */
            --sidebar-text: #e2e8f0;
            /* Text Putih Tulang */
            --sidebar-active: #6366f1;
            /* Indigo */
            --sidebar-hover: rgba(255, 255, 255, 0.1);

            /* Layout */
            --body-bg: #f8fafc;
            --sidebar-width: 260px;
            --sidebar-mini: 80px;
            --header-height: 64px;
        }

        body {
            background: var(--body-bg);
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
            margin: 0;
        }

        /* ================= SIDEBAR ================= */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1050;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--sidebar-muted) var(--sidebar-bg);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* Mode Mini (Desktop) */
        .sidebar.collapsed {
            width: var(--sidebar-mini);
        }

        /* Mode Hidden (Full Screen) */
        .sidebar.hidden-sidebar {
            transform: translateX(-100%);
        }

        /* Elemen dalam Sidebar */
        .sidebar .brand-text,
        .sidebar .nav-text,
        .sidebar .sidebar-heading {
            transition: opacity 0.2s, width 0.2s;
            white-space: nowrap;
            overflow: hidden;
        }

        .sidebar.collapsed .brand-text,
        .sidebar.collapsed .nav-text,
        .sidebar.collapsed .sidebar-heading {
            opacity: 0;
            width: 0;
            display: none;
            /* Hilangkan agar layout mini rapi */
        }

        /* Styling Heading Menu */
        .sidebar-heading {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--sidebar-muted);
            /* Warna lebih terang */
            font-weight: 600;
            padding: 1.5rem 1.2rem 0.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 0.5rem;
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

        .nav-link:hover {
            background: var(--sidebar-hover);
            color: #fff;
        }

        .nav-link.active {
            background: linear-gradient(90deg,
                    rgba(99, 102, 241, 0.25),
                    rgba(99, 102, 241, 0.05));
            color: #fff;
            box-shadow: inset 3px 0 0 var(--sidebar-active);
        }

        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 0.75rem 0;
        }

        .sidebar.collapsed .nav-link i {
            font-size: 1.1rem;
        }

        /* ================= MAIN CONTENT WRAPPER ================= */
        .main {
            margin-left: var(--sidebar-width);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main.expanded {
            margin-left: var(--sidebar-mini);
        }

        .main.full {
            margin-left: 0;
        }

        /* ================= TOP HEADER ================= */
        .top-header {
            height: var(--header-height);
            padding: 0 1.5rem;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1040;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .toggle-btn {
            border: none;
            background: transparent;
            width: 35px;
            height: 35px;
            border-radius: 8px;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
        }

        .toggle-btn:hover {
            background: #f1f5f9;
            color: var(--sidebar-active);
        }

        /* ================= RESPONSIVE (MOBILE) ================= */
        /* Overlay Gelap saat menu mobile terbuka */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1045;
            display: none;
            backdrop-filter: blur(2px);
        }

        .sidebar-overlay.show {
            display: block;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                /* Default sembunyi di mobile */
                width: var(--sidebar-width) !important;
                /* Mobile selalu full width sidebar */
            }

            .sidebar.mobile-open {
                transform: translateX(0);
                /* Muncul saat di-toggle */
                box-shadow: 5px 0 15px rgba(0, 0, 0, 0.1);
            }

            .main,
            .main.expanded,
            .main.full {
                margin-left: 0 !important;
                /* Content selalu full width di mobile */
            }

            /* Tombol Toggle Desktop disembunyikan di Mobile */
            #btn-desktop-toggle,
            #btn-desktop-eye {
                display: none;
            }

            /* Tampilkan Toggle Mobile */
            #btn-mobile-toggle {
                display: flex;
            }
        }

        @media (min-width: 769px) {
            #btn-mobile-toggle {
                display: none;
            }
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
</head>

<body>

    {{-- OVERLAY MOBILE --}}
    <div id="sidebarOverlay" class="sidebar-overlay" onclick="closeMobileSidebar()"></div>

    {{-- SIDEBAR --}}
    <aside id="sidebar" class="sidebar">
        <div class="d-flex flex-column h-100">

            {{-- BRAND LOGO --}}
            <div class="d-flex align-items-center px-4 py-5"
                style="height: var(--header-height); border-bottom: 1px solid rgba(255,255,255,0.05);">
                <div class="d-flex align-items-center gap-3 text-white text-decoration-none">
                    <div class="rounded-3 d-flex align-items-center justify-content-center text-white flex-shrink-0 shadow-lg"
                        style="width:36px;height:36px;background: linear-gradient(135deg, #6366f1, #4f46e5);">
                        <i class="fas fa-cube"></i>
                    </div>
                    <div class="brand-text lh-1">
                        <div class="fw-semibold">TPS MFG</div>
                        <small class="text-white" style="font-size:0.65rem;">Manufacturing System</small>
                    </div>
                </div>
            </div>

            {{-- MENU ITEMS --}}
            <ul class="nav flex-column py-3 mb-auto">

                {{-- Dashboard --}}
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
                        <i class="fas fa-th-large fa-fw"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>

                {{-- Input Produksi --}}
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('production.input') ? 'active' : '' }}"
                        href="{{ route('production.input') }}">
                        <i class="fas fa-dolly-flatbed fa-fw"></i>
                        <span class="nav-text">Rekap Kanban</span>
                    </a>
                </li>

                {{-- [BARU] Laporan Harian (Kanban) --}}
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('kanban.daily_report') ? 'active' : '' }}"
                        href="{{ route('kanban.daily_report') }}">
                        <i class="fas fa-clipboard-check fa-fw"></i>
                        <span class="nav-text">Laporan Harian</span>
                    </a>
                </li>

                {{-- Role Admin Only --}}
                @if(auth()->check() && auth()->user()->role === 'admin')

                    <div class="sidebar-heading mt-2">PLANNING & CONTROL</div>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('plans.index') ? 'active' : '' }}"
                            href="{{ route('plans.index') }}">
                            <i class="fas fa-calendar-alt fa-fw"></i>
                            <span class="nav-text">Planning Schedule</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('plans.sum_loading') ? 'active' : '' }}"
                            href="{{ route('plans.sum_loading') }}">
                            <i class="fas fa-tachometer-alt fa-fw"></i>
                            <span class="nav-text">Machine Capacity</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('plans.loading_report') ? 'active' : '' }}"
                            href="{{ route('plans.loading_report') }}">
                            <i class="fas fa-chart-pie fa-fw"></i>
                            <span class="nav-text">Loading Detail</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('mpp.index') ? 'active' : '' }}"
                            href="{{ route('mpp.index') }}">
                            <i class="fas fa-users-cog fa-fw"></i>
                            <span class="nav-text">Manpower (MPP)</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('kanban.index') }}"
                            class="nav-link {{ request()->routeIs('kanban.index') ? 'active' : '' }}">
                            <i class="fas fa-calculator fa-fw"></i>
                            <span class="nav-text">Kalkulasi Kanban</span>
                        </a>
                    </li>

                    <div class="sidebar-heading mt-2">MASTER DATA</div>

                    <<li class="nav-item">
                        {{-- GANTI JADI lines.* DAN route('lines.index') --}}
                        <a class="nav-link {{ request()->routeIs('lines.*') ? 'active' : '' }}"
                            href="{{ route('lines.index') }}">
                            <i class="fas fa-network-wired fa-fw"></i>
                            <span class="nav-text">Line & Machine</span>
                        </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('master.*') ? 'active' : '' }}"
                                href="{{ route('master.index') }}">
                                <i class="fas fa-cubes fa-fw"></i>
                                <span class="nav-text">Part & Routing</span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('bom.*') ? 'active' : '' }}"
                                href="{{ route('bom.list') }}">
                                <i class="fas fa-sitemap fa-fw"></i>
                                <span class="nav-text">Bill of Materials</span>
                            </a>
                        </li>

                        <div class="sidebar-heading mt-2">SYSTEM</div>

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('logs.index') ? 'active' : '' }}"
                                href="{{ route('logs.index') }}">
                                <i class="fas fa-history fa-fw"></i>
                                <span class="nav-text">Activity Logs</span>
                            </a>
                        </li>

                @endif
            </ul>
            {{-- Footer sidebar dihapus agar tidak double --}}

            <div class="mt-auto p-3">
                <small class="text-muted d-block text-center" style="font-size: 0.6rem; ">v1.0.0 Stable</small>
            </div>

        </div>
    </aside>


    {{-- MAIN WRAPPER --}}
    <div id="main" class="main">

        {{-- TOP HEADER --}}
        <header class="top-header">

            {{-- BAGIAN KIRI: TOGGLES --}}
            <div class="d-flex align-items-center gap-2">

                {{-- Toggle Mobile (Hamburger) --}}
                <button id="btn-mobile-toggle" class="toggle-btn text-dark" onclick="openMobileSidebar()">
                    <i class="fas fa-bars fa-lg"></i>
                </button>

                {{-- Toggle Desktop (Hamburger) --}}
                <button id="btn-desktop-toggle" class="toggle-btn" onclick="toggleDesktopCollapse()"
                    title="Minimize Menu">
                    <i class="fas fa-bars"></i>
                </button>

                {{-- Toggle Eye (Hide/Show) --}}
                <button id="btn-desktop-eye" class="toggle-btn" onclick="toggleSidebarVisibility()"
                    title="Hide Sidebar">
                    <i id="eye-icon" class="fas fa-eye"></i>
                </button>

                <div class="vr mx-2 h-50 d-none d-md-block opacity-25"></div>

                {{-- Judul Halaman --}}
                <h5 class="mb-0 fw-bold text-dark d-none d-sm-block">@yield('title', 'Dashboard')</h5>
            </div>

            {{-- BAGIAN KANAN: USER PROFILE --}}
            <div class="d-flex align-items-center gap-3">

                {{-- Jam Digital Simple --}}
                <div class="text-end d-none d-md-block lh-sm">
                    <div class="fw-bold text-dark" style="font-size: 0.85rem;">{{ date('H:i') }}</div>
                    <small class="text-muted" style="font-size: 0.7rem;">{{ date('d M Y') }}</small>
                </div>

                {{-- User Dropdown --}}
                <div class="dropdown">
                    <a href="#"
                        class="d-flex align-items-center text-decoration-none dropdown-toggle p-1 ps-2 pe-3 rounded-pill bg-light border"
                        id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">

                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white shadow-sm me-2"
                            style="width:32px;height:32px;background:#6366f1;">
                            <i class="fas fa-user"></i>
                        </div>

                        <div class="text-start d-none d-sm-block lh-1">
                            {{-- Pengecekan Auth agar tidak error jika belum login --}}
                            <div class="fw-bold text-dark" style="font-size: 0.8rem;">
                                {{ Auth::check() ? Auth::user()->name : 'Guest' }}
                            </div>
                            <small class="text-muted" style="font-size: 0.65rem;">
                                {{ Auth::check() ? ucfirst(Auth::user()->role) : 'Visitor' }}
                            </small>
                        </div>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 p-2 rounded-3"
                        aria-labelledby="dropdownUser1">
                        <li>
                            <h6 class="dropdown-header text-uppercase small fw-bold text-muted">Account</h6>
                        </li>
                        <li><a class="dropdown-item rounded-2" href="#"><i
                                    class="fas fa-user-circle me-2 text-primary"></i> Profile</a></li>
                        <li><a class="dropdown-item rounded-2" href="#"><i class="fas fa-cog me-2 text-secondary"></i>
                                Settings</a></li>
                        <li>
                            <hr class="dropdown-divider my-2">
                        </li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button class="dropdown-item rounded-2 text-danger fw-bold">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        {{-- CONTENT BODY --}}
        <div class="p-4 flex-grow-1">
            @yield('content')
        </div>

        {{-- FOOTER UTAMA (Satu-satunya Footer) --}}
        <footer class="text-center py-3 bg-white border-top text-muted" style="font-size: 0.75rem;">
            <div class="container-fluid">
                <strong>&copy; {{ date('Y') }} TPS Manufacturing System</strong>. All rights reserved.
            </div>
        </footer>
    </div>

    {{-- SCRIPTS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const sidebar = document.getElementById('sidebar');
        const main = document.getElementById('main');
        const overlay = document.getElementById('sidebarOverlay');
        const eyeIcon = document.getElementById('eye-icon');

        // 1. Desktop: Toggle Mini Sidebar (Collapse)
        function toggleDesktopCollapse() {
            sidebar.classList.toggle('collapsed');
            main.classList.toggle('expanded');
        }

        // 2. Desktop: Hide Sidebar Total (Icon Mata)
        function toggleSidebarVisibility() {
            sidebar.classList.toggle('hidden-sidebar');
            main.classList.toggle('full');

            // Toggle Icon Mata
            if (sidebar.classList.contains('hidden-sidebar')) {
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        // 3. Mobile: Buka Sidebar
        function openMobileSidebar() {
            sidebar.classList.add('mobile-open');
            overlay.classList.add('show');
        }

        // 4. Mobile: Tutup Sidebar (klik overlay atau menu)
        function closeMobileSidebar() {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('show');
        }
    </script>

</body>

</html>