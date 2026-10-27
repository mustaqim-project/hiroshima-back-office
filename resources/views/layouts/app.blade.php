<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Hiroshima POS' }} - Back-office F&B</title>
    <!-- System font stack (Self-contained, zero external dependency) -->
    <style>
        :root {
            --primary: #dc2626;
            --primary-hover: #b91c1c;
            --dark-surface: #0f172a;
            --card-surface: #ffffff;
            --border: #e2e8f0;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --success: #16a34a;
            --warning: #d97706;
            --danger: #dc2626;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        body { background-color: #f8fafc; color: var(--text-main); display: flex; min-height: 100vh; }
        
        /* Sidebar Navigation */
        .sidebar {
            width: 260px;
            background: var(--dark-surface);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            padding: 24px 16px;
            position: fixed;
            top: 0; bottom: 0; left: 0;
            z-index: 50;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 24px;
            border-bottom: 1px solid #1e293b;
            margin-bottom: 20px;
        }
        .brand-badge {
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 20px; color: #fff;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }
        .brand-text h1 { font-size: 16px; font-weight: 700; letter-spacing: -0.02em; }
        .brand-text span { font-size: 12px; color: #94a3b8; }

        .nav-menu { display: flex; flex-direction: column; gap: 6px; list-style: none; }
        .nav-link {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 14px;
            color: #94a3b8; text-decoration: none;
            font-size: 14px; font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .nav-link:hover, .nav-link.active {
            background: #1e293b;
            color: #ffffff;
        }
        .nav-link.active {
            border-left: 4px solid var(--primary);
            color: #ef4444;
        }

        /* Main Content Container */
        .main-content {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .topbar {
            height: 70px;
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 32px;
            position: sticky; top: 0; z-index: 40;
        }
        .content-area {
            padding: 32px;
            flex: 1;
        }
        .card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }
        .card-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }
        .card-title { font-size: 18px; font-weight: 700; }

        /* Tables & Badges */
        .data-table {
            width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;
        }
        .data-table th {
            background: #f8fafc; color: var(--text-muted);
            padding: 12px 16px; font-weight: 600;
            border-bottom: 1px solid var(--border);
        }
        .data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
        }
        .badge {
            display: inline-block; padding: 4px 10px;
            border-radius: 20px; font-size: 12px; font-weight: 600;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #e0f2fe; color: #075985; }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 10px 18px; border-radius: 8px; font-size: 14px; font-weight: 600;
            cursor: pointer; text-decoration: none; border: none; transition: 0.2s;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text-main); }
        .btn-outline:hover { background: #f8fafc; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }

        /* Stats Grid */
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px; margin-bottom: 24px;
        }
        .stat-card {
            background: #ffffff; border: 1px solid var(--border);
            border-radius: 12px; padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .stat-label { font-size: 13px; color: var(--text-muted); margin-bottom: 6px; font-weight: 500; }
        .stat-value { font-size: 26px; font-weight: 800; color: var(--text-main); }
        .stat-desc { font-size: 12px; color: var(--text-muted); margin-top: 6px; }

        .alert-bar {
            padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;
        }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-badge">H</div>
            <div class="brand-text">
                <h1>HIROSHIMA POS</h1>
                <span>F&B Multi-Cabang</span>
            </div>
        </div>

        <ul class="nav-menu">
            <li><a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"><x-icon name="dashboard" size="18" /> <span>Dashboard</span></a></li>
            <li><a href="{{ route('cash-books.index') }}" class="nav-link {{ request()->routeIs('cash-books.*') ? 'active' : '' }}" style="color: #4ade80; font-weight: 700;"><x-icon name="cash-book" size="18" /> <span>Buku Kas (Finance)</span></a></li>
            
            <li style="padding: 12px 16px 4px; font-size: 11px; font-weight: 700; color: #64748b; letter-spacing: 0.5px;">DATA OPERASIONAL STREET FOOD</li>
            <li><a href="{{ route('branches.index') }}" class="nav-link {{ request()->routeIs('branches.*') ? 'active' : '' }}"><x-icon name="building" size="18" /> <span>Cabang & PIN</span></a></li>
            <li><a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}"><x-icon name="food" size="18" /> <span>Menu & Produk</span></a></li>
            <li><a href="{{ route('modifier-groups.index') }}" class="nav-link {{ request()->routeIs('modifier-groups.*') ? 'active' : '' }}"><x-icon name="modifier" size="18" /> <span>Grup Modifier & Varian</span></a></li>
            <li><a href="{{ route('categories.index') }}" class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}"><x-icon name="category" size="18" /> <span>Kategori Menu</span></a></li>
            <li><a href="{{ route('order-types.index') }}" class="nav-link {{ request()->routeIs('order-types.*') ? 'active' : '' }}"><x-icon name="order-type" size="18" /> <span>Jenis Pesanan (Order Types)</span></a></li>
            <li><a href="{{ route('payment-methods.index') }}" class="nav-link {{ request()->routeIs('payment-methods.*') ? 'active' : '' }}"><x-icon name="payment" size="18" /> <span>Metode Bayar</span></a></li>
            <li><a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"><x-icon name="users" size="18" /> <span>Pengguna & Akses</span></a></li>
            <li><a href="{{ route('promos.index') }}" class="nav-link {{ request()->routeIs('promos.*') ? 'active' : '' }}"><x-icon name="promo" size="18" /> <span>Promo & Diskon</span></a></li>

            <li style="padding: 12px 16px 4px; font-size: 11px; font-weight: 700; color: #64748b; letter-spacing: 0.5px;">PENGAWASAN & LAPORAN</li>
            <li><a href="{{ route('reconciliation.index') }}" class="nav-link {{ request()->routeIs('reconciliation.*') ? 'active' : '' }}"><x-icon name="shift" size="18" /> <span>Rekonsiliasi Shift</span></a></li>
            <li><a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"><x-icon name="reports" size="18" /> <span>Laporan Bisnis & Kas</span></a></li>
            <li><a href="{{ route('fraud.index') }}" class="nav-link {{ request()->routeIs('fraud.*') ? 'active' : '' }}"><x-icon name="fraud" size="18" /> <span>Deteksi Fraud</span></a></li>
            <li><a href="{{ route('audit.index') }}" class="nav-link {{ request()->routeIs('audit.*') ? 'active' : '' }}"><x-icon name="audit" size="18" /> <span>Audit Log Chain</span></a></li>

            <li style="padding: 12px 16px 4px; font-size: 11px; font-weight: 700; color: #64748b; letter-spacing: 0.5px;">PENGATURAN SISTEM</li>
            <li><a href="{{ route('settings.smtp') }}" class="nav-link {{ request()->routeIs('settings.smtp*') ? 'active' : '' }}"><x-icon name="settings" size="18" /> <span>Pengaturan SMTP Mail</span></a></li>
        </ul>
    </aside>

    <!-- Main Container -->
    <div class="main-content">
        <header class="topbar">
            <div>
                <strong style="font-size: 14px; color: var(--text-muted);">Pusat Kendali Operasional Hiroshima F&B</strong>
            </div>
            <div style="display: flex; align-items: center; gap: 16px;">
                <span class="badge badge-info">WIB (Asia/Jakarta)</span>
                @auth
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="text-align: right;">
                            <div style="font-size: 13px; font-weight: 700;">{{ auth()->user()->name }}</div>
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
                <div class="alert-bar alert-danger" style="background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert-bar alert-danger" style="background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;">
                    <ul style="margin-left: 20px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
