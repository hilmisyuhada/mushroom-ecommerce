<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
$user = currentUser();
$csrfToken = csrfToken();
$catalogProducts = $pdo->query('SELECT id, name, price, image, weight_gram, category, badge, claim, description, composition, highlight_one_icon, highlight_one_title, highlight_one_text, highlight_two_icon, highlight_two_title, highlight_two_text FROM products WHERE is_active = 1 ORDER BY created_at DESC')->fetchAll();
$storeSettings = storeSettings();
$aboutTitle = $storeSettings['about_title'] ?? 'Dari Budidaya Jamur hingga Produk Bernilai';
$aboutDescription = $storeSettings['about_description'] ?? 'Mushroom Organik hadir dengan semangat mengembangkan jamur tiram menjadi produk pangan yang berkualitas, inovatif, dan memiliki nilai tambah.';
$aboutBadgeTitle = $storeSettings['about_badge_title'] ?? 'Integrated Healthy Food Ecosystem';
$aboutBadgeText = $storeSettings['about_badge_text'] ?? 'Menghubungkan hulu ke hilir dalam pangan sehat, plant-based, natural, dan shelf stable.';
$contactWhatsapp = preg_replace('/\D+/', '', (string) ($storeSettings['contact_whatsapp'] ?? '6282168576196')) ?: '6282168576196';
function catalogEscape(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Jamur Krispi 80g | Mushroom Organik — Belanja Online</title>

    <meta name="description"
          content="Belanja produk olahan jamur organik dengan estimasi ongkir RajaOngkir dan pembayaran QRIS.">

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
          rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <style>

        /* =====================================================
           DESIGN SYSTEM
        ===================================================== */

        :root {

            --primary: #2F6B3F;
            --primary-dark: #1F4D2E;
            --primary-light: #A8C3A0;

            --background: #F8F6ED;
            --background-green: #EAF3E8;

            --white: #FFFFFF;
            --text: #1F2D22;
            --text-light: #6B716C;

            --earth: #795548;
            --border: #E3E5DE;

            --radius-sm: 8px;
            --radius-md: 14px;
            --radius-lg: 22px;

            --shadow:
                0 10px 30px rgba(31, 77, 46, 0.08);

            --container: 1200px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: "Inter", sans-serif;
            background: var(--white);
            color: var(--text);
            line-height: 1.6;
        }

        img {
            max-width: 100%;
            display: block;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button {
            font-family: inherit;
            cursor: pointer;
        }

        .container {
            max-width: var(--container);
            margin: auto;
            padding: 0 24px;
        }


        /* =====================================================
           TOP BAR
        ===================================================== */

        .topbar {
            background: var(--primary-dark);
            color: white;
            font-size: 13px;
            padding: 8px 0;
        }

        .topbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar-left,
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }


        /* =====================================================
           NAVBAR
        ===================================================== */

        .navbar {
            background: white;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-content {
            height: 82px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 240px;
        }

        .logo-icon {
            width: 54px;
            height: 54px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 25px;
        }

        .logo-text strong {
            display: block;
            font-size: 20px;
            color: var(--primary-dark);
        }

        .logo-text span {
            font-size: 11px;
            color: var(--text-light);
        }

        .nav-menu {
            display: flex;
            gap: 36px;
            height: 100%;
            align-items: center;
        }

        .nav-menu a {
            font-size: 14px;
            font-weight: 600;
            position: relative;
            padding: 30px 0;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            color: var(--primary);
        }

        .nav-menu a:hover::after,
        .nav-menu a.active::after {
            content: "";
            position: absolute;
            bottom: 15px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary);
            border-radius: 3px;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .search {
            width: 225px;
            height: 44px;
            border: 1px solid var(--border);
            border-radius: 10px;
            display: flex;
            align-items: center;
            padding: 0 14px;
            gap: 10px;
        }

        .search input {
            border: none;
            outline: none;
            width: 100%;
            font-size: 13px;
        }

        .cart {
            width: 44px;
            height: 44px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: white;
            position: relative;
            color: var(--primary-dark);
        }

        .cart-count {
            position: absolute;
            top: -7px;
            right: -7px;
            background: var(--primary);
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }


        /* =====================================================
           HERO
        ===================================================== */

        .hero {
            background: var(--background);
            min-height: 470px;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        .hero-content {
            display: grid;
            grid-template-columns: 45% 55%;
            align-items: center;
        }

        .hero-text {
            padding: 60px 0;
        }

        .hero-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .hero h1 {
            font-size: clamp(40px, 4vw, 60px);
            line-height: 1.12;
            color: var(--primary-dark);
            margin-bottom: 20px;
            letter-spacing: -1.5px;
        }

        .hero h1 span {
            color: var(--primary);
        }

        .hero-description {
            color: var(--text-light);
            font-size: 16px;
            max-width: 520px;
            margin-bottom: 30px;
        }

        .hero-buttons {
            display: flex;
            gap: 14px;
        }

        .btn {
            padding: 13px 25px;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 700;
            border: none;
            transition: 0.25s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary-dark);
            border: 1px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--background-green);
        }

        .hero-image {
            min-height: 470px;
            background:
                linear-gradient(
                    90deg,
                    var(--background) 0%,
                    rgba(248,246,237,0) 20%
                ),
                url("assets/hero-mushroom.jpg") center/cover;
        }


        /* =====================================================
           BENEFITS
        ===================================================== */

        .benefits {
            background: var(--background-green);
            padding: 25px 0;
        }

        .benefit-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
        }

        .benefit {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 10px 25px;
            border-right: 1px solid #C9D6C7;
        }

        .benefit:last-child {
            border-right: none;
        }

        .benefit-icon {
            width: 50px;
            height: 50px;
            flex-shrink: 0;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .benefit h4 {
            font-size: 14px;
            margin-bottom: 3px;
        }

        .benefit p {
            font-size: 11px;
            color: var(--text-light);
        }


        /* =====================================================
           SECTION
        ===================================================== */

        .section {
            padding: 75px 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .section-header .small-title {
            color: var(--primary);
            font-size: 13px;
            font-weight: 700;
        }

        .section-header h2 {
            font-size: 32px;
            color: var(--primary-dark);
            margin: 5px 0 12px;
        }

        .section-header p {
            color: var(--text-light);
            font-size: 14px;
        }


        /* =====================================================
           PRODUCTS
        ===================================================== */

        .products {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
        }

        .product-card {
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            overflow: hidden;
            background: white;
            transition: 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .product-image {
            height: 360px;
            background: var(--background);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: var(--primary);
            color: white;
            padding: 5px 9px;
            border-radius: 5px;
            font-size: 10px;
            font-weight: 700;
        }

        .product-info {
            padding: 18px;
        }

        .product-description {
            color: var(--text-light);
            font-size: 12px;
            line-height: 1.55;
            margin: 10px 0 14px;
        }

        .product-highlights {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin: 12px 0 16px;
        }

        .product-highlights > div {
            display: flex;
            gap: 8px;
            align-items: flex-start;
            padding: 9px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #fbfcf8;
        }

        .product-highlights i {
            color: var(--primary);
            margin-top: 2px;
        }

        .product-highlights strong,
        .product-highlights small {
            display: block;
        }

        .product-highlights strong {
            font-size: 10px;
            color: var(--text);
        }

        .product-highlights small {
            margin-top: 2px;
            font-size: 9px;
            line-height: 1.35;
            color: var(--text-light);
        }

        .product-weight {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--background-green);
            color: var(--primary-dark);
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .selected-variant {
            margin: 14px 0 10px;
            padding: 10px 12px;
            border-radius: 10px;
            background: var(--background-green);
            color: var(--primary-dark);
            font-size: 12px;
            line-height: 1.45;
        }

        .selected-variant strong {
            font-weight: 800;
        }

        .variant-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 8px;
        }

        .variant-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }

        .variant {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 5px;
            background: white;
            transition: .2s;
            text-align: center;
        }

        .variant:hover,
        .variant.active {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(47,107,63,.08);
        }

        .variant img {
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
            border-radius: 6px;
        }

        .variant span {
            display: block;
            font-size: 9px;
            font-weight: 700;
            color: var(--text);
            margin-top: 4px;
            line-height: 1.2;
        }

        .product-card .product-image {
            background: #f3f1e8;
        }

        .product-card .product-info {
            min-height: 390px;
        }

        .product-category {
            color: var(--primary);
            font-size: 11px;
            font-weight: 600;
        }

        .product-name {
            font-size: 15px;
            font-weight: 700;
            margin: 5px 0 8px;
        }

        .product-price {
            color: var(--primary-dark);
            font-weight: 800;
            font-size: 18px;
        }

        .product-action {
            width: 100%;
            margin-top: 16px;
            padding: 12px 16px;
            border: none;
            border-radius: 9px;
            background: var(--primary);
            color: #fff;
            font-weight: 700;
            font-size: 13px;
        }

        .product-action:hover {
            background: var(--primary-dark);
        }

        .contact-admin {
            display: block;
            width: 100%;
            margin-top: 8px;
            padding: 10px 16px;
            border: 1px solid #25D366;
            border-radius: 9px;
            color: #168c43;
            text-align: center;
            font-size: 13px;
            font-weight: 700;
        }

        .contact-admin:hover { background: #e8fff0; }

        .variant-claim {
            display: inline-block;
            margin: 4px 0 7px;
            padding: 5px 9px;
            border-radius: 999px;
            background: #8b5e34;
            color: #fff;
            font-size: 10px;
            font-weight: 800;
        }

        .variant-claim.no-flour {
            background: #9b6a36;
        }

        @media (max-width: 1024px) {
            .products {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .products {
                grid-template-columns: 1fr;
            }
            .product-image {
                height: 430px;
            }
        }




        /* =====================================================
           TENTANG KAMI
        ===================================================== */

        .about-section {
            background: var(--background-green);
            padding: 85px 0;
            scroll-margin-top: 90px;
        }

        .about-intro {
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            gap: 55px;
            align-items: center;
            margin-bottom: 55px;
        }

        .about-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }

        .about-intro h2 {
            color: var(--primary-dark);
            font-size: clamp(30px, 4vw, 44px);
            line-height: 1.15;
            margin-bottom: 18px;
        }

        .about-intro p {
            color: var(--text-light);
            font-size: 15px;
            margin-bottom: 14px;
        }

        .about-badge {
            background: white;
            border-radius: var(--radius-lg);
            padding: 28px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(47,107,63,.10);
        }

        .about-badge .badge-icon {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: var(--background-green);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
            margin-bottom: 16px;
        }

        .about-badge h3 {
            color: var(--primary-dark);
            font-size: 21px;
            margin-bottom: 8px;
        }

        .about-badge p {
            font-size: 13px;
            margin: 0;
        }

        .about-values {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 55px;
        }

        .about-value {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 22px 18px;
        }

        .about-value i {
            color: var(--primary);
            font-size: 20px;
            margin-bottom: 12px;
        }

        .about-value h4 {
            color: var(--primary-dark);
            font-size: 15px;
            margin-bottom: 5px;
        }

        .about-value p {
            color: var(--text-light);
            font-size: 12px;
            margin: 0;
        }

        .about-gallery-head {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 20px;
            margin-bottom: 18px;
        }

        .about-gallery-head h3 {
            color: var(--primary-dark);
            font-size: 25px;
            margin-top: 4px;
        }

        .about-gallery-head p {
            color: var(--text-light);
            font-size: 13px;
            max-width: 520px;
        }

        .about-source-text {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
            margin-top: 24px;
        }
        .about-source-item {
            display: flex;
            gap: 16px;
            padding: 22px;
            background: #fff;
            border: 1px solid rgba(25, 91, 53, .10);
            border-radius: 18px;
            box-shadow: 0 8px 22px rgba(20, 60, 35, .06);
        }
        .about-source-item > i {
            width: 42px; height: 42px; min-width: 42px;
            display: grid; place-items: center;
            border-radius: 50%;
            background: #e9f3e8;
            color: #236b3d;
            font-size: 18px;
        }
        .about-source-item h4 { margin: 0 0 7px; color: #174f31; }
        .about-source-item p { margin: 0; line-height: 1.65; color: #5d6c63; }

        .about-gallery {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 55px;
        }

        .about-photo {
            background: white;
            border-radius: var(--radius-md);
            overflow: hidden;
            border: 1px solid var(--border);
            box-shadow: 0 5px 18px rgba(31,77,46,.05);
        }

        .about-photo img {
            width: 100%;
            aspect-ratio: 4 / 3;
            object-fit: cover;
        }

        .about-photo figcaption {
            padding: 10px 12px 12px;
            color: var(--text-light);
            font-size: 11px;
            line-height: 1.4;
        }

        .halal-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 28px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(47,107,63,.12);
        }

        .halal-info .about-kicker { margin-bottom: 8px; }
        .halal-info h3 {
            color: var(--primary-dark);
            font-size: 27px;
            margin-bottom: 18px;
        }

        .halal-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 8px;
        }

        .halal-meta div {
            background: var(--background-green);
            border-radius: 10px;
            padding: 14px 15px;
        }

        .halal-meta small {
            display: block;
            color: var(--text-light);
            font-size: 10px;
            margin-bottom: 4px;
        }

        .halal-meta strong {
            color: var(--primary-dark);
            font-size: 13px;
        }

        @media (max-width: 1000px) {
            .about-intro, .halal-card { grid-template-columns: 1fr; }
            .about-values { grid-template-columns: repeat(2, 1fr); }
            .about-gallery { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 600px) {
            .about-section { padding: 60px 0; }
            .about-values, .about-gallery, .halal-meta { grid-template-columns: 1fr; }
            .about-source-text { grid-template-columns: 1fr; }
            .about-gallery-head { display: block; }
            .about-gallery-head p { margin-top: 8px; }
            .halal-card { padding: 18px; }
        }


        /* =====================================================
           FARM STORY
        ===================================================== */

        .farm-story {
            background: var(--background);
        }

        .farm-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 70px;
            align-items: center;
        }

        .farm-image {
            height: 420px;
            border-radius: var(--radius-lg);
            background:
                url("assets/hero-mushroom.jpg") center/cover;
        }

        .farm-text .small-title {
            color: var(--primary);
            font-weight: 700;
            font-size: 13px;
        }

        .farm-text h2 {
            color: var(--primary-dark);
            font-size: 35px;
            line-height: 1.2;
            margin: 10px 0 20px;
        }

        .farm-text p {
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 18px;
        }

        .process {
            margin-top: 25px;
        }

        .process-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .process-number {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
        }


        /* =====================================================
           CTA
        ===================================================== */

        .cta {
            background: var(--primary-dark);
            color: white;
            text-align: center;
            padding: 70px 20px;
        }

        .cta h2 {
            font-size: 35px;
            margin-bottom: 12px;
        }

        .cta p {
            color: #D6E3D8;
            margin-bottom: 25px;
        }

        .cta .btn {
            background: white;
            color: var(--primary-dark);
        }


        /* =====================================================
           FOOTER
        ===================================================== */

        footer {
            background: #183522;
            color: white;
            padding: 55px 0 25px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
        }

        footer h3 {
            font-size: 16px;
            margin-bottom: 18px;
        }

        footer p,
        footer a {
            font-size: 13px;
            color: #C7D3C9;
        }

        footer a {
            display: block;
            margin-bottom: 9px;
        }

        footer a:hover {
            color: white;
        }

        .copyright {
            border-top: 1px solid rgba(255,255,255,.12);
            margin-top: 40px;
            padding-top: 20px;
            text-align: center;
            color: #AAB8AC;
            font-size: 11px;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1000px) {

            .nav-menu {
                display: none;
            }

            .hero-content {
                grid-template-columns: 1fr;
            }

            .hero-image {
                min-height: 350px;
            }

            .benefit-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .benefit {
                border-bottom: 1px solid #C9D6C7;
            }

            .products {
                grid-template-columns: repeat(2, 1fr);
            }

            .farm-grid {
                grid-template-columns: 1fr;
            }

            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {

            .topbar {
                display: none;
            }

            .logo {
                min-width: auto;
            }

            .logo-text {
                display: none;
            }

            .search {
                display: none;
            }

            .hero h1 {
                font-size: 38px;
            }

            .benefit-grid {
                grid-template-columns: 1fr;
            }

            .benefit {
                border-right: none;
            }

            .products {
                grid-template-columns: 1fr;
            }

            .footer-grid {
                grid-template-columns: 1fr;
            }
        }

    
        .variant-claim {
            display: inline-block;
            margin-top: 8px;
            padding: 7px 14px;
            border-radius: 999px;
            background: #8b5e34;
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .8px;
        }

        .variant-claim.no-flour {
            background: #3f7f2f;
        }

        .product-composition {
            margin-top: 14px;
            padding: 12px 14px;
            border-left: 3px solid #5d8d3a;
            background: rgba(93, 141, 58, .08);
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.6;
        }


        /* =====================================================
           E-COMMERCE FLOW
        ====================================================== */
        body.modal-open { overflow: hidden; }
        .cart-count.has-items { box-shadow: 0 0 0 3px rgba(47,107,63,.12); }
        .modal-backdrop { position:fixed; inset:0; background:rgba(17,31,22,.58); backdrop-filter:blur(3px); z-index:3000; display:none; align-items:center; justify-content:center; padding:24px; }
        .modal-backdrop.show { display:flex; }
        .commerce-modal { display:none; width:min(940px,100%); max-height:min(90vh,900px); overflow:auto; background:#fff; border-radius:20px; box-shadow:0 25px 80px rgba(0,0,0,.22); padding:28px; position:relative; }
        .commerce-modal.show { display:block; animation:modalIn .2s ease-out; }
        @keyframes modalIn { from{opacity:0;transform:translateY(12px) scale(.99)} to{opacity:1;transform:none} }
        .modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:20px; margin-bottom:20px; }
        .modal-head h2 { color:var(--primary-dark); font-size:28px; margin-top:3px; }
        .eyebrow { display:inline-block; color:var(--primary); font-size:11px; font-weight:800; letter-spacing:1.2px; }
        .modal-close { width:38px; height:38px; border:1px solid var(--border); border-radius:10px; background:#fff; color:var(--text-light); }
        .modal-close:hover { background:var(--background-green); color:var(--primary-dark); }
        .cart-list { display:grid; gap:10px; }
        .cart-row { display:grid; grid-template-columns:72px 1fr auto auto; align-items:center; gap:14px; padding:12px; border:1px solid var(--border); border-radius:12px; }
        .cart-row img { width:72px; height:72px; object-fit:contain; background:#f5f3ea; border-radius:9px; }
        .cart-item-main strong,.cart-item-main small,.cart-item-main span { display:block; }
        .cart-item-main strong { font-size:14px; color:var(--text); }
        .cart-item-main small { font-size:11px; color:var(--text-light); margin:3px 0; }
        .cart-item-main span { font-weight:800; color:var(--primary-dark); font-size:13px; }
        .qty-control { display:flex; align-items:center; border:1px solid var(--border); border-radius:8px; overflow:hidden; }
        .qty-control button { width:30px;height:30px;border:0;background:#f7f9f5;color:var(--primary-dark);font-size:18px; }
        .qty-control b { width:30px;text-align:center;font-size:12px; }
        .remove-item { border:0;background:transparent;color:#a65b50;padding:8px; }
        .cart-total { margin-top:18px; padding:16px; background:var(--background-green); border-radius:12px; }
        .cart-total div,.checkout-summary>div { display:flex; justify-content:space-between; gap:20px; align-items:center; }
        .cart-total strong,.checkout-summary .grand strong { color:var(--primary-dark); }
        .cart-total small,.checkout-summary small { display:block; color:var(--text-light); font-size:11px; margin-top:5px; }
        .cart-empty { text-align:center; padding:45px 20px 30px; }
        .cart-empty-icon { width:70px;height:70px;border-radius:50%;background:var(--background-green);color:var(--primary);display:flex;align-items:center;justify-content:center;margin:0 auto 15px;font-size:26px; }
        .cart-empty h3 { color:var(--primary-dark); font-size:21px; }
        .cart-empty p { color:var(--text-light); font-size:13px; max-width:430px; margin:7px auto 20px; }
        .checkout-modal { width:min(1000px,100%); }
        .checkout-steps { display:flex; gap:10px; align-items:center; font-size:11px; color:#9aa09b; margin:-5px 0 22px; }
        .checkout-steps .step.active { color:var(--primary); font-weight:800; }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .form-grid label { font-size:12px; font-weight:700; color:var(--text); }
        .form-grid .full { grid-column:1/-1; }
        .form-grid input,.form-grid select,.form-grid textarea { width:100%; margin-top:6px; border:1px solid var(--border); border-radius:9px; padding:11px 12px; font:inherit; font-size:13px; outline:none; background:#fff; color:var(--text); }
        .form-grid input:focus,.form-grid select:focus,.form-grid textarea:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(47,107,63,.08); }
        .shipping-box { margin-top:20px; padding:16px; border:1px solid var(--border); border-radius:14px; background:#fbfcf8; }
        .shipping-box-head { display:flex; justify-content:space-between; align-items:center; gap:20px; }
        .shipping-box-head strong,.shipping-box-head small { display:block; }
        .shipping-box-head strong { font-size:14px; color:var(--primary-dark); }
        .shipping-box-head small { color:var(--text-light); font-size:10px; margin-top:2px; }
        .btn-small { padding:9px 13px; font-size:11px; white-space:nowrap; }
        .shipping-options { margin-top:12px; display:grid; gap:8px; }
        #shippingMap { height:260px; margin-top:10px; border:1px solid var(--border); border-radius:10px; z-index:1; }
        .map-results { display:grid; gap:6px; margin-top:8px; }
        .map-result { display:block; width:100%; padding:9px 10px; border:1px solid var(--border); background:#fff; text-align:left; border-radius:8px; color:var(--text); }
        .map-result:hover { border-color:var(--primary); background:var(--background-green); }
        .shipping-option { display:grid; grid-template-columns:22px 1fr auto; align-items:center; gap:8px; padding:11px 12px; border:1px solid var(--border); border-radius:10px; background:#fff; cursor:pointer; }
        .shipping-option.selected { border-color:var(--primary); box-shadow:0 0 0 2px rgba(47,107,63,.07); }
        .shipping-option span b,.shipping-option span small { display:block; }
        .shipping-option span b { font-size:12px; }
        .shipping-option span small { font-size:10px; color:var(--text-light); }
        .shipping-option strong { color:var(--primary-dark); font-size:12px; }
        .empty-shipping { color:var(--text-light); font-size:11px; padding:8px 2px; }
        .checkout-summary { margin-top:16px; padding:15px; border-radius:12px; background:var(--background-green); }
        .checkout-summary>div { padding:4px 0; font-size:12px; }
        .checkout-summary .grand { margin-top:7px; padding-top:10px; border-top:1px solid rgba(47,107,63,.15); font-size:15px; }
        .modal-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:22px; }
        .payment-layout { display:grid; grid-template-columns:380px 1fr; gap:28px; align-items:start; }
        .qris-panel { text-align:center; border:1px solid var(--border); border-radius:16px; padding:16px; background:#fafaf7; }
        .qris-panel img { width:min(330px,100%); margin:12px auto; border-radius:8px; }
        .qris-label { font-weight:800; color:var(--primary-dark); font-size:14px; }
        .qris-note { color:var(--text-light); font-size:10px; line-height:1.55; }
        .order-code,.pay-total { padding:14px; border:1px solid var(--border); border-radius:12px; margin-bottom:10px; }
        .order-code span,.pay-total span { display:block; font-size:10px; color:var(--text-light); }
        .order-code strong { display:block; margin-top:4px; color:var(--primary-dark); letter-spacing:.8px; }
        .pay-total { position:relative; background:var(--background-green); }
        .pay-total strong { display:block; color:var(--primary-dark); font-size:25px; margin:3px 0; }
        .copy-btn { border:0;background:transparent;color:var(--primary);font-size:11px;font-weight:700;padding:0; }
        .payment-steps { display:grid; gap:9px; margin:14px 0; }
        .payment-steps div { display:flex; align-items:center; gap:10px; font-size:12px; }
        .payment-steps b { width:25px;height:25px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px; }
        .proof-upload { display:block; padding:12px; border:1px dashed #b9c5b9; border-radius:10px; font-size:11px; font-weight:700; }
        .proof-upload input { display:block; margin-top:7px; width:100%; font-size:11px; }
        .proof-upload small { display:block; color:var(--text-light); margin-top:5px; font-weight:400; }
        .payment-warning { display:flex; gap:9px; padding:11px; margin-top:10px; border-radius:10px; background:#fff7df; color:#705b20; font-size:10px; line-height:1.45; }
        .success-modal { width:min(560px,100%); text-align:center; padding:40px 32px; }
        .success-icon { width:70px;height:70px;border-radius:50%;background:var(--background-green);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:28px;margin:0 auto 18px; }
        .success-modal h2 { color:var(--primary-dark); font-size:25px; margin:5px auto 10px; }
        .success-modal>p { color:var(--text-light); font-size:12px; max-width:440px; margin:0 auto; }
        .success-status { display:flex; align-items:center; text-align:left; gap:12px; background:#fff7df; color:#705b20; padding:13px; border-radius:11px; margin-top:18px; }
        .success-status i { font-size:20px; }
        .success-status b,.success-status small { display:block; }
        .success-status b { font-size:12px; }
        .success-status small { font-size:10px; margin-top:2px; }
        .success-actions { display:flex; justify-content:center; gap:10px; margin-top:22px; }
        @media(max-width:760px){
            .form-grid,.payment-layout { grid-template-columns:1fr; }
            .form-grid .full { grid-column:auto; }
            .cart-row { grid-template-columns:58px 1fr auto; }
            .cart-row img { width:58px;height:58px; }
            .remove-item { grid-column:3; grid-row:1; }
            .qty-control { grid-column:2; justify-self:start; }
            .cart-item-main { grid-column:2/4; }
            .shipping-box-head { align-items:flex-start; flex-direction:column; }
            .modal-actions,.success-actions { flex-wrap:wrap; }
            .modal-actions .btn,.success-actions .btn { flex:1; min-width:170px; }
        }

</style>
</head>


<body>

    <!-- =====================================================
         TOP BAR
    ====================================================== -->

    <div class="topbar">

        <div class="container topbar-content">

            <div class="topbar-left">
                <span>
                    <i class="fa-solid fa-leaf"></i>
                    Produk sehat dari jamur organik hasil budidaya sendiri
                </span>
            </div>

            <div class="topbar-right">
                <a href="https://wa.me/6282168576196?text=Halo%20Bos%20Global%2C%20saya%20ingin%20bertanya%20tentang%20produk%20Mushroom%20Organik." target="_blank" rel="noopener">
                    <i class="fa-brands fa-whatsapp"></i>
                    WhatsApp
                </a>

                <span>
                    <i class="fa-regular fa-user"></i>
                    Akun Saya
                </span>
            </div>

        </div>

    </div>


    <!-- =====================================================
         NAVBAR
    ====================================================== -->

    <nav class="navbar">

        <div class="container nav-content">

            <a href="#" class="logo">

                <div class="logo-icon">
                    <i class="fa-solid fa-seedling"></i>
                </div>

                <div class="logo-text">
                    <strong>Mushroom Organik</strong>
                    <span>Sehat Alami, Hidup Berkualitas</span>
                </div>

            </a>


            <div class="nav-menu">

                <a href="#" data-section="top">
                    Beranda
                </a>

                <a href="#produk" data-section="produk">
                    Produk
                </a>

                <a href="#tentang" data-section="tentang">
                    Tentang Kami
                </a>

                <a href="#budidaya" data-section="budidaya">
                    Budidaya
                </a>

                <a href="#kontak" data-section="kontak">
                    Kontak
                </a>

            </div>


            <div class="nav-actions">

                <div class="search">

                    <input
                        type="text"
                        placeholder="Cari produk..."
                        id="searchInput">

                    <i class="fa-solid fa-magnifying-glass"></i>

                </div>

                <a href="<?= $user ? ($user['role'] === 'admin' ? 'admin/index.php' : 'user/index.php') : 'auth.php' ?>" class="account-link">
                    <i class="fa-solid fa-user"></i>
                    <?= htmlspecialchars($user['name'] ?? 'Masuk', ENT_QUOTES, 'UTF-8') ?>
                </a>
                <?php if ($user && $user['role'] === 'customer'): ?>
                    <a href="user/index.php" class="account-link">
                        <i class="fa-solid fa-receipt"></i>
                        Pesanan Saya
                    </a>
                <?php endif; ?>
                <?php if ($user): ?>
                    <a href="logout.php" class="account-link">Keluar</a>
                <?php endif; ?>

                <button class="cart" id="cartButton">

                    <i class="fa-solid fa-cart-shopping"></i>

                    <span class="cart-count" id="cartCount">
                        0
                    </span>

                </button>

            </div>

        </div>

    </nav>


    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="hero">

        <div class="container hero-content">

            <div class="hero-text">

                <div class="hero-label">

                    <i class="fa-solid fa-leaf"></i>

                    100% Natural & Organic

                </div>


                <h1>

                    Dari Jamur Organik,

                    <span>
                        Menjadi Produk Sehat
                    </span>

                    untuk Keluarga

                </h1>


                <p class="hero-description">

                    Jamur pilihan hasil budidaya sendiri,
                    diolah secara higienis tanpa bahan
                    pengawet, untuk kesehatan Anda dan keluarga.

                </p>


                <div class="hero-buttons">

                    <a href="#produk"
                       class="btn btn-primary">

                        Jelajahi Produk

                        <i class="fa-solid fa-arrow-right"></i>

                    </a>


                    <a href="#tentang"
                       class="btn btn-outline">

                        Cerita Kami

                        <i class="fa-solid fa-leaf"></i>

                    </a>

                </div>

            </div>


            <div class="hero-image"></div>

        </div>

    </section>


    <!-- =====================================================
         BENEFITS
    ====================================================== -->

    <section class="benefits">

        <div class="container benefit-grid">


            <div class="benefit">

                <div class="benefit-icon">

                    <i class="fa-solid fa-mushroom"></i>

                </div>

                <div>

                    <h4>100% Organik</h4>

                    <p>
                        Dibudidayakan tanpa bahan kimia
                        dan pestisida berbahaya.
                    </p>

                </div>

            </div>


            <div class="benefit">

                <div class="benefit-icon">

                    <i class="fa-solid fa-seedling"></i>

                </div>

                <div>

                    <h4>Dibudidayakan Sendiri</h4>

                    <p>
                        Dari bibit hingga panen,
                        kami lakukan dengan penuh perhatian.
                    </p>

                </div>

            </div>


            <div class="benefit">

                <div class="benefit-icon">

                    <i class="fa-solid fa-heart"></i>

                </div>

                <div>

                    <h4>Sehat & Bergizi</h4>

                    <p>
                        Produk berbahan jamur pilihan
                        untuk keluarga.
                    </p>

                </div>

            </div>


            <div class="benefit">

                <div class="benefit-icon">

                    <i class="fa-solid fa-truck"></i>

                </div>

                <div>

                    <h4>Pengiriman Aman</h4>

                    <p>
                        Dikemas dengan baik dan dikirim
                        dengan cepat.
                    </p>

                </div>

            </div>


        </div>

    </section>


    <!-- =====================================================
         PRODUCT SECTION
    ====================================================== -->

    <section class="section" id="produk">

        <div class="container">

            <div class="section-header">

                <div class="small-title">
                    PILIHAN TERBAIK KAMI
                </div>

                <h2>
                    Produk Unggulan
                </h2>

                <p>
                    Produk olahan jamur sehat yang
                    dibuat dari hasil budidaya sendiri.
                </p>

            </div>


            <div class="products">
                <?php foreach ($catalogProducts as $product): ?>
                <article class="product-card" data-product-id="<?= catalogEscape($product['id']) ?>" data-product-name="<?= catalogEscape($product['name']) ?>" data-price="<?= (int) $product['price'] ?>" data-image="<?= catalogEscape($product['image']) ?>"><div class="product-image"><?php if ($product['badge']): ?><span class="badge"><?= catalogEscape($product['badge']) ?></span><?php endif; ?><img src="<?= catalogEscape($product['image']) ?>" alt="<?= catalogEscape($product['name']) ?> <?= (int) $product['weight_gram'] ?> gram"></div>
                    <div class="product-info">
                        <div class="product-category"><?= catalogEscape($product['category']) ?></div>
                        <div class="product-name"><?= catalogEscape($product['name']) ?></div>
                        <?php if ($product['claim']): ?><div class="variant-claim <?= stripos((string) $product['claim'], 'tanpa') !== false ? 'no-flour' : '' ?>"><?= catalogEscape($product['claim']) ?></div><?php endif; ?>
                        <div class="product-weight"><i class="fa-solid fa-weight-hanging"></i> Berat bersih <?= (int) $product['weight_gram'] ?> gram</div>
                        <?php if ($product['description']): ?><p class="product-description"><?= nl2br(catalogEscape($product['description'])) ?></p><?php endif; ?>
                        <?php if ($product['composition']): ?><div class="product-composition"><strong>Komposisi:</strong> <?= catalogEscape($product['composition']) ?></div><?php endif; ?>
                        <?php if ($product['highlight_one_text'] || $product['highlight_two_text']): ?><div class="product-highlights"><?php if ($product['highlight_one_text']): ?><div><i class="fa-solid fa-seedling"></i><span><strong>Tambahan deskripsi 1</strong><small><?= catalogEscape($product['highlight_one_text']) ?></small></span></div><?php endif; ?><?php if ($product['highlight_two_text']): ?><div><i class="fa-solid fa-bowl-food"></i><span><strong>Tambahan deskripsi 2</strong><small><?= catalogEscape($product['highlight_two_text']) ?></small></span></div><?php endif; ?></div><?php endif; ?>
                        <div class="product-price">Rp<?= number_format((int) $product['price'], 0, ',', '.') ?></div>
                        <button class="product-action" type="button" data-product="<?= catalogEscape($product['name']) ?>">Tambah ke Keranjang</button><a class="contact-admin" target="_blank" rel="noopener" href="https://wa.me/<?= catalogEscape($contactWhatsapp) ?>?text=<?= rawurlencode('Halo Admin, saya ingin bertanya tentang ' . $product['name']) ?>"><i class="fa-brands fa-whatsapp"></i> Hubungi Admin</a>
                    </div>
                </article>
                <?php endforeach; ?>
                <?php if (false): ?>

                <article class="product-card" data-product-id="jamur-krispi-original-natural" data-product-name="Jamur Krispi – Original Natural" data-price="25000" data-image="assets/images/products/jamur-krispi-original-natural.png"><div class="product-image"><span class="badge">BEST SELLER</span><img src="assets/images/products/jamur-krispi-original-natural.png" alt="Jamur Krispi Original Natural 80 gram"></div>
                    <div class="product-info">
                        <div class="product-category">SNACK JAMUR ORGANIK</div>
                        <div class="product-name">Jamur Krispi – Original Natural</div>
                        <div class="variant-claim no-flour">TANPA TEPUNG</div>
                        <div class="product-weight"><i class="fa-solid fa-weight-hanging"></i> Berat bersih 80 gram</div>
                        <p class="product-description"><strong>Jamur tiram pilihan, dibalut bumbu istimewa</strong> dengan rasa original (natural) yang gurih alami dan tekstur super crunchy.</p>
                        <div class="product-composition"><strong>Komposisi:</strong> jamur tiram, bawang putih, garam, lada, minyak nabati, bubuk perasa.</div>
                        <div class="product-highlights"><div><i class="fa-solid fa-seedling"></i><span><strong>100% jamur tiram asli</strong><small>Jamur tiram pilihan berkualitas.</small></span></div><div><i class="fa-solid fa-bowl-food"></i><span><strong>Super crunchy</strong><small>Digoreng hingga super renyah.</small></span></div></div>
                        <div class="product-price">Rp25.000</div>
                        <button class="product-action" type="button" data-product="Jamur Krispi – Original Natural">Tambah ke Keranjang</button>
                    </div>
                </article>

                <article class="product-card" data-product-id="jamur-krispi-seaweed" data-product-name="Jamur Krispi – Seaweed" data-price="25000" data-image="assets/images/products/jamur-krispi-seaweed.png"><div class="product-image"><img src="assets/images/products/jamur-krispi-seaweed.png" alt="Jamur Krispi Seaweed 80 gram"></div>
                    <div class="product-info">
                        <div class="product-category">SNACK JAMUR ORGANIK</div><div class="product-name">Jamur Krispi – Seaweed</div><div class="variant-claim no-flour">TANPA TEPUNG</div><div class="product-weight"><i class="fa-solid fa-weight-hanging"></i> Berat bersih 80 gram</div>
                        <p class="product-description"><strong>Jamur tiram pilihan, dibalut bumbu istimewa</strong> dengan perpaduan gurih jamur dan rasa seaweed yang lezat, renyah, dan bikin ketagihan.</p>
                        <div class="product-composition"><strong>Komposisi:</strong> jamur tiram, bawang putih, garam, lada, minyak nabati, bubuk perasa.</div>
                        <div class="product-highlights"><div><i class="fa-solid fa-seedling"></i><span><strong>100% jamur tiram asli</strong><small>Jamur tiram pilihan berkualitas.</small></span></div><div><i class="fa-solid fa-leaf"></i><span><strong>Rasa seaweed</strong><small>Gurih dan lezat.</small></span></div></div>
                        <div class="product-price">Rp25.000</div><button class="product-action" type="button" data-product="Jamur Krispi – Seaweed">Tambah ke Keranjang</button>
                    </div>
                </article>

                <article class="product-card" data-product-id="jamur-krispi-cheese" data-product-name="Jamur Krispi – Cheese" data-price="25000" data-image="assets/images/products/jamur-krispi-cheese.png"><div class="product-image"><img src="assets/images/products/jamur-krispi-cheese.png" alt="Jamur Krispi Cheese 80 gram"></div>
                    <div class="product-info">
                        <div class="product-category">SNACK JAMUR ORGANIK</div><div class="product-name">Jamur Krispi – Cheese</div><div class="variant-claim no-flour">TANPA TEPUNG</div><div class="product-weight"><i class="fa-solid fa-weight-hanging"></i> Berat bersih 80 gram</div>
                        <p class="product-description"><strong>Jamur tiram pilihan, dibalut bumbu istimewa</strong> dengan cita rasa cheese yang gurih dan nikmat dalam tekstur super crunchy.</p>
                        <div class="product-composition"><strong>Komposisi:</strong> jamur tiram, bawang putih, garam, lada, minyak nabati, bubuk perasa.</div>
                        <div class="product-highlights"><div><i class="fa-solid fa-seedling"></i><span><strong>100% jamur tiram asli</strong><small>Jamur tiram pilihan berkualitas.</small></span></div><div><i class="fa-solid fa-cheese"></i><span><strong>Rasa cheese</strong><small>Gurih dan nikmat.</small></span></div></div>
                        <div class="product-price">Rp25.000</div><button class="product-action" type="button" data-product="Jamur Krispi – Cheese">Tambah ke Keranjang</button>
                    </div>
                </article>

                <article class="product-card" data-product-id="jamur-krispi-balado" data-product-name="Jamur Krispi – Balado" data-price="25000" data-image="assets/images/products/jamur-krispi-balado.png"><div class="product-image"><img src="assets/images/products/jamur-krispi-balado.png" alt="Jamur Krispi Balado 80 gram"></div>
                    <div class="product-info">
                        <div class="product-category">SNACK JAMUR ORGANIK</div><div class="product-name">Jamur Krispi – Balado</div><div class="variant-claim">PREMIUM SNACK</div><div class="product-weight"><i class="fa-solid fa-weight-hanging"></i> Berat bersih 80 gram</div>
                        <p class="product-description"><strong>Jamur tiram pilihan, dibalut bumbu istimewa</strong> dengan bumbu balado premium, menghadirkan perpaduan gurih dan pedas khas Nusantara.</p>
                        <div class="product-composition"><strong>Komposisi:</strong> jamur tiram, tepung, bawang putih, garam, lada, minyak nabati, bubuk perasa.</div>
                        <div class="product-highlights"><div><i class="fa-solid fa-pepper-hot"></i><span><strong>Balado premium</strong><small>Gurih dan pedas nikmat.</small></span></div><div><i class="fa-solid fa-bowl-food"></i><span><strong>Super crunchy</strong><small>Tekstur renyah.</small></span></div></div>
                        <div class="product-price">Rp25.000</div><button class="product-action" type="button" data-product="Jamur Krispi – Balado">Tambah ke Keranjang</button>
                    </div>
                </article>

                <article class="product-card" data-product-id="jamur-krispi-pizza" data-product-name="Jamur Krispi – Pizza" data-price="25000" data-image="assets/images/products/jamur-krispi-pizza.png"><div class="product-image"><img src="assets/images/products/jamur-krispi-pizza.png" alt="Jamur Krispi Pizza 80 gram"></div>
                    <div class="product-info">
                        <div class="product-category">SNACK JAMUR ORGANIK</div><div class="product-name">Jamur Krispi – Pizza</div><div class="variant-claim">PREMIUM SNACK</div><div class="product-weight"><i class="fa-solid fa-weight-hanging"></i> Berat bersih 80 gram</div>
                        <p class="product-description"><strong>Jamur tiram pilihan, dibalut bumbu istimewa</strong> dengan perpaduan bumbu pizza yang gurih dan lezat, cocok untuk camilan yang bikin ketagihan.</p>
                        <div class="product-composition"><strong>Komposisi:</strong> jamur tiram, tepung, bawang putih, garam, lada, minyak nabati, bubuk perasa.</div>
                        <div class="product-highlights"><div><i class="fa-solid fa-pizza-slice"></i><span><strong>Bumbu pizza</strong><small>Gurih dan lezat.</small></span></div><div><i class="fa-solid fa-bowl-food"></i><span><strong>Super crunchy</strong><small>Tekstur renyah.</small></span></div></div>
                        <div class="product-price">Rp25.000</div><button class="product-action" type="button" data-product="Jamur Krispi – Pizza">Tambah ke Keranjang</button>
                    </div>
                </article>

                <article class="product-card" data-product-id="jamur-krispi-original" data-product-name="Jamur Krispi – Original" data-price="25000" data-image="assets/images/products/jamur-krispi-original.png"><div class="product-image"><img src="assets/images/products/jamur-krispi-original.png" alt="Jamur Krispi Original 80 gram"></div>
                    <div class="product-info">
                        <div class="product-category">SNACK JAMUR ORGANIK</div><div class="product-name">Jamur Krispi – Original</div><div class="variant-claim">PREMIUM SNACK</div><div class="product-weight"><i class="fa-solid fa-weight-hanging"></i> Berat bersih 80 gram</div>
                        <p class="product-description"><strong>Jamur tiram pilihan, dibalut bumbu istimewa</strong> dengan rasa original yang gurih alami, cocok untuk semua usia.</p>
                        <div class="product-composition"><strong>Komposisi:</strong> jamur tiram, tepung, bawang putih, garam, lada, minyak nabati, bubuk perasa.</div>
                        <div class="product-highlights"><div><i class="fa-solid fa-seedling"></i><span><strong>Rasa original</strong><small>Gurih alami.</small></span></div><div><i class="fa-solid fa-bowl-food"></i><span><strong>Super crunchy</strong><small>Tekstur renyah.</small></span></div></div>
                        <div class="product-price">Rp25.000</div><button class="product-action" type="button" data-product="Jamur Krispi – Original">Tambah ke Keranjang</button>
                    </div>
                </article>

                <article class="product-card" data-product-id="jamur-krispi-mie-goreng" data-product-name="Jamur Krispi – Mie Goreng" data-price="25000" data-image="assets/images/products/jamur-krispi-mie-goreng.png"><div class="product-image"><img src="assets/images/products/jamur-krispi-mie-goreng.png" alt="Jamur Krispi Mie Goreng 80 gram"></div>
                    <div class="product-info">
                        <div class="product-category">SNACK JAMUR ORGANIK</div><div class="product-name">Jamur Krispi – Mie Goreng</div><div class="variant-claim">PREMIUM SNACK</div><div class="product-weight"><i class="fa-solid fa-weight-hanging"></i> Berat bersih 80 gram</div>
                        <p class="product-description"><strong>Jamur tiram pilihan, dibalut bumbu istimewa</strong> dengan perpaduan bumbu mie goreng yang gurih dan lezat, menghadirkan camilan renyah yang bikin nagih.</p>
                        <div class="product-composition"><strong>Komposisi:</strong> jamur tiram, tepung, bawang putih, garam, lada, minyak nabati, bubuk perasa.</div>
                        <div class="product-highlights"><div><i class="fa-solid fa-bowl-food"></i><span><strong>Bumbu mie goreng</strong><small>Gurih dan lezat.</small></span></div><div><i class="fa-solid fa-bolt"></i><span><strong>Super crunchy</strong><small>Renyah dan nagih.</small></span></div></div>
                        <div class="product-price">Rp25.000</div><button class="product-action" type="button" data-product="Jamur Krispi – Mie Goreng">Tambah ke Keranjang</button>
                    </div>
                </article>

                <article class="product-card" data-product-id="jamur-krispi-seaweed-spicy" data-product-name="Jamur Krispi – Seaweed Spicy" data-price="25000" data-image="assets/images/products/jamur-krispi-seaweed-spicy.png"><div class="product-image"><img src="assets/images/products/jamur-krispi-seaweed-spicy.png" alt="Jamur Krispi Seaweed Spicy 80 gram"></div>
                    <div class="product-info">
                        <div class="product-category">SNACK JAMUR ORGANIK</div><div class="product-name">Jamur Krispi – Seaweed Spicy</div><div class="variant-claim no-flour">TANPA TEPUNG</div><div class="product-weight"><i class="fa-solid fa-weight-hanging"></i> Berat bersih 80 gram</div>
                        <p class="product-description"><strong>Jamur tiram pilihan, dibalut bumbu istimewa</strong> dengan perpaduan rasa seaweed yang gurih dan sentuhan pedas yang nikmat.</p>
                        <div class="product-composition"><strong>Komposisi:</strong> jamur tiram, bawang putih, garam, lada, minyak nabati, bubuk perasa.</div>
                        <div class="product-highlights"><div><i class="fa-solid fa-pepper-hot"></i><span><strong>Seaweed pedas</strong><small>Gurih dan nikmat.</small></span></div><div><i class="fa-solid fa-bowl-food"></i><span><strong>Super crunchy</strong><small>Tekstur renyah.</small></span></div></div>
                        <div class="product-price">Rp25.000</div><button class="product-action" type="button" data-product="Jamur Krispi – Seaweed Spicy">Tambah ke Keranjang</button>
                    </div>
                </article>

                <?php endif; ?>
            </div>

        </div>

    </section>



    <!-- =====================================================
         CART / CHECKOUT / PAYMENT UI
    ====================================================== -->
    <div class="modal-backdrop" id="modalBackdrop" aria-hidden="true">
        <div class="commerce-modal" id="cartModal" role="dialog" aria-modal="true" aria-labelledby="cartTitle">
            <div class="modal-head">
                <div>
                    <span class="eyebrow">BELANJA</span>
                    <h2 id="cartTitle">Keranjang Belanja</h2>
                </div>
                <button class="modal-close" data-close-modal aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div id="cartContent"></div>
        </div>

        <div class="commerce-modal checkout-modal" id="checkoutModal" role="dialog" aria-modal="true" aria-labelledby="checkoutTitle">
            <div class="modal-head">
                <div>
                    <span class="eyebrow">CHECKOUT</span>
                    <h2 id="checkoutTitle">Alamat & Pengiriman</h2>
                </div>
                <button class="modal-close" data-close-modal aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="checkout-steps">
                <span class="step active">1. Alamat</span><span>→</span><span class="step">2. Ongkir</span><span>→</span><span class="step">3. QRIS</span>
            </div>
            <form id="checkoutForm" novalidate>
                <div class="form-grid">
                    <label>Nama penerima<input id="customerName" required placeholder="Nama lengkap"></label>
                    <label>No. WhatsApp<input id="customerPhone" required inputmode="tel" placeholder="08xxxxxxxxxx"></label>
                    <label>Provinsi<select id="province" required>
                        <option value="">Pilih provinsi</option>
                        <option>Sumatera Utara</option><option>Sumatera Barat</option><option>Riau</option><option>Sumatera Selatan</option><option>Jambi</option><option>Bengkulu</option><option>Lampung</option>
                        <option>DKI Jakarta</option><option>Jawa Barat</option><option>Jawa Tengah</option><option>DI Yogyakarta</option><option>Jawa Timur</option><option>Banten</option>
                        <option>Bali</option><option>Kalimantan Barat</option><option>Kalimantan Timur</option><option>Kalimantan Selatan</option><option>Sulawesi Selatan</option><option>Sulawesi Utara</option><option>Maluku</option><option>NTT</option><option>Papua</option>
                    </select></label>
                    <label>Mode pengiriman<select id="shippingMode" required><option value="reguler">Kurir Reguler (JNE / J&amp;T / POS)</option><option value="instant">Kurir Instant (GoSend / SPX Instant)</option></select></label>
                    <label class="full">Cari alamat tujuan<div style="display:flex;gap:8px;margin-top:6px"><input id="citySearch" required autocomplete="off" placeholder="Contoh: Jalan ..., Medan"><button type="button" class="btn btn-small" id="findLocations">Cari di peta</button></div><div id="mapResults" class="map-results"></div><div id="shippingMap"></div><small>Pilih hasil alamat atau klik peta untuk menaruh marker. Alamat marker akan dicocokkan otomatis ke RajaOngkir.</small></label>
                    <label>Kecamatan tujuan<select id="city" required><option value="">Pilih alamat di peta terlebih dahulu</option></select></label>
                    <input type="hidden" id="destinationLat"><input type="hidden" id="destinationLon">
                    <label class="full">Alamat lengkap<textarea id="address" required rows="3" placeholder="Nama jalan, nomor rumah, kecamatan, patokan"></textarea></label>
                    <label>Kode pos<input id="postalCode" required inputmode="numeric" placeholder="201xx"></label>
                    <label>Catatan pesanan (opsional)<input id="orderNote" placeholder="Contoh: titip di satpam"></label>
                </div>
                <div class="shipping-box">
                    <div class="shipping-box-head"><div><strong>Estimasi pengiriman</strong><small>Lokasi memakai OpenStreetMap; RajaOngkir hanya dipanggil untuk tarif reguler.</small></div><button type="button" class="btn btn-small" id="calculateShipping"><i class="fa-solid fa-calculator"></i> Pilih Pengiriman</button></div>
                    <div id="shippingOptions" class="shipping-options"><div class="empty-shipping">Isi alamat terlebih dahulu, lalu klik <b>Hitung Ongkir</b>.</div></div>
                </div>
                <div class="checkout-summary" id="checkoutSummary"></div>
                <div class="modal-actions"><button type="button" class="btn btn-outline" data-close-modal>Kembali</button><button type="submit" class="btn btn-primary" id="toPaymentBtn">Lanjut ke Pembayaran <i class="fa-solid fa-arrow-right"></i></button></div>
            </form>
        </div>

        <div class="commerce-modal payment-modal" id="paymentModal" role="dialog" aria-modal="true" aria-labelledby="paymentTitle">
            <div class="modal-head">
                <div><span class="eyebrow">PEMBAYARAN QRIS</span><h2 id="paymentTitle">Bayar dengan QRIS</h2></div>
                <button class="modal-close" data-close-modal aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="payment-layout">
                <div class="qris-panel"><div class="qris-label"><i class="fa-solid fa-qrcode"></i> Scan QRIS toko</div><img src="assets/images/payment/qris-oishrum.jpeg" alt="QRIS Mushroom Organik"><p class="qris-note">Bayar sesuai total pesanan, lalu unggah screenshot bukti pembayaran agar pesanan segera diverifikasi.</p><label class="proof-upload">Upload bukti pembayaran<input type="file" id="paymentProof" accept="image/jpeg,image/png,image/webp" required><small>Format JPG, PNG, WEBP, maksimal 5 MB.</small></label></div>
                <div class="payment-info">
                    <div class="order-code"><span>Nomor Pesanan</span><strong id="orderNumber">-</strong></div>
                    <div class="pay-total"><span>Total yang harus dibayar</span><strong id="paymentTotal">Rp0</strong><button type="button" id="copyTotal" class="copy-btn"><i class="fa-regular fa-copy"></i> Salin</button></div>
                    <div class="payment-steps">
                        <div><b>1</b><span>Buka aplikasi pembayaran</span></div>
                        <div><b>2</b><span>Scan QRIS dan bayar sesuai nominal</span></div>
                        <div><b>3</b><span>Upload bukti pembayaran untuk diverifikasi admin</span></div>
                    </div>
                    <div class="payment-warning"><i class="fa-solid fa-circle-info"></i><span>Pesanan langsung masuk ke dashboard admin. Admin akan memeriksa bukti dan memperbarui status pembayaran.</span></div>
                </div>
            </div>
            <div class="modal-actions"><button type="button" class="btn btn-outline" id="backToCheckout">Kembali ke Checkout</button><button type="button" class="btn btn-primary" id="confirmPayment">Kirim Pesanan <i class="fa-solid fa-paper-plane"></i></button></div>
        </div>

        <div class="commerce-modal success-modal" id="successModal" role="dialog" aria-modal="true" aria-labelledby="successTitle">
            <div class="success-icon"><i class="fa-solid fa-check"></i></div>
            <span class="eyebrow">PESANAN DITERIMA</span>
            <h2 id="successTitle">Terima kasih, pesanan Anda berhasil dibuat.</h2>
            <p id="successText"></p>
            <div class="success-status"><i class="fa-solid fa-clock"></i><span><b>Menunggu verifikasi pembayaran</b><small>Admin akan memeriksa bukti QRIS dan memproses pesanan setelah pembayaran disetujui.</small></span></div>
            <div class="success-actions"><button class="btn btn-primary" id="printOrder"><i class="fa-solid fa-print"></i> Cetak Ringkasan</button><button class="btn btn-outline" id="closeSuccess">Kembali ke Produk</button></div>
        </div>
    </div>

    <!-- =====================================================
         TENTANG KAMI
    ====================================================== -->

    <section class="about-section" id="tentang">
        <div class="container">
            <div class="about-intro">
                <div>
                    <div class="about-kicker"><i class="fa-solid fa-seedling"></i> TENTANG KAMI</div>
                    <h2><?= catalogEscape($aboutTitle) ?></h2>
                    <p><?= nl2br(catalogEscape($aboutDescription)) ?></p>
                    <p>Kami berupaya menjaga kualitas bahan dan proses pengolahan agar jamur dapat dinikmati dalam berbagai bentuk, sekaligus memperkenalkan pengalaman budidaya jamur kepada masyarakat melalui <em>mushroom farm kit</em> dan media tanam.</p>
                    <p><strong>Pengalaman budidaya yang kami hadirkan</strong> mencakup jamur tiram putih segar yang dipanen setiap hari, baglog sebagai media tanam, serta bibit jamur tiram putih strain Florida dengan kultur jaringan murni.</p>
                </div>
                <div class="about-badge">
                    <div class="badge-icon"><i class="fa-solid fa-leaf"></i></div>
                    <h3><?= catalogEscape($aboutBadgeTitle) ?></h3>
                    <p><?= nl2br(catalogEscape($aboutBadgeText)) ?></p>
                </div>
            </div>

            <div class="about-values">
                <div class="about-value"><i class="fa-solid fa-seedling"></i><h4>Budidaya</h4><p>Mengembangkan jamur tiram dan media tanam sebagai bagian dari rantai usaha.</p></div>
                <div class="about-value"><i class="fa-solid fa-utensils"></i><h4>Produk Olahan</h4><p>Mengolah jamur menjadi produk pangan yang praktis dan bernilai tambah.</p></div>
                <div class="about-value"><i class="fa-solid fa-lightbulb"></i><h4>Inovasi</h4><p>Menghadirkan variasi produk dan pengalaman budidaya jamur di rumah.</p></div>
                <div class="about-value"><i class="fa-solid fa-shield-halved"></i><h4>Kualitas</h4><p>Memperhatikan bahan, kebersihan, dan proses untuk menjaga mutu produk.</p></div>
            </div>

            <div class="about-gallery-head">
                <div><div class="about-kicker">AKTIVITAS & PRODUK</div><h3>Budidaya, Media Tanam, dan Farm Kit</h3></div>
                <p>Ekosistem Mushroom Organik mencakup budidaya jamur tiram, penyediaan media tanam dan bibit, serta pengalaman menanam jamur sendiri di rumah.</p>
            </div>

            <div class="about-gallery">
                <figure class="about-photo"><img src="assets/images/about/gallery-01.jpeg" alt="Budidaya mushroom farm kit di rumah"><figcaption>Mushroom farm kit untuk pengalaman budidaya jamur tiram sendiri di rumah.</figcaption></figure>
                <figure class="about-photo"><img src="assets/images/about/gallery-02.jpeg" alt="Jamur tiram tumbuh pada farm kit"><figcaption>Farm kit dengan jamur tiram yang siap dipanen.</figcaption></figure>
                <figure class="about-photo"><img src="assets/images/about/gallery-03.jpeg" alt="Baglog sebagai media tanam jamur tiram"><figcaption><strong>Baglog (media tanam) jamur tiram putih.</strong></figcaption></figure>
                <figure class="about-photo"><img src="assets/images/about/gallery-05.jpeg" alt="Baglog jamur tiram pada rak budidaya"><figcaption>Media tanam jamur tiram pada rak budidaya.</figcaption></figure>
            </div>

            <div class="about-source-text">
                <div class="about-source-item">
                    <i class="fa-solid fa-leaf"></i>
                    <div><h4>Integrated Healthy Food Ecosystem</h4><p>Menghubungkan hulu ke hilir dalam pangan sehat, <em>plant-based</em>, natural, &amp; <em>shelf stable</em>.</p></div>
                </div>
                <div class="about-source-item">
                    <i class="fa-solid fa-gift"></i>
                    <div><h4>Mushroom Farm Kit</h4><p>“Hadiahkan orang tersayang dengan <em>mushroom farm kit</em> untuk pengalaman budidaya jamur tiram sendiri di rumah.”</p></div>
                </div>
                <div class="about-source-item">
                    <i class="fa-solid fa-seedling"></i>
                    <div><h4>Media &amp; Bibit</h4><p><strong>Baglog (media tanam) jamur tiram putih.</strong> Bibit jamur tiram putih strain Florida, kultur jaringan murni.</p></div>
                </div>
                <div class="about-source-item">
                    <i class="fa-solid fa-basket-shopping"></i>
                    <div><h4>Jamur Segar</h4><p>Jamur tiram putih segar, dipanen setiap hari.</p></div>
                </div>
            </div>

            <div class="halal-card">
                <div class="halal-info">
                    <div class="about-kicker"><i class="fa-solid fa-certificate"></i> LEGALITAS PRODUK</div>
                    <h3>Sertifikat Halal</h3>
                    <div class="halal-meta">
                        <div><small>Nomor Sertifikat</small><strong>ID12410021736220425</strong></div>
                        <div><small>Diterbitkan</small><strong>24 April 2025</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- =====================================================
         FARM STORY
    ====================================================== -->

    <section class="section farm-story"
             id="budidaya">

        <div class="container farm-grid">


            <div class="farm-image"></div>


            <div class="farm-text">

                <div class="small-title">
                    CERITA DI BALIK PRODUK
                </div>

                <h2>
                    Dari Budidaya Sendiri
                    hingga ke Meja Anda
                </h2>

                <p>

                    Kami membudidayakan jamur sendiri
                    dengan perhatian pada kualitas,
                    kebersihan dan keberlanjutan.

                </p>

                <p>

                    Jamur yang dipanen kemudian diolah
                    menjadi berbagai produk makanan sehat
                    yang dapat dinikmati oleh keluarga.

                </p>


                <div class="process">

                    <div class="process-item">

                        <div class="process-number">
                            1
                        </div>

                        <strong>
                            Budidaya Jamur
                        </strong>

                    </div>


                    <div class="process-item">

                        <div class="process-number">
                            2
                        </div>

                        <strong>
                            Panen
                        </strong>

                    </div>


                    <div class="process-item">

                        <div class="process-number">
                            3
                        </div>

                        <strong>
                            Pengolahan
                        </strong>

                    </div>


                    <div class="process-item">

                        <div class="process-number">
                            4
                        </div>

                        <strong>
                            Produk Sehat
                        </strong>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- =====================================================
         CTA
    ====================================================== -->

    <section class="cta">

        <div class="container">

            <h2>
                Sehat Dimulai dari Bahan yang Baik
            </h2>

            <p>
                Temukan berbagai produk olahan jamur
                hasil budidaya sendiri.
            </p>

            <a href="#produk"
               class="btn">

                Belanja Sekarang
                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>

    </section>


    <!-- =====================================================
         FOOTER
    ====================================================== -->

    <footer id="kontak">

        <div class="container">

            <div class="footer-grid">


                <div>

                    <h3>
                        Mushroom Organik
                    </h3>

                    <p>
                        Produk olahan sehat dari jamur
                        organik hasil budidaya sendiri.
                    </p>

                </div>


                <div>

                    <h3>
                        Navigasi
                    </h3>

                    <a href="#">
                        Beranda
                    </a>

                    <a href="#produk">
                        Produk
                    </a>

                    <a href="#tentang">
                        Tentang Kami
                    </a>

                    <a href="#budidaya">
                        Budidaya
                    </a>

                </div>


                <div>

                    <h3>
                        Bantuan
                    </h3>

                    <a href="#">
                        Cara Belanja
                    </a>

                    <a href="#">
                        Pengiriman
                    </a>

                    <a href="#">
                        Pembayaran
                    </a>

                    <a href="#">
                        FAQ
                    </a>

                </div>


                <div>

                    <h3>
                        Hubungi Kami
                    </h3>

                    <a href="https://wa.me/6282168576196?text=Halo%20Bos%20Global%2C%20saya%20ingin%20bertanya%20tentang%20produk%20Mushroom%20Organik." target="_blank" rel="noopener">
                        <i class="fa-brands fa-whatsapp"></i>&nbsp; WhatsApp: 0821-6857-6196
                    </a>

                    <a href="https://instagram.com/bosglobal.id" target="_blank" rel="noopener">
                        <i class="fa-brands fa-instagram"></i>&nbsp; Instagram: @bosglobal.id
                    </a>

                    <a href="mailto:bestonesolution.global@gmail.com">
                        <i class="fa-regular fa-envelope"></i>&nbsp; Email: bestonesolution.global@gmail.com
                    </a>

                    <a href="https://www.facebook.com/search/pages/?q=Bos%20Global" target="_blank" rel="noopener">
                        <i class="fa-brands fa-facebook"></i>&nbsp; Facebook: Bos Global
                    </a>

                    <p><small>Klik kontak di atas untuk terhubung langsung dengan Bos Global.</small></p>

                </div>

            </div>


            <div class="copyright">

                © 2026 Mushroom Organik.
                Semua hak dilindungi.

            </div>

        </div>

    </footer>


    <!-- =====================================================
         JAVASCRIPT — CART, CHECKOUT, SHIPPING & QRIS
    ====================================================== -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    (() => {
        const CART_USER_ID = <?= json_encode($user['id'] ?? 'guest') ?>;
        const STORAGE_KEY = 'mushroomOrganikCartV4_' + String(CART_USER_ID);
        const ORDER_KEY = 'mushroomOrganikLastOrderV3';
        const cartButton = document.getElementById('cartButton');
        const cartCounter = document.getElementById('cartCount');
        const backdrop = document.getElementById('modalBackdrop');
        const modals = ['cartModal','checkoutModal','paymentModal','successModal'];
        const money = n => new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(n);
        const getCart = () => { try { const cart = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); return Array.isArray(cart) ? cart : []; } catch (error) { localStorage.removeItem(STORAGE_KEY); return []; } };
        const saveCart = cart => { localStorage.setItem(STORAGE_KEY, JSON.stringify(cart)); renderCartCount(); };
        let shipping = null;
        let currentOrder = null;

        function renderCartCount(){
            const count = getCart().reduce((s,i)=>s+i.qty,0);
            cartCounter.textContent = count;
            cartCounter.classList.toggle('has-items', count > 0);
        }
        function openModal(id){
            modals.forEach(m=>document.getElementById(m).classList.remove('show'));
            backdrop.classList.add('show'); backdrop.setAttribute('aria-hidden','false');
            document.getElementById(id).classList.add('show');
            document.body.classList.add('modal-open');
            if(id==='cartModal') renderCart();
        }
        function closeAll(){
            backdrop.classList.remove('show'); backdrop.setAttribute('aria-hidden','true');
            modals.forEach(m=>document.getElementById(m).classList.remove('show'));
            document.body.classList.remove('modal-open');
        }
        function cartTotals(){
            const cart=getCart();
            return {subtotal:cart.reduce((s,i)=>s+i.price*i.qty,0), qty:cart.reduce((s,i)=>s+i.qty,0), weight:cart.reduce((s,i)=>s+i.qty*80,0)};
        }
        function renderCart(){
            const box=document.getElementById('cartContent'); const cart=getCart();
            if(!cart.length){
                box.innerHTML=`<div class="cart-empty"><div class="cart-empty-icon"><i class="fa-solid fa-basket-shopping"></i></div><h3>Keranjang masih kosong</h3><p>Pilih varian Jamur Krispi favorit Anda untuk melanjutkan ke checkout.</p><button class="btn btn-primary" id="shopNow">Belanja Produk</button></div>`;
                document.getElementById('shopNow').onclick=()=>{closeAll();document.getElementById('produk').scrollIntoView({behavior:'smooth'});};
                return;
            }
            box.innerHTML=`<div class="cart-list">${cart.map(i=>`<div class="cart-row">
                <img src="${i.image}" alt="${i.name}">
                <div class="cart-item-main"><strong>${i.name}</strong><small>${i.claim} · ${i.weight} gram</small><span>${money(i.price)}</span></div>
                <div class="qty-control"><button data-minus="${i.id}">−</button><b>${i.qty}</b><button data-plus="${i.id}">+</button></div>
                <button class="remove-item" data-remove="${i.id}" aria-label="Hapus"><i class="fa-regular fa-trash-can"></i></button>
            </div>`).join('')}</div>
            <div class="cart-total"><div><span>Subtotal produk</span><strong>${money(cartTotals().subtotal)}</strong></div><small>Berat total: ${cartTotals().weight} gram · Ongkir dihitung setelah alamat diisi.</small></div>
            <div class="modal-actions"><button class="btn btn-outline" id="continueShopping">Lanjut Belanja</button><button class="btn btn-primary" id="checkoutNow"><?php if (!$user): ?>Login untuk Checkout<?php else: ?>Lanjut ke Checkout <i class="fa-solid fa-arrow-right"></i><?php endif; ?></button></div>`;
            box.querySelectorAll('[data-plus]').forEach(b=>b.onclick=()=>changeQty(b.dataset.plus,1));
            box.querySelectorAll('[data-minus]').forEach(b=>b.onclick=()=>changeQty(b.dataset.minus,-1));
            box.querySelectorAll('[data-remove]').forEach(b=>b.onclick=()=>removeItem(b.dataset.remove));
            document.getElementById('continueShopping').onclick=()=>closeAll();
            document.getElementById('checkoutNow').onclick=()=>openCheckout();
        }
        function changeQty(id,delta){
            const cart=getCart(); const item=cart.find(i=>i.id===id); if(!item)return;
            item.qty+=delta; if(item.qty<=0) cart.splice(cart.indexOf(item),1); saveCart(cart); renderCart();
        }
        function removeItem(id){saveCart(getCart().filter(i=>i.id!==id));renderCart();}
        function addProduct(card, button){
            const id=card.dataset.productId, name=card.dataset.productName, price=Number(card.dataset.price), image=card.dataset.image;
            const claim=(card.querySelector('.variant-claim')?.textContent||'PREMIUM SNACK').trim();
            const weight=(card.querySelector('.product-weight')?.textContent.match(/\d+/)||['80'])[0];
            const cart=getCart(); let item=cart.find(i=>i.id===id);
            if(item)item.qty++; else cart.push({id,name,price,image,claim,weight,qty:1});
            saveCart(cart); renderCart();
            button.innerHTML='<i class="fa-solid fa-check"></i> Ditambahkan';
            setTimeout(()=>button.innerHTML='Tambah ke Keranjang',1200);
        }

        document.querySelectorAll('.product-card').forEach(card=>{
            const button=card.querySelector('.product-action'); if(button) button.addEventListener('click',e=>{e.stopPropagation();addProduct(card,button);});
        });
        cartButton.addEventListener('click',()=>openModal('cartModal'));
        document.querySelectorAll('[data-close-modal]').forEach(b=>b.addEventListener('click',closeAll));
        backdrop.addEventListener('click',e=>{if(e.target===backdrop)closeAll();});
        document.addEventListener('keydown',e=>{if(e.key==='Escape')closeAll();});

        // Live search: filter the eight catalog products without leaving the page.
        const search=document.getElementById('searchInput');
        search.addEventListener('input',()=>{
            const q=search.value.trim().toLowerCase();
            document.querySelectorAll('.product-card').forEach(card=>card.style.display=card.innerText.toLowerCase().includes(q)?'':'none');
        });

        const csrfToken = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE) ?>;
        const esc=value=>String(value??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
        const citySearch=document.getElementById('citySearch'), cityInput=document.getElementById('city'), findLocations=document.getElementById('findLocations'), shippingModeInput=document.getElementById('shippingMode'), latitudeInput=document.getElementById('destinationLat'), longitudeInput=document.getElementById('destinationLon'), mapResults=document.getElementById('mapResults');
        let selectedDestinationId=''; let locationController=null; let map=null; let mapMarker=null; const locationCache={};
        function resetShippingChoice(){shipping=null;document.getElementById('shippingOptions').innerHTML='<div class="empty-shipping">Pilih alamat di peta, lalu klik <b>Pilih Pengiriman</b>.</div>';document.getElementById('checkoutSummary').innerHTML='';}
        function ensureMap(){
            if(map || typeof L === 'undefined')return;
            map=L.map('shippingMap').setView([3.5952,98.6722],10);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; OpenStreetMap'}).addTo(map);
            map.on('click',event=>selectMapLocation(event.latlng.lat,event.latlng.lng,'Mencari alamat marker...'));
        }
        function selectMapLocation(latitude,longitude,label){
            latitudeInput.value=latitude; longitudeInput.value=longitude; selectedDestinationId='osm';
            cityInput.innerHTML=`<option value="osm">Lokasi dipilih dari peta</option>`; cityInput.value='osm'; cityInput.disabled=false;
            if(mapMarker)mapMarker.setLatLng([latitude,longitude]); else mapMarker=L.marker([latitude,longitude]).addTo(map);
            map.setView([latitude,longitude],15); resetShippingChoice();
            citySearch.value=label;
            fillAddressFromMap(latitude,longitude,label);
        }
        async function findLocationOptions(){
            const query=citySearch.value.trim(); if(query.length<3){alert('Ketik minimal 3 karakter alamat.');return;}
            if(locationController)locationController.abort(); locationController=new AbortController(); findLocations.disabled=true; findLocations.textContent='Mencari...'; mapResults.innerHTML='';
            try{
                const cacheKey=query.toLowerCase(); let locations=locationCache[cacheKey];
                if(!locations){const response=await fetch('api/geocode.php?q='+encodeURIComponent(query)); const result=await response.json(); if(!response.ok||!result.success)throw new Error(result.message||'OpenStreetMap tidak dapat dihubungi.'); locations=result.locations; locationCache[cacheKey]=locations;}
                if(!Array.isArray(locations)||!locations.length)throw new Error('Alamat tidak ditemukan di OpenStreetMap.');
                mapResults.innerHTML=locations.map((location,index)=>`<button type="button" class="map-result" data-location-index="${index}">${esc(location.display_name)}</button>`).join('');
                mapResults.querySelectorAll('[data-location-index]').forEach(button=>button.addEventListener('click',()=>{const location=locations[Number(button.dataset.locationIndex)];ensureMap();selectMapLocation(Number(location.lat),Number(location.lon),location.display_name);mapResults.innerHTML='';}));
                ensureMap();
            }catch(error){mapResults.innerHTML=`<small>${esc(error.message||'Pencarian alamat gagal.')}</small>`;}
            finally{findLocations.disabled=false;findLocations.textContent='Cari di peta';}
        }
        async function fillAddressFromMap(latitude,longitude,fallbackLabel){
            try {
                const response=await fetch('api/geocode.php?action=reverse&lat='+encodeURIComponent(latitude)+'&lon='+encodeURIComponent(longitude));
                const result=await response.json(); if(!response.ok||!result.success)throw new Error(result.message||'Alamat tidak ditemukan.');
                const address=result.location.address||{};
                const district=address.municipality||address.district||address.county||address.suburb||address.village||'';
                const city=address.city||address.town||address.municipality||address.county||'';
                document.getElementById('address').value=result.location.display_name||fallbackLabel;
                document.getElementById('province').value=address.state||address.province||'';
                cityInput.innerHTML='<option value="osm">'+esc(district||city||'Lokasi dipilih dari peta')+'</option>'; cityInput.value='osm'; cityInput.disabled=false;
                document.getElementById('postalCode').value=address.postcode||'';
                resetShippingChoice();
            } catch(error) {
                document.getElementById('address').value=fallbackLabel;
                document.getElementById('shippingOptions').innerHTML='<div class="empty-shipping">Marker tersimpan, tetapi detail alamat belum tersedia. '+esc(error.message)+'</div>';
            }
        }
        findLocations.addEventListener('click',findLocationOptions);
        function openCheckout(){
            if(!getCart().length)return renderCart();
            <?php if (!$user): ?>
            window.location.href='auth.php';
            return;
            <?php endif; ?>
            openModal('checkoutModal');
            ensureMap(); if(map)setTimeout(()=>map.invalidateSize(),100);
        }
        function validateAddress(){
            const ids=['customerName','customerPhone','province','city','address','postalCode'];
            for(const id of ids){const el=document.getElementById(id);if(!el.value.trim()){el.focus();return false;}}
            return true;
        }
        async function calculateShipping(){
            if(!validateAddress()){alert('Lengkapi nama, WhatsApp, provinsi, kota, alamat, dan kode pos terlebih dahulu.');return;}
            const province=document.getElementById('province').value;
            if(!selectedDestinationId||!latitudeInput.value||!longitudeInput.value){alert('Pilih alamat dari hasil OpenStreetMap terlebih dahulu.');return;}
            const button=document.getElementById('calculateShipping'); button.disabled=true; document.getElementById('shippingOptions').innerHTML='<div class="empty-shipping">Mengambil tarif RajaOngkir...</div>';
            try {
                const response=await fetch('api/shipping.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify({mode:shippingModeInput.value,latitude:Number(latitudeInput.value),longitude:Number(longitudeInput.value),weight:cartTotals().weight})});
                const result=await response.json(); if(!response.ok||!result.success)throw new Error(result.message||'Ongkir gagal dihitung.');
                const options=result.options; shipping=options[0];
                document.getElementById('shippingOptions').innerHTML=options.map((o,idx)=>`<label class="shipping-option ${idx===0?'selected':''}"><input type="radio" name="shipping" value="${o.cost}" data-days="${o.days}" data-name="${o.name}" ${idx===0?'checked':''}><span><b>${o.name}</b><small>Estimasi tiba ${o.days}</small></span><strong>${money(o.cost)}</strong></label>`).join('');
            document.querySelectorAll('input[name="shipping"]').forEach(r=>r.addEventListener('change',()=>{shipping={name:r.dataset.name,cost:Number(r.value),days:r.dataset.days};document.querySelectorAll('.shipping-option').forEach(x=>x.classList.remove('selected'));r.closest('.shipping-option').classList.add('selected');updateCheckoutSummary();}));
            updateCheckoutSummary();
            } catch(error) { shipping=null; document.getElementById('shippingOptions').innerHTML=`<div class="empty-shipping">${error.message}</div>`; }
            finally { button.disabled=false; }
        }
        function updateCheckoutSummary(){
            if(!shipping)return;
            const t=cartTotals(), total=t.subtotal+shipping.cost;
            document.getElementById('checkoutSummary').innerHTML=`<div><span>Subtotal produk</span><strong>${money(t.subtotal)}</strong></div><div><span>Ongkir · ${shipping.name}</span><strong>${money(shipping.cost)}</strong></div><div class="grand"><span>Total pembayaran</span><strong>${money(total)}</strong></div><small>Estimasi tiba: <b>${shipping.days}</b>. Total berat produk: ${t.weight} gram.</small>`;
        }
        document.getElementById('calculateShipping').addEventListener('click',calculateShipping);
        document.getElementById('checkoutForm').addEventListener('submit',e=>{
            e.preventDefault();
            if(!validateAddress()){alert('Lengkapi data alamat terlebih dahulu.');return;}
            if(!shipping){alert('Klik “Hitung Ongkir” untuk mendapatkan estimasi biaya dan waktu tiba.');return;}
            const t=cartTotals();
            currentOrder={
                id:'OIS-'+new Date().toISOString().slice(0,10).replaceAll('-','')+'-'+Math.floor(1000+Math.random()*9000),
                customer:{name:customerName.value.trim(),phone:customerPhone.value.trim(),province:province.value,city:city.value.trim(),address:address.value.trim(),postalCode:postalCode.value.trim(),note:orderNote.value.trim()},
                items:getCart(), subtotal:t.subtotal, shipping, shippingMode:shippingModeInput.value, destinationLat:Number(latitudeInput.value), destinationLon:Number(longitudeInput.value), total:t.subtotal+shipping.cost, createdAt:new Date().toISOString()
            };
            document.getElementById('orderNumber').textContent=currentOrder.id;
            document.getElementById('paymentTotal').textContent=money(currentOrder.total);
            openModal('paymentModal');
        });
        document.getElementById('backToCheckout').addEventListener('click',()=>openModal('checkoutModal'));
        document.getElementById('copyTotal').addEventListener('click',async()=>{try{await navigator.clipboard.writeText(String(currentOrder.total));document.getElementById('copyTotal').innerHTML='<i class="fa-solid fa-check"></i> Tersalin';setTimeout(()=>document.getElementById('copyTotal').innerHTML='<i class="fa-regular fa-copy"></i> Salin',1200);}catch(e){alert('Nominal: '+money(currentOrder.total));}});
        document.getElementById('confirmPayment').addEventListener('click',async()=>{
            if(!currentOrder)return;
            const proof=document.getElementById('paymentProof').files[0];
            if(!proof){alert('Unggah bukti pembayaran terlebih dahulu.');return;}
            const button=document.getElementById('confirmPayment');
            button.disabled=true;
            try {
                const payload=new FormData(); payload.append('payload',JSON.stringify(currentOrder)); payload.append('proof',proof);
                const response=await fetch('api/order.php',{method:'POST',headers:{'X-CSRF-Token':csrfToken},body:payload});
                const result=await response.json();
                if(!response.ok||!result.success) throw new Error(result.message||'Pesanan gagal disimpan.');
                currentOrder.id=result.order.id;
                currentOrder.total=result.order.total;
                currentOrder.paymentStatus=result.order.paymentStatus;
                localStorage.setItem(ORDER_KEY,JSON.stringify(currentOrder)); localStorage.removeItem(STORAGE_KEY); renderCartCount();
                showPaymentResult('Bukti pembayaran sudah diterima dan menunggu pemeriksaan admin.','Menunggu verifikasi pembayaran');
            } catch(error) {
                alert(error.message||'Pesanan gagal disimpan. Coba lagi.');
            } finally {
                button.disabled=false;
            }
        });
        function showPaymentResult(message,status){currentOrder.paymentStatus=status;localStorage.setItem(ORDER_KEY,JSON.stringify(currentOrder));document.getElementById('successText').innerHTML=`Nomor pesanan Anda <b>${currentOrder.id}</b>. ${message} Total <b>${money(currentOrder.total)}</b>.`;openModal('successModal');}
        document.getElementById('printOrder').addEventListener('click',()=>{
            if(!currentOrder)return;
            const lines=currentOrder.items.map(i=>`<tr><td>${i.name}</td><td>${i.qty}</td><td>${money(i.price*i.qty)}</td></tr>`).join('');
            const w=window.open('','_blank'); if(!w)return;
            w.document.write(`<!doctype html><html><head><title>${currentOrder.id}</title><style>body{font-family:Arial,sans-serif;padding:30px;color:#1f2d22}h1{color:#2f6b3f}table{width:100%;border-collapse:collapse;margin-top:20px}th,td{padding:10px;border-bottom:1px solid #ddd;text-align:left}.total{font-size:20px;font-weight:bold;margin-top:20px}</style></head><body><h1>Mushroom Organik</h1><p>Ringkasan Pesanan</p><h3>${currentOrder.id}</h3><p>${currentOrder.customer.name}<br>${currentOrder.customer.phone}<br>${currentOrder.customer.address}, ${currentOrder.customer.city}, ${currentOrder.customer.province} ${currentOrder.customer.postalCode}</p><table><thead><tr><th>Produk</th><th>Qty</th><th>Total</th></tr></thead><tbody>${lines}</tbody></table><p>Ongkir: ${money(currentOrder.shipping.cost)} (${currentOrder.shipping.name})</p><p class="total">Total: ${money(currentOrder.total)}</p><p>Status pembayaran: ${currentOrder.paymentStatus||'Menunggu pembayaran'}.</p><script>window.print()<\/script></body></html>`);w.document.close();
        });
        document.getElementById('closeSuccess').addEventListener('click',()=>{closeAll();document.getElementById('produk').scrollIntoView({behavior:'smooth'});});
        const navLinks=[...document.querySelectorAll('.nav-menu a[data-section]')];
        const sections=navLinks.map(link=>({link,section:document.getElementById(link.dataset.section)})).filter(item=>item.section);
        function setActiveNav(sectionId){navLinks.forEach(link=>link.classList.toggle('active',link.dataset.section===sectionId));}
        function setActiveFromHash(){setActiveNav(location.hash.replace('#','')||'top');}
        navLinks.forEach(link=>link.addEventListener('click',()=>setActiveNav(link.dataset.section)));
        window.addEventListener('hashchange',setActiveFromHash);
        if('IntersectionObserver' in window){
            const observer=new IntersectionObserver(entries=>{
                const visible=entries.filter(entry=>entry.isIntersecting).sort((first,second)=>second.intersectionRatio-first.intersectionRatio)[0];
                if(visible)setActiveNav(visible.target.id);
            },{rootMargin:'-35% 0px -55% 0px',threshold:[0,.25,.5,1]});
            sections.forEach(item=>observer.observe(item.section));
        }
        setActiveFromHash();
        renderCartCount();
    })();
    document.addEventListener('click',event=>{if(!event.target.closest('#checkoutNow'))return;setTimeout(()=>{document.getElementById('checkoutSummary').innerHTML='';document.getElementById('shippingOptions').innerHTML='<div class="empty-shipping">Menghitung ulang ongkir untuk keranjang terbaru...</div>';document.getElementById('paymentProof').value='';document.getElementById('calculateShipping').click();},0)},true);
    </script>

</body>
</html>