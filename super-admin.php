<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PM Super Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ═══════════════════════════════════════════════════════
           ROOT VARIABLES - Matching Prompt Manager Theme
           ═══════════════════════════════════════════════════════ */
        :root {
            --bg-primary: #0a0a0f;
            --bg-secondary: #12121a;
            --bg-tertiary: #1a1a25;
            --bg-card: #15151f;
            --border-color: #2a2a3a;
            --border-glow: #4f46e5;
            --text-primary: #f0f0f5;
            --text-secondary: #a0a0b0;
            --text-muted: #606070;
            --accent-primary: #6366f1;
            --accent-secondary: #8b5cf6;
            --accent-tertiary: #a855f7;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #06b6d4;
            --gradient-main: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
            --gradient-dark: linear-gradient(180deg, #0a0a0f 0%, #12121a 100%);
            --shadow-glow: 0 0 40px rgba(99, 102, 241, 0.15);
            --shadow-card: 0 4px 24px rgba(0, 0, 0, 0.4);
            --sidebar-width: 280px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Space Grotesk', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ═══════════════════════════════════════════════════════
           LOGIN OVERLAY & MODAL
           ═══════════════════════════════════════════════════════ */
        .sa-login-overlay {
            position: fixed;
            inset: 0;
            background: rgba(5, 5, 10, 0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            opacity: 0;
            animation: saFadeIn 0.5s ease forwards;
        }
        .sa-login-overlay.hidden {
            display: none !important;
        }

        @keyframes saFadeIn {
            to { opacity: 1; }
        }

        .sa-login-card {
            background: linear-gradient(145deg, #13131e 0%, #0d0d14 100%);
            border: 1.5px solid rgba(99, 102, 241, 0.25);
            border-radius: 20px;
            padding: 2.5rem 2.5rem 2rem;
            width: 400px;
            max-width: 92vw;
            box-shadow:
                0 0 80px rgba(99, 102, 241, 0.12),
                0 20px 60px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
            transform: translateY(30px) scale(0.95);
            animation: saSlideUp 0.5s 0.15s ease forwards;
            position: relative;
            overflow: hidden;
        }
        .sa-login-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #a855f7, #06b6d4);
            border-radius: 20px 20px 0 0;
        }

        @keyframes saSlideUp {
            to { transform: translateY(0) scale(1); }
        }

        .sa-login-shield {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .sa-login-shield .shield-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.1) 100%);
            border: 2px solid rgba(99, 102, 241, 0.25);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            animation: saPulseGlow 3s ease-in-out infinite;
        }
        .sa-login-shield .shield-icon i {
            font-size: 1.8rem;
            background: var(--gradient-main);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        @keyframes saPulseGlow {
            0%, 100% { box-shadow: 0 0 20px rgba(99, 102, 241, 0.15); }
            50% { box-shadow: 0 0 35px rgba(99, 102, 241, 0.3); }
        }

        .sa-login-shield h2 {
            font-size: 1.4rem;
            font-weight: 700;
            background: var(--gradient-main);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.3rem;
        }
        .sa-login-shield p {
            font-size: 0.8rem;
            color: var(--text-muted);
            letter-spacing: 0.5px;
        }

        .sa-login-form { display: flex; flex-direction: column; gap: 1rem; }

        .sa-input-group {
            position: relative;
        }
        .sa-input-group label {
            display: block;
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-bottom: 0.4rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .sa-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .sa-input-wrapper i.input-icon {
            position: absolute;
            left: 14px;
            color: var(--text-muted);
            font-size: 0.85rem;
            transition: color 0.3s ease;
        }
        .sa-input-wrapper input {
            width: 100%;
            padding: 0.8rem 3rem 0.8rem 2.6rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1.5px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px;
            color: var(--text-primary);
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            letter-spacing: 1px;
        }
        .sa-input-wrapper input:focus {
            outline: none;
            border-color: rgba(99, 102, 241, 0.5);
            background: rgba(99, 102, 241, 0.05);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.1);
        }
        .sa-input-wrapper input:focus ~ i.input-icon,
        .sa-input-wrapper input:focus ~ .sa-toggle-pw {
            color: var(--accent-primary);
        }
        .sa-input-wrapper input::placeholder {
            color: var(--text-muted);
            letter-spacing: 0;
        }

        .sa-toggle-pw {
            position: absolute;
            right: 14px;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 0.9rem;
            padding: 4px;
            transition: color 0.3s ease;
        }
        .sa-toggle-pw:hover {
            color: var(--accent-secondary);
        }

        .sa-login-btn {
            width: 100%;
            padding: 0.85rem;
            background: var(--gradient-main);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            letter-spacing: 0.5px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .sa-login-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .sa-login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(99, 102, 241, 0.35);
        }
        .sa-login-btn:hover::before { opacity: 1; }
        .sa-login-btn:active { transform: translateY(0); }

        .sa-login-error {
            display: none;
            align-items: center;
            gap: 8px;
            padding: 0.6rem 0.8rem;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            border-radius: 10px;
            color: #fca5a5;
            font-size: 0.8rem;
            animation: saShake 0.4s ease;
        }
        .sa-login-error.show { display: flex; }

        @keyframes saShake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-8px); }
            40% { transform: translateX(8px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(4px); }
        }

        .sa-login-footer {
            text-align: center;
            margin-top: 1.2rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(99, 102, 241, 0.1);
        }
        .sa-login-footer a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.75rem;
            transition: color 0.3s ease;
        }
        .sa-login-footer a:hover {
            color: var(--accent-primary);
        }

        /* ═══════════════════════════════════════════════════════
           DASHBOARD LAYOUT
           ═══════════════════════════════════════════════════════ */
        .sa-dashboard {
            display: none;
            min-height: 100vh;
        }
        .sa-dashboard.active { display: flex; }

        /* ── Left Sidebar ── */
        .sa-sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: linear-gradient(180deg, #0e0e16 0%, #0a0a10 100%);
            border-right: 1px solid rgba(99, 102, 241, 0.12);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sa-sidebar-header {
            padding: 1.2rem 1.4rem;
            border-bottom: 1px solid rgba(99, 102, 241, 0.1);
            position: relative;
        }
        .sa-sidebar-header::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #a855f7, #06b6d4);
        }
        .sa-sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sa-sidebar-brand .brand-icon {
            width: 42px; height: 42px;
            background: var(--gradient-main);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: #fff;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }
        .sa-sidebar-brand .brand-text h3 {
            font-size: 1rem;
            font-weight: 700;
            background: var(--gradient-main);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1.2;
        }
        .sa-sidebar-brand .brand-text span {
            font-size: 0.65rem;
            color: var(--text-muted);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            font-weight: 600;
        }

        /* ── Sidebar Navigation ── */
        .sa-sidebar-nav {
            flex: 1;
            padding: 1rem 0.8rem;
            overflow-y: auto;
        }
        .sa-nav-section {
            margin-bottom: 1.5rem;
        }
        .sa-nav-section-title {
            font-size: 0.6rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 700;
            padding: 0 0.8rem;
            margin-bottom: 0.6rem;
        }

        .sa-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.7rem 0.9rem;
            border-radius: 10px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.25s ease;
            margin-bottom: 2px;
            position: relative;
        }
        .sa-nav-item i {
            width: 20px;
            text-align: center;
            font-size: 0.9rem;
        }
        .sa-nav-item:hover {
            background: rgba(99, 102, 241, 0.08);
            color: var(--text-primary);
        }
        .sa-nav-item.active {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.1));
            color: #a5b4fc;
            border: 1px solid rgba(99, 102, 241, 0.2);
        }
        .sa-nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            background: var(--gradient-main);
            border-radius: 0 4px 4px 0;
        }
        .sa-nav-badge {
            margin-left: auto;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .sa-nav-badge.soon {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }
        .sa-nav-badge.new {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        /* ── Sidebar Footer ── */
        .sa-sidebar-footer {
            padding: 1rem 1.2rem;
            border-top: 1px solid rgba(99, 102, 241, 0.1);
        }
        .sa-sidebar-footer .sa-admin-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.6rem 0.8rem;
            background: rgba(99, 102, 241, 0.06);
            border: 1px solid rgba(99, 102, 241, 0.12);
            border-radius: 10px;
        }
        .sa-admin-badge .admin-avatar {
            width: 34px; height: 34px;
            background: var(--gradient-main);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            color: #fff;
        }
        .sa-admin-badge .admin-info span {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        .sa-admin-badge .admin-info small {
            font-size: 0.6rem;
            color: var(--success);
            letter-spacing: 0.5px;
        }

        /* ── Main Content Area ── */
        .sa-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: var(--bg-primary);
        }

        /* ── Top Bar ── */
        .sa-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.8rem 2rem;
            background: rgba(10, 10, 15, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(99, 102, 241, 0.08);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .sa-topbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .sa-topbar-left h1 {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .sa-topbar-left .page-indicator {
            font-size: 0.7rem;
            color: var(--text-muted);
            background: rgba(99, 102, 241, 0.08);
            padding: 3px 10px;
            border-radius: 20px;
            border: 1px solid rgba(99, 102, 241, 0.12);
        }
        .sa-topbar-right {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        .sa-topbar-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.45rem 0.9rem;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(99, 102, 241, 0.15);
            border-radius: 8px;
            color: var(--text-secondary);
            font-size: 0.78rem;
            font-family: 'Space Grotesk', sans-serif;
            cursor: pointer;
            transition: all 0.25s ease;
        }
        .sa-topbar-btn:hover {
            background: rgba(99, 102, 241, 0.1);
            border-color: rgba(99, 102, 241, 0.3);
            color: var(--text-primary);
        }
        .sa-topbar-btn.danger:hover {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        /* ── Content Area ── */
        .sa-content {
            padding: 2rem;
        }

        /* ── Page Containers (shown/hidden by JS nav) ── */
        .sa-page { display: none; animation: saPageIn 0.35s ease; }
        .sa-page.active { display: block; }

        @keyframes saPageIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── Stats Row ── */
        .sa-stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.2rem;
            margin-bottom: 2rem;
        }
        .sa-stat-card {
            background: linear-gradient(145deg, rgba(21, 21, 31, 0.9), rgba(18, 18, 26, 0.7));
            border: 1px solid rgba(99, 102, 241, 0.1);
            border-radius: 14px;
            padding: 1.3rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .sa-stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 3px; height: 100%;
            border-radius: 14px 0 0 14px;
        }
        .sa-stat-card.purple::before { background: var(--gradient-main); }
        .sa-stat-card.cyan::before { background: linear-gradient(180deg, #06b6d4, #22d3ee); }
        .sa-stat-card.green::before { background: linear-gradient(180deg, #10b981, #34d399); }
        .sa-stat-card.amber::before { background: linear-gradient(180deg, #f59e0b, #fbbf24); }

        .sa-stat-card:hover {
            border-color: rgba(99, 102, 241, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
        }
        .sa-stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .sa-stat-card.purple .sa-stat-icon {
            background: rgba(99, 102, 241, 0.12);
            color: #a5b4fc;
        }
        .sa-stat-card.cyan .sa-stat-icon {
            background: rgba(6, 182, 212, 0.12);
            color: #67e8f9;
        }
        .sa-stat-card.green .sa-stat-icon {
            background: rgba(16, 185, 129, 0.12);
            color: #6ee7b7;
        }
        .sa-stat-card.amber .sa-stat-icon {
            background: rgba(245, 158, 11, 0.12);
            color: #fcd34d;
        }
        .sa-stat-info h4 {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.2;
        }
        .sa-stat-info p {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* ── Section Cards ── */
        .sa-section-card {
            background: linear-gradient(145deg, rgba(21, 21, 31, 0.9), rgba(18, 18, 26, 0.7));
            border: 1px solid rgba(99, 102, 241, 0.1);
            border-radius: 14px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        .sa-section-card:hover {
            border-color: rgba(99, 102, 241, 0.18);
        }
        .sa-section-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.2rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid rgba(99, 102, 241, 0.08);
        }
        .sa-section-card-header h3 {
            font-size: 1rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .sa-section-card-header h3 i {
            font-size: 0.9rem;
            color: var(--accent-primary);
        }

        /* ── Table Placeholder ── */
        .sa-table-placeholder {
            width: 100%;
            border-collapse: collapse;
        }
        .sa-table-placeholder thead th {
            text-align: left;
            padding: 0.7rem 1rem;
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            border-bottom: 1px solid rgba(99, 102, 241, 0.08);
        }
        .sa-table-placeholder tbody tr {
            transition: background 0.2s ease;
        }
        .sa-table-placeholder tbody tr:hover {
            background: rgba(99, 102, 241, 0.04);
        }
        .sa-table-placeholder tbody td {
            padding: 0.8rem 1rem;
            font-size: 0.85rem;
            color: var(--text-secondary);
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }
        .sa-tier-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .sa-tier-badge.pro {
            background: rgba(99, 102, 241, 0.15);
            color: #a5b4fc;
            border: 1px solid rgba(99, 102, 241, 0.25);
        }
        .sa-tier-badge.free {
            background: rgba(107, 114, 128, 0.15);
            color: #9ca3af;
            border: 1px solid rgba(107, 114, 128, 0.25);
        }
        .sa-tier-badge.enterprise {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.25);
        }
        .sa-status-dot {
            display: inline-block;
            width: 8px; height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }
        .sa-status-dot.online { background: var(--success); box-shadow: 0 0 8px rgba(16, 185, 129, 0.4); }
        .sa-status-dot.offline { background: var(--text-muted); }

        /* ── Action Buttons inside cards ── */
        .sa-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.72rem;
            font-weight: 600;
            font-family: 'Space Grotesk', sans-serif;
            cursor: not-allowed;
            opacity: 0.6;
            border: 1px solid;
            transition: all 0.25s ease;
        }
        .sa-action-btn.edit {
            background: rgba(99, 102, 241, 0.1);
            border-color: rgba(99, 102, 241, 0.2);
            color: #a5b4fc;
        }
        .sa-action-btn.delete {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }
        .sa-action-btn.view {
            background: rgba(6, 182, 212, 0.1);
            border-color: rgba(6, 182, 212, 0.2);
            color: #67e8f9;
        }

        /* ── Feature Grid (for pages with placeholder cards) ── */
        .sa-feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.2rem;
        }
        .sa-feature-card {
            background: rgba(21, 21, 31, 0.6);
            border: 1px dashed rgba(99, 102, 241, 0.15);
            border-radius: 14px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        .sa-feature-card:hover {
            border-color: rgba(99, 102, 241, 0.3);
            background: rgba(99, 102, 241, 0.04);
            transform: translateY(-3px);
        }
        .sa-feature-card .feature-icon {
            width: 56px; height: 56px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 0.8rem;
        }
        .sa-feature-card .feature-icon.purple {
            background: rgba(99, 102, 241, 0.12);
            color: #a5b4fc;
        }
        .sa-feature-card .feature-icon.cyan {
            background: rgba(6, 182, 212, 0.12);
            color: #67e8f9;
        }
        .sa-feature-card .feature-icon.green {
            background: rgba(16, 185, 129, 0.12);
            color: #6ee7b7;
        }
        .sa-feature-card .feature-icon.amber {
            background: rgba(245, 158, 11, 0.12);
            color: #fcd34d;
        }
        .sa-feature-card .feature-icon.red {
            background: rgba(239, 68, 68, 0.12);
            color: #fca5a5;
        }
        .sa-feature-card .feature-icon.pink {
            background: rgba(236, 72, 153, 0.12);
            color: #f9a8d4;
        }
        .sa-feature-card h4 {
            font-size: 0.9rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
        }
        .sa-feature-card p {
            font-size: 0.72rem;
            color: var(--text-muted);
            line-height: 1.5;
        }
        .sa-feature-card .coming-soon-tag {
            display: inline-block;
            margin-top: 0.6rem;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.58rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            background: rgba(245, 158, 11, 0.12);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        /* ── Empty State ── */
        .sa-empty-state {
            text-align: center;
            padding: 3rem 2rem;
        }
        .sa-empty-state i {
            font-size: 2.5rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
            display: block;
        }
        .sa-empty-state h4 {
            font-size: 1rem;
            color: var(--text-secondary);
            margin-bottom: 0.3rem;
        }
        .sa-empty-state p {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* ── Scrollbar ── */
        .sa-sidebar-nav::-webkit-scrollbar { width: 4px; }
        .sa-sidebar-nav::-webkit-scrollbar-track { background: transparent; }
        .sa-sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.2);
            border-radius: 4px;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .sa-sidebar {
                transform: translateX(-100%);
            }
            .sa-sidebar.mobile-open {
                transform: translateX(0);
            }
            .sa-main {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

    <!-- ═══════════════════════════════════════════════════════
         LOGIN OVERLAY (Always shown first)
         ═══════════════════════════════════════════════════════ -->
    <div class="sa-login-overlay" id="saLoginOverlay">
        <div class="sa-login-card">
            <div class="sa-login-shield">
                <div class="shield-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h2>Super Admin Access</h2>
                <p>Restricted Area &mdash; Authorized Personnel Only</p>
            </div>

            <form class="sa-login-form" id="saLoginForm">
                <div class="sa-input-group">
                    <label>Password</label>
                    <div class="sa-input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="saPasswordInput" placeholder="Enter admin password" autocomplete="off" autofocus>
                        <button type="button" class="sa-toggle-pw" onclick="saTogglePassword()" title="Show Password">
                            <i class="fas fa-eye" id="saEyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="sa-login-error" id="saLoginError">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Invalid password. Access denied.</span>
                </div>

                <button type="submit" class="sa-login-btn">
                    <i class="fas fa-unlock-alt"></i>&nbsp; Authenticate
                </button>
            </form>

            <div class="sa-login-footer">
                <a href="index.php"><i class="fas fa-arrow-left"></i>&nbsp; Back to Prompt Manager</a>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         DASHBOARD (Hidden until login)
         ═══════════════════════════════════════════════════════ -->
    <div class="sa-dashboard" id="saDashboard">

        <!-- ── Left Sidebar ── -->
        <nav class="sa-sidebar" id="saSidebar">
            <div class="sa-sidebar-header">
                <div class="sa-sidebar-brand">
                    <div class="brand-icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="brand-text">
                        <h3>PM Admin</h3>
                        <span>Super Admin Panel</span>
                    </div>
                </div>
            </div>

            <div class="sa-sidebar-nav">
                <!-- Main -->
                <div class="sa-nav-section">
                    <div class="sa-nav-section-title">Main</div>
                    <a class="sa-nav-item active" onclick="saNavigate('dashboard', this)" data-page="dashboard">
                        <i class="fas fa-th-large"></i> Dashboard
                    </a>
                </div>

                <!-- Management -->
                <div class="sa-nav-section">
                    <div class="sa-nav-section-title">Management</div>
                    <a class="sa-nav-item" onclick="saNavigate('users', this)" data-page="users">
                        <i class="fas fa-users-cog"></i> User Management
                        <span class="sa-nav-badge soon">SOON</span>
                    </a>
                    <a class="sa-nav-item" onclick="saNavigate('tiers', this)" data-page="tiers">
                        <i class="fas fa-layer-group"></i> Tier & Access Control
                        <span class="sa-nav-badge soon">SOON</span>
                    </a>
                    <a class="sa-nav-item" onclick="saNavigate('prompts', this)" data-page="prompts">
                        <i class="fas fa-file-alt"></i> Prompt Library
                        <span class="sa-nav-badge soon">SOON</span>
                    </a>
                </div>

                <!-- Analytics -->
                <div class="sa-nav-section">
                    <div class="sa-nav-section-title">Analytics</div>
                    <a class="sa-nav-item" onclick="saNavigate('analytics', this)" data-page="analytics">
                        <i class="fas fa-chart-bar"></i> Analytics & Reports
                        <span class="sa-nav-badge soon">SOON</span>
                    </a>
                    <a class="sa-nav-item" onclick="saNavigate('logs', this)" data-page="logs">
                        <i class="fas fa-scroll"></i> Activity Logs
                        <span class="sa-nav-badge soon">SOON</span>
                    </a>
                </div>

                <!-- System -->
                <div class="sa-nav-section">
                    <div class="sa-nav-section-title">System</div>
                    <a class="sa-nav-item" onclick="saNavigate('api', this)" data-page="api">
                        <i class="fas fa-plug"></i> API Management
                        <span class="sa-nav-badge soon">SOON</span>
                    </a>
                    <a class="sa-nav-item" onclick="saNavigate('settings', this)" data-page="settings">
                        <i class="fas fa-cog"></i> System Settings
                        <span class="sa-nav-badge soon">SOON</span>
                    </a>
                    <a class="sa-nav-item" onclick="saNavigate('backup', this)" data-page="backup">
                        <i class="fas fa-database"></i> Backup & Restore
                        <span class="sa-nav-badge soon">SOON</span>
                    </a>
                </div>
            </div>

            <div class="sa-sidebar-footer">
                <div class="sa-admin-badge">
                    <div class="admin-avatar"><i class="fas fa-crown"></i></div>
                    <div class="admin-info">
                        <span>Super Admin</span>
                        <small><i class="fas fa-circle" style="font-size:6px;margin-right:4px;"></i>Online</small>
                    </div>
                </div>
            </div>
        </nav>

        <!-- ── Main Content ── -->
        <main class="sa-main">
            <div class="sa-topbar">
                <div class="sa-topbar-left">
                    <h1 id="saPageTitle">Dashboard</h1>
                    <span class="page-indicator" id="saPageIndicator">Overview</span>
                </div>
                <div class="sa-topbar-right">
                    <a href="index.php" class="sa-topbar-btn">
                        <i class="fas fa-arrow-left"></i> Back to PM
                    </a>
                    <button class="sa-topbar-btn danger" onclick="saLogout()">
                        <i class="fas fa-sign-out-alt"></i> Lock
                    </button>
                </div>
            </div>

            <div class="sa-content">

                <!-- ══════════ PAGE: Dashboard ══════════ -->
                <div class="sa-page active" id="page-dashboard">
                    <div class="sa-stats-row">
                        <div class="sa-stat-card purple">
                            <div class="sa-stat-icon"><i class="fas fa-users"></i></div>
                            <div class="sa-stat-info">
                                <h4>0</h4>
                                <p>Total Users</p>
                            </div>
                        </div>
                        <div class="sa-stat-card cyan">
                            <div class="sa-stat-icon"><i class="fas fa-file-alt"></i></div>
                            <div class="sa-stat-info">
                                <h4>0</h4>
                                <p>Total Prompts</p>
                            </div>
                        </div>
                        <div class="sa-stat-card green">
                            <div class="sa-stat-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="sa-stat-info">
                                <h4>0</h4>
                                <p>Active Sessions</p>
                            </div>
                        </div>
                        <div class="sa-stat-card amber">
                            <div class="sa-stat-icon"><i class="fas fa-bolt"></i></div>
                            <div class="sa-stat-info">
                                <h4>0</h4>
                                <p>API Calls Today</p>
                            </div>
                        </div>
                    </div>

                    <div class="sa-section-card">
                        <div class="sa-section-card-header">
                            <h3><i class="fas fa-clock"></i> Recent Activity</h3>
                        </div>
                        <div class="sa-empty-state">
                            <i class="fas fa-inbox"></i>
                            <h4>No Activity Yet</h4>
                            <p>User activities will appear here once features are enabled.</p>
                        </div>
                    </div>

                    <div class="sa-section-card">
                        <div class="sa-section-card-header">
                            <h3><i class="fas fa-rocket"></i> Quick Actions</h3>
                        </div>
                        <div class="sa-feature-grid">
                            <div class="sa-feature-card">
                                <div class="feature-icon purple"><i class="fas fa-user-plus"></i></div>
                                <h4>Add User</h4>
                                <p>Register a new user account with assigned tier</p>
                                <span class="coming-soon-tag">Coming Soon</span>
                            </div>
                            <div class="sa-feature-card">
                                <div class="feature-icon cyan"><i class="fas fa-file-export"></i></div>
                                <h4>Export Data</h4>
                                <p>Export all prompts and user data</p>
                                <span class="coming-soon-tag">Coming Soon</span>
                            </div>
                            <div class="sa-feature-card">
                                <div class="feature-icon green"><i class="fas fa-sync-alt"></i></div>
                                <h4>Sync Settings</h4>
                                <p>Synchronize configurations across instances</p>
                                <span class="coming-soon-tag">Coming Soon</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ══════════ PAGE: User Management ══════════ -->
                <div class="sa-page" id="page-users">
                    <div class="sa-stats-row">
                        <div class="sa-stat-card purple">
                            <div class="sa-stat-icon"><i class="fas fa-users"></i></div>
                            <div class="sa-stat-info"><h4>0</h4><p>Total Users</p></div>
                        </div>
                        <div class="sa-stat-card green">
                            <div class="sa-stat-icon"><i class="fas fa-user-check"></i></div>
                            <div class="sa-stat-info"><h4>0</h4><p>Active Users</p></div>
                        </div>
                        <div class="sa-stat-card amber">
                            <div class="sa-stat-icon"><i class="fas fa-user-clock"></i></div>
                            <div class="sa-stat-info"><h4>0</h4><p>Pending Approval</p></div>
                        </div>
                    </div>
                    <div class="sa-section-card">
                        <div class="sa-section-card-header">
                            <h3><i class="fas fa-users-cog"></i> All Users</h3>
                        </div>
                        <table class="sa-table-placeholder">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Tier</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>sample_user</td>
                                    <td>user@example.com</td>
                                    <td><span class="sa-tier-badge pro">PRO</span></td>
                                    <td><span class="sa-status-dot online"></span>Online</td>
                                    <td>
                                        <button class="sa-action-btn edit"><i class="fas fa-edit"></i> Edit</button>
                                        <button class="sa-action-btn delete"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>demo_user</td>
                                    <td>demo@example.com</td>
                                    <td><span class="sa-tier-badge free">FREE</span></td>
                                    <td><span class="sa-status-dot offline"></span>Offline</td>
                                    <td>
                                        <button class="sa-action-btn edit"><i class="fas fa-edit"></i> Edit</button>
                                        <button class="sa-action-btn delete"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ══════════ PAGE: Tier & Access Control ══════════ -->
                <div class="sa-page" id="page-tiers">
                    <div class="sa-feature-grid">
                        <div class="sa-feature-card">
                            <div class="feature-icon green"><i class="fas fa-seedling"></i></div>
                            <h4>Free Tier</h4>
                            <p>Basic access with limited prompts and features</p>
                            <span class="coming-soon-tag">Configure Soon</span>
                        </div>
                        <div class="sa-feature-card">
                            <div class="feature-icon purple"><i class="fas fa-gem"></i></div>
                            <h4>Pro Tier</h4>
                            <p>Full prompt library, advanced features, priority support</p>
                            <span class="coming-soon-tag">Configure Soon</span>
                        </div>
                        <div class="sa-feature-card">
                            <div class="feature-icon amber"><i class="fas fa-crown"></i></div>
                            <h4>Enterprise Tier</h4>
                            <p>Unlimited access, custom integrations, dedicated support</p>
                            <span class="coming-soon-tag">Configure Soon</span>
                        </div>
                        <div class="sa-feature-card">
                            <div class="feature-icon cyan"><i class="fas fa-sliders-h"></i></div>
                            <h4>Permission Matrix</h4>
                            <p>Fine-grained control over feature access per tier</p>
                            <span class="coming-soon-tag">Configure Soon</span>
                        </div>
                    </div>
                </div>

                <!-- ══════════ PAGE: Prompt Library ══════════ -->
                <div class="sa-page" id="page-prompts">
                    <div class="sa-stats-row">
                        <div class="sa-stat-card purple">
                            <div class="sa-stat-icon"><i class="fas fa-file-alt"></i></div>
                            <div class="sa-stat-info"><h4>0</h4><p>Total Prompts</p></div>
                        </div>
                        <div class="sa-stat-card cyan">
                            <div class="sa-stat-icon"><i class="fas fa-globe"></i></div>
                            <div class="sa-stat-info"><h4>0</h4><p>Public Prompts</p></div>
                        </div>
                        <div class="sa-stat-card amber">
                            <div class="sa-stat-icon"><i class="fas fa-lock"></i></div>
                            <div class="sa-stat-info"><h4>0</h4><p>Private Prompts</p></div>
                        </div>
                    </div>
                    <div class="sa-section-card">
                        <div class="sa-section-card-header">
                            <h3><i class="fas fa-file-alt"></i> All Prompts</h3>
                        </div>
                        <div class="sa-empty-state">
                            <i class="fas fa-file-circle-plus"></i>
                            <h4>No Prompts Managed</h4>
                            <p>System-level prompt management will be available here.</p>
                        </div>
                    </div>
                </div>

                <!-- ══════════ PAGE: Analytics & Reports ══════════ -->
                <div class="sa-page" id="page-analytics">
                    <div class="sa-stats-row">
                        <div class="sa-stat-card cyan">
                            <div class="sa-stat-icon"><i class="fas fa-chart-line"></i></div>
                            <div class="sa-stat-info"><h4>0</h4><p>Daily Active Users</p></div>
                        </div>
                        <div class="sa-stat-card purple">
                            <div class="sa-stat-icon"><i class="fas fa-eye"></i></div>
                            <div class="sa-stat-info"><h4>0</h4><p>Page Views</p></div>
                        </div>
                        <div class="sa-stat-card green">
                            <div class="sa-stat-icon"><i class="fas fa-clock"></i></div>
                            <div class="sa-stat-info"><h4>0m</h4><p>Avg. Session</p></div>
                        </div>
                    </div>
                    <div class="sa-section-card">
                        <div class="sa-section-card-header">
                            <h3><i class="fas fa-chart-area"></i> Usage Charts</h3>
                        </div>
                        <div class="sa-empty-state">
                            <i class="fas fa-chart-pie"></i>
                            <h4>Analytics Dashboard</h4>
                            <p>Charts and reports will render here when analytics tracking is enabled.</p>
                        </div>
                    </div>
                </div>

                <!-- ══════════ PAGE: Activity Logs ══════════ -->
                <div class="sa-page" id="page-logs">
                    <div class="sa-section-card">
                        <div class="sa-section-card-header">
                            <h3><i class="fas fa-scroll"></i> System Logs</h3>
                        </div>
                        <table class="sa-table-placeholder">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="color:var(--text-muted);">—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td><span style="color:var(--text-muted);font-size:0.75rem;">No logs recorded yet</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ══════════ PAGE: API Management ══════════ -->
                <div class="sa-page" id="page-api">
                    <div class="sa-feature-grid">
                        <div class="sa-feature-card">
                            <div class="feature-icon purple"><i class="fas fa-key"></i></div>
                            <h4>API Keys</h4>
                            <p>Generate, rotate, and revoke API keys for integrations</p>
                            <span class="coming-soon-tag">Coming Soon</span>
                        </div>
                        <div class="sa-feature-card">
                            <div class="feature-icon cyan"><i class="fas fa-tachometer-alt"></i></div>
                            <h4>Rate Limits</h4>
                            <p>Configure rate limiting per tier and endpoint</p>
                            <span class="coming-soon-tag">Coming Soon</span>
                        </div>
                        <div class="sa-feature-card">
                            <div class="feature-icon green"><i class="fas fa-plug"></i></div>
                            <h4>Webhooks</h4>
                            <p>Set up webhook endpoints for event notifications</p>
                            <span class="coming-soon-tag">Coming Soon</span>
                        </div>
                        <div class="sa-feature-card">
                            <div class="feature-icon amber"><i class="fas fa-book"></i></div>
                            <h4>API Docs</h4>
                            <p>Auto-generated API documentation and playground</p>
                            <span class="coming-soon-tag">Coming Soon</span>
                        </div>
                    </div>
                </div>

                <!-- ══════════ PAGE: System Settings ══════════ -->
                <div class="sa-page" id="page-settings">
                    <div class="sa-feature-grid">
                        <div class="sa-feature-card">
                            <div class="feature-icon purple"><i class="fas fa-palette"></i></div>
                            <h4>Appearance</h4>
                            <p>Theme customization, branding, logo settings</p>
                            <span class="coming-soon-tag">Coming Soon</span>
                        </div>
                        <div class="sa-feature-card">
                            <div class="feature-icon cyan"><i class="fas fa-envelope"></i></div>
                            <h4>Email / SMTP</h4>
                            <p>Configure email templates and SMTP settings</p>
                            <span class="coming-soon-tag">Coming Soon</span>
                        </div>
                        <div class="sa-feature-card">
                            <div class="feature-icon green"><i class="fas fa-shield-alt"></i></div>
                            <h4>Security</h4>
                            <p>Password policies, 2FA settings, session management</p>
                            <span class="coming-soon-tag">Coming Soon</span>
                        </div>
                        <div class="sa-feature-card">
                            <div class="feature-icon red"><i class="fas fa-bell"></i></div>
                            <h4>Notifications</h4>
                            <p>Push notification settings and alert thresholds</p>
                            <span class="coming-soon-tag">Coming Soon</span>
                        </div>
                        <div class="sa-feature-card">
                            <div class="feature-icon amber"><i class="fas fa-globe"></i></div>
                            <h4>Localization</h4>
                            <p>Language packs, timezone, date/number formats</p>
                            <span class="coming-soon-tag">Coming Soon</span>
                        </div>
                        <div class="sa-feature-card">
                            <div class="feature-icon pink"><i class="fas fa-tools"></i></div>
                            <h4>Maintenance</h4>
                            <p>Maintenance mode toggle, scheduled downtime</p>
                            <span class="coming-soon-tag">Coming Soon</span>
                        </div>
                    </div>
                </div>

                <!-- ══════════ PAGE: Backup & Restore ══════════ -->
                <div class="sa-page" id="page-backup">
                    <div class="sa-feature-grid">
                        <div class="sa-feature-card">
                            <div class="feature-icon green"><i class="fas fa-cloud-upload-alt"></i></div>
                            <h4>Create Backup</h4>
                            <p>Full database and files backup with compression</p>
                            <span class="coming-soon-tag">Coming Soon</span>
                        </div>
                        <div class="sa-feature-card">
                            <div class="feature-icon cyan"><i class="fas fa-cloud-download-alt"></i></div>
                            <h4>Restore Backup</h4>
                            <p>Restore from a previous backup point</p>
                            <span class="coming-soon-tag">Coming Soon</span>
                        </div>
                        <div class="sa-feature-card">
                            <div class="feature-icon purple"><i class="fas fa-history"></i></div>
                            <h4>Backup History</h4>
                            <p>View and manage all previous backups</p>
                            <span class="coming-soon-tag">Coming Soon</span>
                        </div>
                        <div class="sa-feature-card">
                            <div class="feature-icon amber"><i class="fas fa-calendar-alt"></i></div>
                            <h4>Scheduled Backups</h4>
                            <p>Automated backup scheduling with retention policies</p>
                            <span class="coming-soon-tag">Coming Soon</span>
                        </div>
                    </div>
                </div>

            </div><!-- /.sa-content -->
        </main>
    </div><!-- /.sa-dashboard -->

    <!-- ═══════════════════════════════════════════════════════
         JAVASCRIPT
         ═══════════════════════════════════════════════════════ -->
    <script>
        const SA_PASSWORD = 'GL_Admin';

        /* ── Login ── */
        document.getElementById('saLoginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const input = document.getElementById('saPasswordInput');
            const errorEl = document.getElementById('saLoginError');
            const pw = input.value.trim();

            if (pw === SA_PASSWORD) {
                errorEl.classList.remove('show');
                document.getElementById('saLoginOverlay').classList.add('hidden');
                document.getElementById('saDashboard').classList.add('active');
                input.value = '';
            } else {
                errorEl.classList.add('show');
                input.value = '';
                input.focus();
            }
        });

        /* ── Toggle Password Visibility ── */
        function saTogglePassword() {
            const input = document.getElementById('saPasswordInput');
            const icon = document.getElementById('saEyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        /* ── Logout (re-lock) ── */
        function saLogout() {
            document.getElementById('saDashboard').classList.remove('active');
            document.getElementById('saLoginOverlay').classList.remove('hidden');
            document.getElementById('saPasswordInput').value = '';
            document.getElementById('saLoginError').classList.remove('show');
            setTimeout(() => document.getElementById('saPasswordInput').focus(), 100);
        }

        /* ── Page titles mapping ── */
        const SA_PAGE_TITLES = {
            dashboard:  { title: 'Dashboard',            indicator: 'Overview' },
            users:      { title: 'User Management',      indicator: 'Manage Users' },
            tiers:      { title: 'Tier & Access Control', indicator: 'Permissions' },
            prompts:    { title: 'Prompt Library',        indicator: 'All Prompts' },
            analytics:  { title: 'Analytics & Reports',   indicator: 'Insights' },
            logs:       { title: 'Activity Logs',         indicator: 'System Logs' },
            api:        { title: 'API Management',        indicator: 'Integrations' },
            settings:   { title: 'System Settings',       indicator: 'Configuration' },
            backup:     { title: 'Backup & Restore',      indicator: 'Data Safety' },
        };

        /* ── Sidebar Navigation ── */
        function saNavigate(page, el) {
            document.querySelectorAll('.sa-page').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.sa-nav-item').forEach(n => n.classList.remove('active'));

            const target = document.getElementById('page-' + page);
            if (target) target.classList.add('active');
            if (el) el.classList.add('active');

            const info = SA_PAGE_TITLES[page];
            if (info) {
                document.getElementById('saPageTitle').textContent = info.title;
                document.getElementById('saPageIndicator').textContent = info.indicator;
            }
        }

        /* ── Auto-focus password input ── */
        window.addEventListener('load', () => {
            document.getElementById('saPasswordInput').focus();
        });
    </script>
</body>
</html>
