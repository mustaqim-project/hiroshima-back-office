<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>HIROSHIMA • Japanese Street Food (広島 日本の屋台料理)</title>
    <meta name="description" content="Sensasi otentik street food Jepang dari Hiroshima. Ramen kuah gurih, chicken katsu mentai renyah, karaage lezat, dan ocha dingin segar. 100% bahan halal pilihan.">

    <!-- PWA & Mobile Web Capabilities -->
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#dc2626">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Hiroshima">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">

    <!-- Google Fonts: Plus Jakarta Sans & Shippori Mincho -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Shippori+Mincho:wght@700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-main: #0B0B0E;
            --bg-surface: #14141B;
            --bg-card: #1A1A24;
            --bg-card-hover: #222230;
            --border-subtle: rgba(255, 255, 255, 0.08);
            --border-highlight: rgba(225, 29, 72, 0.35);
            --red-torii: #E11D48;
            --red-glow: rgba(225, 29, 72, 0.25);
            --red-dark: #9F1239;
            --gold-crispy: #F59E0B;
            --gold-light: #FEF3C7;
            --text-primary: #F8FAFC;
            --text-secondary: #94A3B8;
            --text-muted: #64748B;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-main);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        .font-jp {
            font-family: 'Shippori Mincho', serif;
        }

        /* Glassmorphism & Cards */
        .glass-panel {
            background: rgba(20, 20, 27, 0.75);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid var(--border-subtle);
        }

        .food-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
        }

        .food-card:hover {
            transform: translateY(-4px);
            border-color: var(--border-highlight);
            box-shadow: 0 16px 32px -8px rgba(0, 0, 0, 0.6), 0 0 20px -4px var(--red-glow);
        }

        /* Buttons */
        .btn-torii {
            background: linear-gradient(135deg, #E11D48 0%, #BE123C 100%);
            color: #FFFFFF;
            font-weight: 700;
            padding: 12px 24px;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 14px 0 rgba(225, 29, 72, 0.38);
            transition: all 0.2s ease;
        }

        .btn-torii:hover {
            background: linear-gradient(135deg, #F43F5E 0%, #E11D48 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px 0 rgba(225, 29, 72, 0.5);
        }

        .btn-outline {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            font-weight: 600;
            padding: 12px 22px;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            border: 1px solid var(--border-subtle);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        /* Categories tabs */
        .cat-pill {
            padding: 8px 18px;
            border-radius: 9999px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            background: var(--bg-card);
            color: var(--text-secondary);
            border: 1px solid var(--border-subtle);
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .cat-pill:hover, .cat-pill.active {
            background: var(--red-torii);
            color: #FFFFFF;
            border-color: var(--red-torii);
            box-shadow: 0 4px 12px rgba(225, 29, 72, 0.35);
        }

        /* Japanese subtle watermark */
        .jp-watermark {
            position: absolute;
            font-family: 'Shippori Mincho', serif;
            font-size: 14vw;
            font-weight: 800;
            color: rgba(255, 255, 255, 0.02);
            user-select: none;
            pointer-events: none;
            z-index: 0;
            line-height: 1;
        }

        /* Badges */
        .badge-tag {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-red {
            background: rgba(225, 29, 72, 0.15);
            color: #FDA4AF;
            border: 1px solid rgba(225, 29, 72, 0.3);
        }

        .badge-gold {
            background: rgba(245, 158, 11, 0.15);
            color: #FCD34D;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .badge-green {
            background: rgba(16, 185, 129, 0.15);
            color: #6EE7B7;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        /* Layout Container */
        .container-custom {
            max-width: 1240px;
            margin-left: auto;
            margin-right: auto;
            padding-left: 20px;
            padding-right: 20px;
        }

        @media (min-width: 900px) {
            #desktopNavLinks {
                display: flex !important;
            }
            #heroGrid {
                grid-template-columns: 1.15fr 0.85fr !important;
            }
            #posCalloutGrid {
                grid-template-columns: 1.3fr 0.7fr !important;
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: var(--bg-main);
        }
        ::-webkit-scrollbar-thumb {
            background: #2D2D3D;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--red-torii);
        }
    </style>
</head>
<body>

@php
    $categories = \App\Models\Category::where('is_active', true)->orderBy('sort_order')->get();
    $products = \App\Models\Product::with(['category', 'prices'])->where('is_active', true)->get();
    $branches = \App\Models\Branch::where('is_active', true)->get();
    $promos = \App\Models\Promo::where('is_active', true)->get();
    $orderTypes = \App\Models\OrderType::where('is_active', true)->get();
    $dineInId = $orderTypes->firstWhere('code', 'dine_in')?->id ?? 1;
    $deliveryId = $orderTypes->firstWhere('code', 'delivery')?->id ?? 3;
@endphp

<!-- TOP ANNOUNCEMENT TICKER -->
<div style="background: linear-gradient(90deg, #9F1239 0%, #E11D48 50%, #9F1239 100%); color: #FFF; font-size: 12.5px; font-weight: 700; padding: 7px 16px; text-align: center; position: relative; z-index: 50; overflow: hidden; letter-spacing: 0.2px;">
    <div style="display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: center;">
        <span style="background: #FFF; color: #E11D48; padding: 2px 7px; border-radius: 999px; font-size: 10px; font-weight: 800;">PROMO SPESIAL</span>
        <span>🎉 Diskon Grand Opening 20% & Potongan Rp 10.000 di Seluruh Gerai Hiroshima!</span>
        <span style="opacity: 0.85;">• 100% Bahan Halal Pilihan • Buka Setiap Hari 10:00 - 22:00 WIB</span>
    </div>
</div>

<!-- STICKY NAVBAR -->
<nav class="glass-panel" style="position: sticky; top: 0; z-index: 40; border-bottom: 1px solid var(--border-subtle); width: 100%;">
    <div class="container-custom" style="display: flex; justify-content: space-between; align-items: center; height: 68px;">
        <!-- Brand Logo & Name -->
        <a href="/" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: linear-gradient(135deg, #E11D48 0%, #9F1239 100%); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(225, 29, 72, 0.4); border: 1.5px solid rgba(255, 255, 255, 0.2);">
                <span class="font-jp" style="font-size: 22px; font-weight: 800; color: #FFF;">広</span>
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="font-size: 18px; font-weight: 800; letter-spacing: 1px; color: #FFFFFF;">HIROSHIMA</span>
                    <span class="font-jp" style="font-size: 13px; color: #FDA4AF; font-weight: 700;">広島</span>
                </div>
                <div style="font-size: 10.5px; color: var(--gold-crispy); font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;">
                    Japanese Street Food
                </div>
            </div>
        </a>

        <!-- Desktop Nav Links -->
        <div style="display: none; align-items: center; gap: 28px;" id="desktopNavLinks">
            <a href="#menu" style="font-size: 14px; font-weight: 600; color: var(--text-secondary); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#FFF'" onmouseout="this.style.color='var(--text-secondary)'">Menu Favorit</a>
            <a href="#keunggulan" style="font-size: 14px; font-weight: 600; color: var(--text-secondary); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#FFF'" onmouseout="this.style.color='var(--text-secondary)'">Keunggulan</a>
            <a href="#cabang" style="font-size: 14px; font-weight: 600; color: var(--text-secondary); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#FFF'" onmouseout="this.style.color='var(--text-secondary)'">Gerai & Lokasi</a>
            <a href="#promo" style="font-size: 14px; font-weight: 600; color: var(--text-secondary); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#FFF'" onmouseout="this.style.color='var(--text-secondary)'">Promo</a>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; align-items: center; gap: 10px;">
            <a href="#cabang" class="btn-outline" style="padding: 8px 16px; font-size: 13px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>Lokasi</span>
            </a>

            @auth
                <a href="{{ route('dashboard') }}" class="btn-torii" style="padding: 8px 18px; font-size: 13px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                    <span>Dashboard</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="btn-torii" style="padding: 8px 18px; font-size: 13px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/></svg>
                    <span>Akses Kasir</span>
                </a>
            @endauth
        </div>
    </div>
</nav>

<!-- HERO SECTION -->
<section style="position: relative; padding: 70px 0 60px; overflow: hidden; background: radial-gradient(circle at 50% 15%, rgba(225, 29, 72, 0.18) 0%, transparent 65%);">
    <div class="jp-watermark" style="top: 10px; right: 2%;">屋台料理</div>
    <div class="jp-watermark" style="bottom: 0px; left: 2%;">広島</div>

    <div class="container-custom" style="position: relative; z-index: 10;">
        <div style="display: grid; grid-template-columns: 1fr; gap: 40px; align-items: center;" id="heroGrid">
            <div>
                <!-- Top Badge -->
                <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(225, 29, 72, 0.12); border: 1px solid rgba(225, 29, 72, 0.35); padding: 6px 14px; border-radius: 999px; margin-bottom: 20px;">
                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #E11D48; box-shadow: 0 0 8px #E11D48;"></span>
                    <span style="font-size: 12px; font-weight: 700; color: #FDA4AF; letter-spacing: 0.5px;">AUTHENTIC YATAI EXPERIENCE</span>
                </div>

                <!-- Main Headline -->
                <h1 style="font-size: clamp(32px, 5.5vw, 54px); font-weight: 800; line-height: 1.15; margin-bottom: 18px; letter-spacing: -0.5px;">
                    Sensasi Gurih Otentik<br>
                    <span style="background: linear-gradient(135deg, #FFFFFF 30%, #FDA4AF 70%, #E11D48 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Street Food Jepang</span><br>
                    di Setiap Gigitan.
                </h1>

                <!-- Subtitle -->
                <p style="font-size: clamp(15px, 2vw, 17px); color: var(--text-secondary); max-width: 580px; margin-bottom: 30px; line-height: 1.65;">
                    Nikmati kelezatan <strong>Chicken Katsu renyah berpadu saus mentai torched</strong>, <strong>Karage Hot Fire membakar selera</strong>, hingga <strong>Ocha dingin segar</strong>. Diracik dari bahan halal berkualitas bintang lima dengan keramahan harga yatai kaki lima.
                </p>

                <!-- CTA Row -->
                <div style="display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 40px;">
                    <a href="#menu" class="btn-torii" style="padding: 14px 28px; font-size: 15px;">
                        <span>🥢 Lihat Daftar Menu</span>
                    </a>
                    <a href="#cabang" class="btn-outline" style="padding: 14px 24px; font-size: 15px;">
                        <span>📍 Kunjungi Gerai Terdekat</span>
                    </a>
                </div>

                <!-- Trust Stats Row -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 16px; padding-top: 24px; border-top: 1px solid var(--border-subtle); max-width: 560px;">
                    <div>
                        <div style="font-size: 24px; font-weight: 800; color: #FFF;">100%</div>
                        <div style="font-size: 12px; color: var(--text-secondary); font-weight: 600;">Halal Ingredients</div>
                    </div>
                    <div>
                        <div style="font-size: 24px; font-weight: 800; color: var(--gold-crispy);">33+</div>
                        <div style="font-size: 12px; color: var(--text-secondary); font-weight: 600;">Variasi Menu Otentik</div>
                    </div>
                    <div>
                        <div style="font-size: 24px; font-weight: 800; color: #E11D48;">{{ $branches->count() }} Gerai</div>
                        <div style="font-size: 12px; color: var(--text-secondary); font-weight: 600;">Jakarta & Sekitarnya</div>
                    </div>
                    <div>
                        <div style="font-size: 24px; font-weight: 800; color: #10B981;">4.9 ★</div>
                        <div style="font-size: 12px; color: var(--text-secondary); font-weight: 600;">Ulasan Pelanggan</div>
                    </div>
                </div>
            </div>

            <!-- Hero Dishes Visual Showcase -->
            <div style="position: relative;">
                <div style="position: absolute; inset: -20px; background: radial-gradient(circle, rgba(225, 29, 72, 0.28) 0%, transparent 70%); filter: blur(30px); border-radius: 50%; z-index: 1;"></div>

                <div style="position: relative; z-index: 2; display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <!-- Hero Card 1: Paket Hiro 1 -->
                    <div class="food-card" style="padding: 14px;">
                        <div style="border-radius: 12px; overflow: hidden; height: 160px; margin-bottom: 12px; background: #000;">
                            <img src="/storage/products/hrs-001_paket_hiro_satu.jpg" alt="Paket Hiro 1" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='/storage/products/hrs_016_chicken_katsu.jpg'">
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                            <span class="badge-tag badge-red">Best Seller #1</span>
                            <span style="font-size: 14px; font-weight: 800; color: var(--gold-crispy);">Rp 29.000</span>
                        </div>
                        <h3 style="font-size: 14px; font-weight: 700; color: #FFF; margin-bottom: 2px;">Paket Hiro 1</h3>
                        <p style="font-size: 11px; color: var(--text-secondary);">Nasi + Chicken Katsu + Salad + Ocha Dingin</p>
                    </div>

                    <!-- Hero Card 2: Paket Best Hiro -->
                    <div class="food-card" style="padding: 14px; transform: translateY(20px);">
                        <div style="border-radius: 12px; overflow: hidden; height: 160px; margin-bottom: 12px; background: #000;">
                            <img src="/storage/products/hrs-005_paket_best_hiro.jpg" alt="Paket Best Hiro" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='/storage/products/hrs-002_paket_hiro_dua.jpg'">
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                            <span class="badge-tag badge-gold">Special Platter</span>
                            <span style="font-size: 14px; font-weight: 800; color: var(--gold-crispy);">Rp 35.000</span>
                        </div>
                        <h3 style="font-size: 14px; font-weight: 700; color: #FFF; margin-bottom: 2px;">Paket Best Hiro</h3>
                        <p style="font-size: 11px; color: var(--text-secondary);">Kombo lengkap katsu, roll & bumbu spesial</p>
                    </div>

                    <!-- Hero Card 3: Karage Hot Fire -->
                    <div class="food-card" style="padding: 14px;">
                        <div style="border-radius: 12px; overflow: hidden; height: 160px; margin-bottom: 12px; background: #000;">
                            <img src="/storage/products/hrs_031_karage_hot_fire.jpg" alt="Karage Hot Fire" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='/storage/products/hrs_032_karage_original.jpg'">
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                            <span class="badge-tag badge-red">Pedas Mantap 🔥</span>
                            <span style="font-size: 14px; font-weight: 800; color: var(--gold-crispy);">Rp 26.000</span>
                        </div>
                        <h3 style="font-size: 14px; font-weight: 700; color: #FFF; margin-bottom: 2px;">Karage Hot Fire</h3>
                        <p style="font-size: 11px; color: var(--text-secondary);">Karaage renyah berlumur saus pedas Hiroshima</p>
                    </div>

                    <!-- Hero Card 4: Katsu Mentai -->
                    <div class="food-card" style="padding: 14px; transform: translateY(20px);">
                        <div style="border-radius: 12px; overflow: hidden; height: 160px; margin-bottom: 12px; background: #000;">
                            <img src="/storage/products/hrs_018_katsu_mentai.jpg" alt="Chicken Katsu Mentai" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='/storage/products/hrs_020_katsu_mozaro.jpg'">
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                            <span class="badge-tag badge-gold">Mentai Torched</span>
                            <span style="font-size: 14px; font-weight: 800; color: var(--gold-crispy);">Rp 28.000</span>
                        </div>
                        <h3 style="font-size: 14px; font-weight: 700; color: #FFF; margin-bottom: 2px;">Katsu Mentai</h3>
                        <p style="font-size: 11px; color: var(--text-secondary);">Saus mentai gurih dibakar harum di atas katsu</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- PROMO SECTION (IF ANY PROMOS) -->
@if($promos->isNotEmpty())
<section id="promo" style="padding: 30px 0; background: #111117; border-top: 1px solid var(--border-subtle); border-bottom: 1px solid var(--border-subtle);">
    <div class="container-custom">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 20px;">🔥</span>
                <h2 style="font-size: 18px; font-weight: 800; color: #FFF;">Promo & Penawaran Spesial Berlangsung</h2>
            </div>
            <span style="font-size: 12px; color: var(--text-muted);">Tunjukkan promo ini ke kasir saat memesan</span>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
            @foreach($promos as $promo)
                <div style="background: linear-gradient(135deg, rgba(225, 29, 72, 0.12) 0%, rgba(20, 20, 27, 0.8) 100%); border: 1.5px dashed rgba(225, 29, 72, 0.45); border-radius: 14px; padding: 18px; display: flex; align-items: center; gap: 16px;">
                    <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(225, 29, 72, 0.2); display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
                        🎁
                    </div>
                    <div>
                        <div style="font-size: 15px; font-weight: 800; color: #FFF; margin-bottom: 2px;">{{ $promo->name }}</div>
                        <div style="font-size: 12px; color: #FDA4AF; font-weight: 600;">
                            @if($promo->type === 'percentage')
                                Potongan Diskon {{ (int)$promo->value }}%
                            @else
                                Potongan Langsung Rp {{ number_format((int)$promo->value, 0, ',', '.') }}
                            @endif
                        </div>
                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Berlaku di seluruh gerai Hiroshima POS</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- MENU CATALOG SECTION -->
<section id="menu" style="padding: 70px 0; position: relative;">
    <div class="jp-watermark" style="top: 150px; left: 5%;">料理</div>

    <div class="container-custom" style="position: relative; z-index: 10;">
        <div style="text-align: center; max-width: 680px; margin: 0 auto 36px;">
            <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.35); padding: 5px 14px; border-radius: 999px; margin-bottom: 12px;">
                <span class="badge-tag badge-gold" style="padding: 0; background: transparent; border: none;">Katalog Menu</span>
            </div>
            <h2 style="font-size: clamp(26px, 4vw, 38px); font-weight: 800; color: #FFF; margin-bottom: 12px;">
                Pilihan Menu Otentik Hiroshima
            </h2>
            <p style="font-size: 15px; color: var(--text-secondary);">
                Dari paket hemat lengkap, aneka olahan chicken & beef katsu, side dish renyah, hingga teh ocha dingin pereda dahaga.
            </p>
        </div>

        <!-- Filter Category Tabs -->
        <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; margin-bottom: 36px;" id="catFilterContainer">
            <button class="cat-pill active" onclick="filterMenu('all', this)">Semua Menu ({{ $products->count() }})</button>
            @foreach($categories as $cat)
                @php
                    $count = $products->where('category_id', $cat->id)->count();
                @endphp
                @if($count > 0)
                    <button class="cat-pill" onclick="filterMenu('cat-{{ $cat->id }}', this)">
                        {{ $cat->name }} ({{ $count }})
                    </button>
                @endif
            @endforeach
        </div>

        <!-- Product Cards Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px;" id="menuGrid">
            @forelse($products as $prod)
                @php
                    $priceDineIn = $prod->prices->firstWhere('order_type_id', $dineInId)?->price ?? $prod->manual_cost ?? 25000;
                    $priceDelivery = $prod->prices->firstWhere('order_type_id', $deliveryId)?->price ?? (int)($priceDineIn * 1.15);
                    $imageUrl = $prod->image_url ?: '/storage/products/hrs_016_chicken_katsu.jpg';
                @endphp
                <div class="food-card menu-item cat-{{ $prod->category_id }}" data-category="cat-{{ $prod->category_id }}">
                    <!-- Dish Image -->
                    <div style="position: relative; height: 180px; background: #0F0F14; overflow: hidden;">
                        <img src="{{ $imageUrl }}" alt="{{ $prod->name }}" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'" onerror="this.src='/storage/products/hrs_016_chicken_katsu.jpg'">
                        @if($prod->category)
                            <span class="badge-tag badge-red" style="position: absolute; top: 12px; left: 12px;">
                                {{ $prod->category->name }}
                            </span>
                        @endif
                    </div>

                    <!-- Content -->
                    <div style="padding: 16px; display: flex; flex-direction: column; flex-grow: 1;">
                        <h3 style="font-size: 16px; font-weight: 700; color: #FFF; margin-bottom: 6px; line-height: 1.3;">
                            {{ $prod->name }}
                        </h3>

                        <p style="font-size: 12px; color: var(--text-secondary); margin-bottom: 14px; line-height: 1.5; flex-grow: 1;">
                            {{ $prod->description ?: 'Olahan khas Jepang otentik dengan racikan bumbu khas Hiroshima yang gurih dan nikmat.' }}
                        </p>

                        <!-- Multi-Price Display (Dine In & Delivery) -->
                        <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 10px; margin-bottom: 14px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <span style="font-size: 11px; color: var(--text-muted); font-weight: 600;">Dine In / Take Away:</span>
                                <span style="font-size: 15px; font-weight: 800; color: var(--gold-crispy);">Rp {{ number_format($priceDineIn, 0, ',', '.') }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 11px; color: var(--text-muted); font-weight: 600;">Ojol / Delivery:</span>
                                <span style="font-size: 12px; font-weight: 700; color: #FDA4AF;">Rp {{ number_format($priceDelivery, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <a href="https://wa.me/628121111001?text={{ urlencode('Halo Hiroshima, saya ingin memesan menu ' . $prod->name) }}" target="_blank" class="btn-outline" style="width: 100%; padding: 10px; font-size: 12.5px; border-radius: 8px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
                            <span>Pesan via WhatsApp</span>
                        </a>
                    </div>
                </div>
            @empty
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--text-muted);">
                    Belum ada menu yang ditampilkan.
                </div>
            @endforelse
        </div>
    </div>
</section>

<!-- 4 PILAR KEUNGGULAN HIROSHIMA -->
<section id="keunggulan" style="padding: 70px 0; background: #101015; border-top: 1px solid var(--border-subtle); border-bottom: 1px solid var(--border-subtle);">
    <div class="container-custom">
        <div style="text-align: center; max-width: 600px; margin: 0 auto 40px;">
            <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(225, 29, 72, 0.12); border: 1px solid rgba(225, 29, 72, 0.35); padding: 5px 14px; border-radius: 999px; margin-bottom: 12px;">
                <span class="badge-tag badge-red" style="padding: 0; background: transparent; border: none;">Kualitas Terbaik</span>
            </div>
            <h2 style="font-size: clamp(26px, 4vw, 36px); font-weight: 800; color: #FFF; margin-bottom: 12px;">
                Mengapa Memilih Hiroshima?
            </h2>
            <p style="font-size: 15px; color: var(--text-secondary);">
                Kami menghadirkan pengalaman bersantap yatai Jepang yang tulus, lezat, dan bersahabat di setiap sajian.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px;">
            <!-- Pillar 1 -->
            <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: 16px; padding: 28px;">
                <div style="width: 52px; height: 52px; border-radius: 12px; background: rgba(225, 29, 72, 0.15); display: flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 18px;">
                    🍲
                </div>
                <h3 style="font-size: 17px; font-weight: 700; color: #FFF; margin-bottom: 8px;">Racikan Bumbu Otentik</h3>
                <p style="font-size: 13.5px; color: var(--text-secondary); line-height: 1.6;">
                    Resep bumbu marinasi katsu dan kuah ramen diracik dengan standar street food Hiroshima yang kaya akan umami.
                </p>
            </div>

            <!-- Pillar 2 -->
            <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: 16px; padding: 28px;">
                <div style="width: 52px; height: 52px; border-radius: 12px; background: rgba(16, 185, 129, 0.15); display: flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 18px;">
                    🌿
                </div>
                <h3 style="font-size: 17px; font-weight: 700; color: #FFF; margin-bottom: 8px;">100% Bahan Halal & Fresh</h3>
                <p style="font-size: 13.5px; color: var(--text-secondary); line-height: 1.6;">
                    Seluruh daging ayam fillet, daging sapi, dan saus pendamping terjamin halal tanpa kompromi, higienis dan sehat.
                </p>
            </div>

            <!-- Pillar 3 -->
            <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: 16px; padding: 28px;">
                <div style="width: 52px; height: 52px; border-radius: 12px; background: rgba(245, 158, 11, 0.15); display: flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 18px;">
                    🔥
                </div>
                <h3 style="font-size: 17px; font-weight: 700; color: #FFF; margin-bottom: 8px;">Dimasak Fresh (Made to Order)</h3>
                <p style="font-size: 13.5px; color: var(--text-secondary); line-height: 1.6;">
                    Setiap porsi digoreng dan dimasak sesaat setelah Anda memesan, memastikan kerenyahan maksimal saat disantap.
                </p>
            </div>

            <!-- Pillar 4 -->
            <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: 16px; padding: 28px;">
                <div style="width: 52px; height: 52px; border-radius: 12px; background: rgba(59, 130, 246, 0.15); display: flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 18px;">
                    💎
                </div>
                <h3 style="font-size: 17px; font-weight: 700; color: #FFF; margin-bottom: 8px;">Harga Ramah di Kantong</h3>
                <p style="font-size: 13.5px; color: var(--text-secondary); line-height: 1.6;">
                    Kelezatan hidangan Jepang premium mulai dari Rp 8.000 hingga Rp 35.000. Makan kenyang, lezat, tanpa khawatir budget.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- GERAI & LOKASI OUTLET (DATABASE DRIVEN) -->
<section id="cabang" style="padding: 70px 0; position: relative;">
    <div class="container-custom">
        <div style="text-align: center; max-width: 600px; margin: 0 auto 40px;">
            <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(225, 29, 72, 0.12); border: 1px solid rgba(225, 29, 72, 0.35); padding: 5px 14px; border-radius: 999px; margin-bottom: 12px;">
                <span class="badge-tag badge-red" style="padding: 0; background: transparent; border: none;">Lokasi Gerai</span>
            </div>
            <h2 style="font-size: clamp(26px, 4vw, 36px); font-weight: 800; color: #FFF; margin-bottom: 12px;">
                Kunjungi Gerai Hiroshima
            </h2>
            <p style="font-size: 15px; color: var(--text-secondary);">
                Tersedia untuk Dine In (makan di tempat), Take Away, maupun pemesanan via Ojek Online.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
            @foreach($branches as $branch)
                <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: 16px; padding: 24px; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                            <h3 style="font-size: 18px; font-weight: 800; color: #FFF;">{{ $branch->name }}</h3>
                            <span class="badge-tag badge-green">BUKA</span>
                        </div>

                        <div style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 10px; font-size: 13.5px; color: var(--text-secondary);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#E11D48" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>{{ $branch->address ?: 'Pusat Kuliner Kaki Lima, Jakarta' }}</span>
                        </div>

                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; font-size: 13.5px; color: var(--text-secondary);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            <span>{{ $branch->phone ?: '0812-3456-7890' }}</span>
                        </div>

                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 18px; font-size: 12.5px; color: var(--text-muted);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <span>Setiap Hari • 10.00 - 22.00 WIB</span>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <a href="https://maps.google.com/?q={{ urlencode($branch->name . ' ' . $branch->address) }}" target="_blank" class="btn-outline" style="flex: 1; padding: 10px; font-size: 12.5px; text-align: center;">
                            <span>🗺️ Google Maps</span>
                        </a>
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $branch->phone ?: '628121111001') }}?text={{ urlencode('Halo ' . $branch->name . ', saya ingin memesan makanan') }}" target="_blank" class="btn-torii" style="flex: 1; padding: 10px; font-size: 12.5px; text-align: center;">
                            <span>💬 WhatsApp</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- PWA & POS CLOUD CALLOUT -->
<section style="padding: 50px 0; background: linear-gradient(180deg, #101015 0%, #0B0B0E 100%);">
    <div class="container-custom">
        <div style="background: radial-gradient(circle at 80% 20%, rgba(225, 29, 72, 0.22) 0%, rgba(26, 26, 36, 0.9) 60%); border: 1.5px solid var(--border-subtle); border-radius: 20px; padding: 40px; display: grid; grid-template-columns: 1fr; gap: 24px; align-items: center;" id="posCalloutGrid">
            <div>
                <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.35); padding: 4px 12px; border-radius: 999px; margin-bottom: 14px;">
                    <span style="font-size: 11px; font-weight: 700; color: #93C5FD;">SISTEM KASIR & BACK OFFICE TERPADU</span>
                </div>
                <h3 style="font-size: clamp(22px, 3.5vw, 32px); font-weight: 800; color: #FFF; margin-bottom: 10px;">
                    Hiroshima Cloud POS & Manajemen Shift
                </h3>
                <p style="font-size: 14.5px; color: var(--text-secondary); line-height: 1.6; margin-bottom: 24px; max-width: 620px;">
                    Didukung fitur lengkap: manajemen shift kasir 23 jam otomatis, penahanan transaksi (Hold Bill), multi-harga per jenis pesanan, rekonsiliasi kas, dan cetak struk Bluetooth Thermal dua rangkap (Pelanggan & Dapur).
                </p>

                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <a href="{{ route('login') }}" class="btn-torii" style="padding: 12px 24px; font-size: 14px;">
                        <span>🚀 Masuk Portal Kasir / Admin</span>
                    </a>
                    <button id="pwaInstallBtn" onclick="triggerPwaInstall()" class="btn-outline" style="padding: 12px 20px; font-size: 14px; display: none;">
                        <span>📲 Install Aplikasi PWA</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer style="background: #08080A; border-top: 1px solid var(--border-subtle); padding: 50px 0 30px; font-size: 13px; color: var(--text-muted);">
    <div class="container-custom">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 36px; margin-bottom: 40px;">
            <!-- Col 1: Brand -->
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
                    <div style="width: 34px; height: 34px; border-radius: 8px; background: #E11D48; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #FFF; font-size: 18px;" class="font-jp">
                        広
                    </div>
                    <div>
                        <div style="font-size: 16px; font-weight: 800; color: #FFF;">HIROSHIMA</div>
                        <div style="font-size: 10px; color: var(--gold-crispy); font-weight: 700;">JAPANESE STREET FOOD</div>
                    </div>
                </div>
                <p style="font-size: 13px; line-height: 1.6; color: var(--text-secondary); margin-bottom: 14px;">
                    Menyajikan hidangan street food Jepang otentik dengan racikan bumbu khas Hiroshima, terjamin halal dan ramah di kantong.
                </p>
            </div>

            <!-- Col 2: Nav Links -->
            <div>
                <div style="font-size: 14px; font-weight: 700; color: #FFF; margin-bottom: 14px;">Menu & Navigasi</div>
                <ul style="list-style: none; display: flex; flex-direction: column; gap: 8px;">
                    <li><a href="#menu" style="color: var(--text-secondary); text-decoration: none;" onmouseover="this.style.color='#FFF'" onmouseout="this.style.color='var(--text-secondary)'">Katalog Menu</a></li>
                    <li><a href="#keunggulan" style="color: var(--text-secondary); text-decoration: none;" onmouseover="this.style.color='#FFF'" onmouseout="this.style.color='var(--text-secondary)'">Keunggulan Kualitas</a></li>
                    <li><a href="#cabang" style="color: var(--text-secondary); text-decoration: none;" onmouseover="this.style.color='#FFF'" onmouseout="this.style.color='var(--text-secondary)'">Daftar Gerai Jakarta</a></li>
                    <li><a href="#promo" style="color: var(--text-secondary); text-decoration: none;" onmouseover="this.style.color='#FFF'" onmouseout="this.style.color='var(--text-secondary)'">Promo Berjalan</a></li>
                </ul>
            </div>

            <!-- Col 3: Backoffice Links -->
            <div>
                <div style="font-size: 14px; font-weight: 700; color: #FFF; margin-bottom: 14px;">Sistem & Akses Staf</div>
                <ul style="list-style: none; display: flex; flex-direction: column; gap: 8px;">
                    <li><a href="{{ route('login') }}" style="color: var(--text-secondary); text-decoration: none;" onmouseover="this.style.color='#FFF'" onmouseout="this.style.color='var(--text-secondary)'">Login Kasir POS</a></li>
                    <li><a href="{{ route('login') }}" style="color: var(--text-secondary); text-decoration: none;" onmouseover="this.style.color='#FFF'" onmouseout="this.style.color='var(--text-secondary)'">Portal Back Office</a></li>
                    <li><a href="{{ route('pin.request') }}" style="color: var(--text-secondary); text-decoration: none;" onmouseover="this.style.color='#FFF'" onmouseout="this.style.color='var(--text-secondary)'">Lupa PIN Kasir</a></li>
                    <li><a href="{{ route('password.request') }}" style="color: var(--text-secondary); text-decoration: none;" onmouseover="this.style.color='#FFF'" onmouseout="this.style.color='var(--text-secondary)'">Reset Password</a></li>
                </ul>
            </div>

            <!-- Col 4: Contact & Social -->
            <div>
                <div style="font-size: 14px; font-weight: 700; color: #FFF; margin-bottom: 14px;">Kontak & Jam Buka</div>
                <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;">
                    Buka Setiap Hari: <strong>10.00 - 22.00 WIB</strong>
                </div>
                <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;">
                    Email: support@hiroshima.web.id
                </div>
                <div style="font-size: 13px; color: var(--text-secondary);">
                    WhatsApp CS: +62 812-1111-001
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-subtle); padding-top: 20px; flex-wrap: wrap; gap: 12px;">
            <div>
                &copy; {{ date('Y') }} HIROSHIMA Japanese Street Food (広島 日本の屋台料理). Hak Cipta Dilindungi.
            </div>
            <div style="display: flex; gap: 16px;">
                <span style="color: var(--text-muted); font-size: 12px;">PWA Ready • Cloud POS v2.2</span>
            </div>
        </div>
    </div>
</footer>

<!-- PWA Service Worker & Install Script -->
<script src="{{ asset('pwa-register.js') }}" defer></script>
<script>
    // Responsive Desktop Nav Layout Adjustments
    function checkDesktopNav() {
        const desktopNav = document.getElementById('desktopNavLinks');
        const heroGrid = document.getElementById('heroGrid');
        const posCalloutGrid = document.getElementById('posCalloutGrid');

        if (window.innerWidth >= 900) {
            if (desktopNav) desktopNav.style.display = 'flex';
            if (heroGrid) heroGrid.style.gridTemplateColumns = '1.15fr 0.85fr';
            if (posCalloutGrid) posCalloutGrid.style.gridTemplateColumns = '1.3fr 0.7fr';
        } else {
            if (desktopNav) desktopNav.style.display = 'none';
            if (heroGrid) heroGrid.style.gridTemplateColumns = '1fr';
            if (posCalloutGrid) posCalloutGrid.style.gridTemplateColumns = '1fr';
        }
    }
    window.addEventListener('resize', checkDesktopNav);
    window.addEventListener('DOMContentLoaded', checkDesktopNav);

    // Category Filtering
    function filterMenu(catClass, btn) {
        // Toggle active button
        const container = document.getElementById('catFilterContainer');
        if (container) {
            container.querySelectorAll('.cat-pill').forEach(b => b.classList.remove('active'));
        }
        btn.classList.add('active');

        // Filter cards
        const items = document.querySelectorAll('.menu-item');
        items.forEach(card => {
            if (catClass === 'all') {
                card.style.display = 'flex';
            } else {
                if (card.classList.contains(catClass)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            }
        });
    }

    // PWA Install Event Handler
    let deferredPrompt = null;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        const installBtn = document.getElementById('pwaInstallBtn');
        if (installBtn) {
            installBtn.style.display = 'inline-flex';
        }
    });

    function triggerPwaInstall() {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('User accepted PWA installation');
                }
                deferredPrompt = null;
                const installBtn = document.getElementById('pwaInstallBtn');
                if (installBtn) installBtn.style.display = 'none';
            });
        } else {
            alert('Aplikasi PWA Hiroshima dapat di-install melalui menu titik tiga (⋮) atau opsi "Install App / Tambahkan ke Layar Utama" di browser Anda.');
        }
    }
</script>

</body>
</html>
