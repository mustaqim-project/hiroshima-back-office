<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ $title ?? \App\Models\Setting::getAppName() }} - {{ \App\Models\Setting::getAppTagline() }}</title>
    @if(\App\Models\Setting::getFaviconUrl())
        <link rel="icon" type="image/x-icon" href="{{ \App\Models\Setting::getFaviconUrl() }}">
    @else
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @endif
    <!-- PWA & Mobile Web Capabilities -->
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#dc2626">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Hiroshima">
    <meta name="application-name" content="Hiroshima Back Office">
    <meta name="format-detection" content="telephone=no">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icons/icon-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">
    <!-- Responsive Design & System Font Stack (Zero External Dependencies) -->
    <style>
        :root {
            --primary: #dc2626;
            --primary-hover: #b91c1c;
            --dark-surface: #0f172a;
            --dark-surface-elevated: #1e293b;
            --card-surface: #ffffff;
            --border: #e2e8f0;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --success: #16a34a;
            --warning: #d97706;
            --danger: #dc2626;
            --sidebar-width: 260px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        body { background-color: #f8fafc; color: var(--text-main); display: flex; min-height: 100vh; overflow-x: hidden; }
        
        /* Sidebar Navigation */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--dark-surface);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            padding: 20px 14px 40px;
            position: fixed;
            top: 0; bottom: 0; left: 0;
            z-index: 200;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: #334155 transparent;
            transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 4px;
        }
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }
        .brand {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 18px;
            border-bottom: 1px solid #1e293b;
            margin-bottom: 18px;
            flex-shrink: 0;
        }
        .brand-badge {
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            width: 38px; height: 38px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 18px; color: #fff;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.35);
        }
        .brand-text h1 { font-size: 15px; font-weight: 700; letter-spacing: -0.02em; color: #ffffff; }
        .brand-text span { font-size: 11px; color: #94a3b8; display: block; margin-top: 1px; }

        .sidebar-close-btn {
            display: none;
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 6px;
            border-radius: 6px;
            line-height: 1;
        }
        .sidebar-close-btn:hover {
            background: #1e293b;
            color: #ffffff;
        }

        .nav-menu { display: flex; flex-direction: column; gap: 4px; list-style: none; }
        .nav-section-label {
            padding: 12px 14px 4px;
            font-size: 10px;
            font-weight: 800;
            color: #64748b;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }
        .nav-link {
            display: flex; align-items: center; gap: 11px;
            padding: 10px 12px;
            color: #94a3b8; text-decoration: none;
            font-size: 13.5px; font-weight: 500;
            border-radius: 8px;
            transition: all 0.15s ease;
        }
        .nav-link:hover {
            background: #1e293b;
            color: #ffffff;
        }
        .nav-link.active {
            background: #1e293b;
            color: #ef4444;
            font-weight: 700;
            border-left: 3px solid var(--primary);
        }

        /* Backdrop overlay for mobile drawer */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 195;
            opacity: 0;
            transition: opacity 0.25s ease;
        }
        .sidebar-backdrop.active {
            display: block;
            opacity: 1;
        }

        /* Main Content Container */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: calc(100% - var(--sidebar-width));
            min-width: 0;
            transition: margin-left 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .topbar {
            height: 64px;
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 28px;
            position: sticky; top: 0; z-index: 40;
            gap: 12px;
        }
        .sidebar-toggle-btn {
            display: none;
            background: #f1f5f9;
            border: 1px solid var(--border);
            border-radius: 8px;
            width: 40px; height: 40px;
            align-items: center; justify-content: center;
            cursor: pointer;
            color: var(--text-main);
            flex-shrink: 0;
        }
        .sidebar-toggle-btn:hover {
            background: #e2e8f0;
        }
        .topbar-brand-mobile {
            display: none;
        }

        .content-area {
            padding: 24px 28px 40px;
            flex: 1;
            min-width: 0;
        }
        .card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            margin-bottom: 20px;
            min-width: 0;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .card-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 10px;
        }
        .card-title { font-size: 17px; font-weight: 700; color: #0f172a; }

        /* Tables & Responsive Wrapper */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        .data-table {
            width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px;
            min-width: 580px;
        }
        .data-table th {
            background: #f8fafc; color: var(--text-muted);
            padding: 10px 14px; font-weight: 600; font-size: 12.5px;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        .data-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .badge {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 3px 8px;
            border-radius: 16px; font-size: 11.5px; font-weight: 600;
            white-space: nowrap;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #e0f2fe; color: #075985; }

        /* Buttons & Forms */
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 8px 16px; border-radius: 8px; font-size: 13.5px; font-weight: 600;
            cursor: pointer; text-decoration: none; border: none; transition: 0.15s;
            min-height: 38px;
            gap: 6px;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-outline { background: #ffffff; border: 1px solid var(--border); color: var(--text-main); }
        .btn-outline:hover { background: #f8fafc; border-color: #cbd5e1; }
        .btn-sm { padding: 6px 12px; font-size: 12px; min-height: 32px; }

        /* Stats Grid */
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px; margin-bottom: 20px;
        }
        .stat-card {
            background: #ffffff; border: 1px solid var(--border);
            border-radius: 12px; padding: 18px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .stat-label { font-size: 12.5px; color: var(--text-muted); margin-bottom: 4px; font-weight: 500; }
        .stat-value { font-size: 22px; font-weight: 800; color: var(--text-main); letter-spacing: -0.02em; }
        .stat-desc { font-size: 11.5px; color: var(--text-muted); margin-top: 4px; }

        .alert-bar {
            padding: 12px 18px; border-radius: 8px; margin-bottom: 18px; font-size: 13.5px; line-height: 1.5;
        }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* Mobile Bottom Thumb Navigation */
        .mobile-bottom-nav {
            display: none;
            position: fixed;
            bottom: 0; left: 0; right: 0;
            height: 60px;
            background: #ffffff;
            border-top: 1px solid var(--border);
            z-index: 100;
            padding: 4px 8px;
            justify-content: space-around;
            align-items: center;
            box-shadow: 0 -4px 16px rgba(0,0,0,0.06);
        }
        .mobile-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            color: #64748b;
            text-decoration: none;
            background: transparent;
            border: none;
            font-size: 10.5px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 8px;
            cursor: pointer;
            min-width: 54px;
            transition: color 0.15s;
        }
        .mobile-nav-item:hover, .mobile-nav-item.active {
            color: var(--primary);
        }

        /* ==========================================================================
           RESPONSIVE BREAKPOINTS (Mobile-First Adaptive Design)
           ========================================================================== */
        @media (max-width: 1023px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.4);
            }
            .sidebar.is-open {
                transform: translateX(0);
            }
            .sidebar-close-btn {
                display: inline-flex;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            .sidebar-toggle-btn {
                display: inline-flex;
            }
            .topbar-brand-mobile {
                display: flex;
            }
            .topbar {
                padding: 0 16px;
                height: 58px;
            }
            .topbar-tagline {
                display: none;
            }
        }

        @media (max-width: 767px) {
            .mobile-bottom-nav {
                display: flex;
            }
            .main-content {
                padding-bottom: 64px;
            }
            .content-area {
                padding: 16px 14px 24px;
            }
            .card {
                padding: 16px;
                border-radius: 10px;
                margin-bottom: 16px;
            }
            .topbar-tz-badge {
                display: none;
            }
            .topbar-user-info {
                display: none;
            }
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
                gap: 10px;
            }
            .stat-card {
                padding: 12px;
            }
            .stat-value {
                font-size: 19px;
            }
            footer {
                flex-direction: column;
                gap: 6px;
                text-align: center;
                padding: 14px 16px 20px !important;
            }
            .topbar-download-btn span {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Drawer Backdrop -->
    <div id="sidebar-backdrop" class="sidebar-backdrop" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside id="sidebar-drawer" class="sidebar">
        <div class="brand">
            <div style="display: flex; align-items: center; gap: 10px; min-width: 0; flex: 1;">
                @if(\App\Models\Setting::getLogoUrl())
                    <img id="sidebar-brand-logo" 
                         src="{{ \App\Models\Setting::getLogoUrl() }}" 
                         alt="{{ \App\Models\Setting::getAppName() }}" 
                         onerror="this.style.display='none'; document.getElementById('sidebar-brand-badge-fallback').style.display='flex';" 
                         style="max-height: 38px; max-width: 44px; border-radius: 8px; object-fit: contain; flex-shrink: 0; background: rgba(255,255,255,0.06); padding: 2px;">
                    <div id="sidebar-brand-badge-fallback" class="brand-badge" style="display: none; flex-shrink: 0;">{{ strtoupper(substr(\App\Models\Setting::getAppName(), 0, 1)) }}</div>
                @else
                    <div class="brand-badge" style="flex-shrink: 0;">{{ strtoupper(substr(\App\Models\Setting::getAppName(), 0, 1)) }}</div>
                @endif
                <div class="brand-text" style="min-width: 0;">
                    <h1 style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ \App\Models\Setting::getAppName() }}</h1>
                    <span title="{{ \App\Models\Setting::getAppTagline() }}">{{ \Illuminate\Support\Str::limit(\App\Models\Setting::getAppTagline(), 22) }}</span>
                </div>
            </div>
            <button type="button" class="sidebar-close-btn" onclick="toggleSidebar()" aria-label="Tutup Menu">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <ul class="nav-menu">
            <li><a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"><x-icon name="dashboard" size="18" /> <span>Dashboard</span></a></li>
            <li><a href="{{ route('cash-books.index') }}" class="nav-link {{ request()->routeIs('cash-books.*') ? 'active' : '' }}" style="color: #4ade80; font-weight: 700;"><x-icon name="cash-book" size="18" /> <span>Buku Kas</span></a></li>
            
            <li class="nav-section-label">OPERASIONAL</li>
            <li><a href="{{ route('branches.index') }}" class="nav-link {{ request()->routeIs('branches.*') ? 'active' : '' }}"><x-icon name="building" size="18" /> <span>Cabang</span></a></li>
            <li><a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}"><x-icon name="food" size="18" /> <span>Produk</span></a></li>
            <li><a href="{{ route('modifier-groups.index') }}" class="nav-link {{ request()->routeIs('modifier-groups.*') ? 'active' : '' }}"><x-icon name="modifier" size="18" /> <span>Modifier & Varian</span></a></li>
            <li><a href="{{ route('categories.index') }}" class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}"><x-icon name="category" size="18" /> <span>Kategori</span></a></li>
            <li><a href="{{ route('order-types.index') }}" class="nav-link {{ request()->routeIs('order-types.*') ? 'active' : '' }}"><x-icon name="order-type" size="18" /> <span>Jenis Pesanan</span></a></li>
            <li><a href="{{ route('payment-methods.index') }}" class="nav-link {{ request()->routeIs('payment-methods.*') ? 'active' : '' }}"><x-icon name="payment" size="18" /> <span>Metode Bayar</span></a></li>
            <li><a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"><x-icon name="users" size="18" /> <span>Pengguna</span></a></li>
            <li><a href="{{ route('promos.index') }}" class="nav-link {{ request()->routeIs('promos.*') ? 'active' : '' }}"><x-icon name="promo" size="18" /> <span>Promo</span></a></li>

            <li class="nav-section-label">LAPORAN & AUDIT</li>
            <li><a href="{{ route('reconciliation.index') }}" class="nav-link {{ request()->routeIs('reconciliation.*') ? 'active' : '' }}"><x-icon name="shift" size="18" /> <span>Shift Kasir</span></a></li>
            <li><a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"><x-icon name="reports" size="18" /> <span>Laporan</span></a></li>
            <li><a href="{{ route('fraud.index') }}" class="nav-link {{ request()->routeIs('fraud.*') ? 'active' : '' }}"><x-icon name="fraud" size="18" /> <span>Deteksi Fraud</span></a></li>
            <li><a href="{{ route('audit.index') }}" class="nav-link {{ request()->routeIs('audit.*') ? 'active' : '' }}"><x-icon name="audit" size="18" /> <span>Audit Log</span></a></li>

            <li class="nav-section-label">PENGATURAN</li>
            <li><a href="{{ route('settings.general') }}" class="nav-link {{ request()->routeIs('settings.general*') ? 'active' : '' }}"><x-icon name="settings" size="18" /> <span>Pengaturan Umum</span></a></li>
            <li><a href="{{ route('settings.smtp') }}" class="nav-link {{ request()->routeIs('settings.smtp*') ? 'active' : '' }}"><x-icon name="mail" size="18" /> <span>Email & SMTP</span></a></li>
            <li><a href="{{ route('app.download.page') }}" class="nav-link {{ request()->routeIs('app.download*') ? 'active' : '' }}"><x-icon name="download" size="18" /> <span>Unduh Aplikasi POS</span></a></li>
            <li id="sidebar-pwa-item" style="display: none;">
                <a href="javascript:void(0)" onclick="window.hiroshimaInstallPWA && window.hiroshimaInstallPWA()" class="nav-link" style="color: #38bdf8;">
                    <x-icon name="smartphone" size="18" /> <span>Pasang PWA Backoffice</span>
                </a>
            </li>

            <li class="nav-section-label">APLIKASI MOBILE</li>
            <li>
                <a href="{{ route('app.download.page') }}" class="nav-link {{ request()->routeIs('app.download*') ? 'active' : '' }}" style="color: #38bdf8;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                        <line x1="12" y1="18" x2="12.01" y2="18"></line>
                    </svg>
                    <span>Download Hiroshima</span>
                    <span class="badge" style="background: rgba(56, 189, 248, 0.2); color: #38bdf8; font-size: 10px; margin-left: auto; padding: 2px 6px;">APK</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Container -->
    <div class="main-content">
        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                <button type="button" class="sidebar-toggle-btn" onclick="toggleSidebar()" aria-label="Buka Menu Sidebar">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                </button>
                <div class="topbar-brand-mobile" style="align-items: center; gap: 8px;">
                    @if(\App\Models\Setting::getLogoUrl())
                        <img src="{{ \App\Models\Setting::getLogoUrl() }}" alt="Logo" onerror="this.style.display='none'" style="max-height: 28px; max-width: 32px; border-radius: 4px; object-fit: contain;">
                    @endif
                    <strong style="font-size: 15px; font-weight: 800; color: #0f172a; white-space: nowrap;">{{ \App\Models\Setting::getAppName() }}</strong>
                </div>
                <div class="topbar-tagline">
                    <strong style="font-size: 13.5px; color: var(--text-muted); font-weight: 500;">{{ \App\Models\Setting::getAppTagline() }}</strong>
                </div>
            </div>

            <div class="header-actions" style="display: flex; align-items: center; gap: 10px;">
                <a href="{{ route('app.download') }}" class="btn btn-outline btn-sm topbar-download-btn" style="border-color: #cbd5e1; color: #0f172a; font-weight: 600; gap: 6px; padding: 6px 12px; background: #ffffff;" title="Unduh Aplikasi Mobile Hiroshima (APK)">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <span>Download App Hiroshima</span>
                </a>
                <span class="badge badge-info topbar-tz-badge">WIB (Asia/Jakarta)</span>
                @auth
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="topbar-user-info" style="text-align: right;">
                            <div style="font-size: 13px; font-weight: 700; color: #0f172a;">{{ auth()->user()->name }}</div>
                            <div style="font-size: 11px; color: var(--text-muted);">{{ strtoupper(auth()->user()->role) }}</div>
                        </div>
                        <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                            @csrf
                            <button type="submit" class="btn btn-outline btn-sm" style="color: #dc2626; border-color: #fecaca;" onclick="return confirm('Keluar dari sesi backoffice?')">Keluar</button>
                        </form>
                    </div>
                @endauth
            </div>
        </header>

        <main class="content-area">
            @if(session('success'))
                <div class="alert-bar alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert-bar alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert-bar alert-danger">
                    <ul style="margin-left: 20px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session('import_warnings'))
                <div class="alert-bar" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                    <strong>Peringatan Format Impor:</strong>
                    <ul style="margin-left: 20px; margin-top: 6px;">
                        @foreach(session('import_warnings') as $warn)
                            <li>{{ $warn }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session('import_errors'))
                <div class="alert-bar alert-danger">
                    <strong>Kendala Format Impor:</strong>
                    <ul style="margin-left: 20px; margin-top: 6px;">
                        @foreach(session('import_errors') as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>

        <footer style="padding: 16px 28px; border-top: 1px solid var(--border); font-size: 12px; color: var(--text-muted); display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
            <div>{{ \App\Models\Setting::getFooterText() }}</div>
            <div>{{ \App\Models\Setting::getCompanyName() }}</div>
        </footer>
    </div>

    <!-- Mobile Bottom Thumb Navigation -->
    <nav class="mobile-bottom-nav">
        <a href="{{ route('dashboard') }}" class="mobile-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <x-icon name="dashboard" size="20" />
            <span>Dashboard</span>
        </a>
        <a href="{{ route('cash-books.index') }}" class="mobile-nav-item {{ request()->routeIs('cash-books.*') ? 'active' : '' }}">
            <x-icon name="cash-book" size="20" />
            <span>Buku Kas</span>
        </a>
        <a href="{{ route('products.index') }}" class="mobile-nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}">
            <x-icon name="food" size="20" />
            <span>Produk</span>
        </a>
        <a href="{{ route('reports.index') }}" class="mobile-nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <x-icon name="reports" size="20" />
            <span>Laporan</span>
        </a>
        <button type="button" class="mobile-nav-item" onclick="toggleSidebar()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
            <span>Menu</span>
        </button>
    </nav>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar-drawer');
            const backdrop = document.getElementById('sidebar-backdrop');
            if (!sidebar || !backdrop) return;
            const isOpen = sidebar.classList.contains('is-open');
            if (isOpen) {
                sidebar.classList.remove('is-open');
                backdrop.classList.remove('active');
                document.body.style.overflow = '';
            } else {
                sidebar.classList.add('is-open');
                backdrop.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
        // Auto-close drawer on Esc key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const sidebar = document.getElementById('sidebar-drawer');
                const backdrop = document.getElementById('sidebar-backdrop');
                if (sidebar && sidebar.classList.contains('is-open')) {
                    sidebar.classList.remove('is-open');
                    backdrop.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }
        });
    </script>

    @stack('scripts')
    <!-- PWA Service Worker Registration & Install Engine -->
    <script defer src="{{ asset('pwa-register.js') }}"></script>
</body>
</html>
