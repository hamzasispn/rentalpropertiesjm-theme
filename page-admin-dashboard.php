<?php
/**
 * Template Name: Admin Panel
 */

if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_redirect(home_url('/admin-login'));
    exit;
}

$current_user = wp_get_current_user();
$logo         = get_option('mytheme_logo');
$rest_url     = esc_url_raw(rest_url());
$nonce        = wp_create_nonce('wp_rest');
$categories   = get_categories(['hide_empty' => false]);
$res_cats     = get_terms(['taxonomy' => 'resource_category', 'hide_empty' => false]);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <style>
        :root {
            --gold: #eab308;
            --gold-dark: #ca8a04;
            --gold-glow: rgba(234,179,8,0.15);
            --bg: #080d1a;
            --surface: #0f1729;
            --surface-2: #162035;
            --surface-3: #1e2d47;
            --border: rgba(234,179,8,0.12);
            --border-subtle: rgba(255,255,255,0.06);
            --text: #f1f5f9;
            --text-muted: #64748b;
            --text-dim: #94a3b8;
            --green: #22c55e;
            --red: #ef4444;
            --blue: #3b82f6;
            --sidebar-w: 240px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
            line-height: 1.5;
        }

        /* ── Sidebar ── */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            z-index: 100;
            overflow: hidden;
        }
        .sidebar-logo {
            padding: 20px 20px 16px;
            border-bottom: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-logo img { height: 36px; object-fit: contain; filter: brightness(0) invert(1); }
        .sidebar-logo-text {
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.01em;
            line-height: 1.2;
        }
        .sidebar-logo-sub {
            font-size: 10px;
            color: var(--gold);
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .sidebar-nav { flex: 1; padding: 12px 10px; overflow-y: auto; }
        .nav-section-label {
            font-size: 10px;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 8px 10px 4px;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 8px;
            cursor: pointer;
            color: var(--text-dim);
            font-size: 13px;
            font-weight: 500;
            transition: all 0.15s;
            text-decoration: none;
            margin-bottom: 1px;
            border: 1px solid transparent;
        }
        .nav-item:hover { background: var(--surface-2); color: var(--text); }
        .nav-item.active {
            background: var(--gold-glow);
            border-color: var(--border);
            color: var(--gold);
            font-weight: 600;
        }
        .nav-item svg { flex-shrink: 0; opacity: 0.8; }
        .nav-item.active svg { opacity: 1; }
        .nav-badge {
            margin-left: auto;
            background: var(--gold);
            color: #000;
            font-size: 10px;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 100px;
            min-width: 20px;
            text-align: center;
        }
        .sidebar-footer {
            padding: 14px 20px;
            border-top: 1px solid var(--border-subtle);
        }
        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-avatar {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: var(--gold-glow);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            color: var(--gold);
            flex-shrink: 0;
        }
        .sidebar-user-name { font-size: 13px; font-weight: 600; color: var(--text); }
        .sidebar-user-role { font-size: 11px; color: var(--text-muted); }
        .btn-logout {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            background: rgba(239,68,68,0.08);
            border: 1px solid rgba(239,68,68,0.2);
            color: #fca5a5;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s;
            cursor: pointer;
            width: 100%;
            justify-content: center;
        }
        .btn-logout:hover { background: rgba(239,68,68,0.15); }

        /* ── Main content ── */
        .main {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .topbar {
            height: 56px;
            background: var(--surface);
            border-bottom: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            padding: 0 28px;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .topbar-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.02em;
        }
        .topbar-actions { margin-left: auto; display: flex; align-items: center; gap: 10px; }
        .btn-site {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: var(--surface-2);
            border: 1px solid var(--border-subtle);
            color: var(--text-dim);
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s;
        }
        .btn-site:hover { background: var(--surface-3); color: var(--text); }

        /* ── Content area ── */
        .content { flex: 1; padding: 28px; }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* ── Stats Grid ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 14px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border-subtle);
            border-radius: 12px;
            padding: 18px 20px;
            position: relative;
            overflow: hidden;
            transition: border-color 0.2s;
        }
        .stat-card:hover { border-color: var(--border); }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 60px; height: 60px;
            background: radial-gradient(circle, var(--gold-glow) 0%, transparent 70%);
            border-radius: 0 12px 0 60px;
        }
        .stat-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -0.03em;
            line-height: 1;
            margin-bottom: 4px;
        }
        .stat-value.gold { color: var(--gold); }
        .stat-value.green { color: var(--green); }
        .stat-value.red { color: var(--red); }
        .stat-sub { font-size: 11px; color: var(--text-muted); }

        /* ── Section header ── */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            gap: 12px;
        }
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.02em;
        }
        .section-subtitle { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

        /* ── Property Cards ── */
        .property-list { display: flex; flex-direction: column; gap: 12px; }
        .property-card {
            background: var(--surface);
            border: 1px solid var(--border-subtle);
            border-radius: 12px;
            padding: 16px;
            display: flex;
            gap: 14px;
            align-items: flex-start;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .property-card:hover { border-color: var(--border); }
        .property-thumb {
            width: 80px; height: 64px;
            border-radius: 8px;
            object-fit: cover;
            background: var(--surface-2);
            flex-shrink: 0;
            border: 1px solid var(--border-subtle);
        }
        .property-thumb-placeholder {
            width: 80px; height: 64px;
            border-radius: 8px;
            background: var(--surface-2);
            border: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .property-info { flex: 1; min-width: 0; }
        .property-title {
            font-weight: 600;
            color: var(--text);
            font-size: 14px;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .property-meta { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 8px; }
        .property-meta-item {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            color: var(--text-muted);
        }
        .property-author {
            font-size: 11px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .property-actions { display: flex; gap: 8px; flex-shrink: 0; align-items: flex-start; }

        /* ── Status badges ── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .badge-pending { background: rgba(234,179,8,0.12); color: #fde047; border: 1px solid rgba(234,179,8,0.25); }
        .badge-published { background: rgba(34,197,94,0.1); color: #86efac; border: 1px solid rgba(34,197,94,0.2); }
        .badge-rejected { background: rgba(239,68,68,0.1); color: #fca5a5; border: 1px solid rgba(239,68,68,0.2); }
        .badge-draft { background: rgba(100,116,139,0.12); color: #94a3b8; border: 1px solid rgba(100,116,139,0.2); }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s;
            white-space: nowrap;
        }
        .btn-approve {
            background: rgba(34,197,94,0.1);
            border-color: rgba(34,197,94,0.25);
            color: #86efac;
        }
        .btn-approve:hover { background: rgba(34,197,94,0.18); }
        .btn-reject {
            background: rgba(239,68,68,0.1);
            border-color: rgba(239,68,68,0.25);
            color: #fca5a5;
        }
        .btn-reject:hover { background: rgba(239,68,68,0.18); }
        .btn-primary {
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: #000;
            font-weight: 700;
            border: none;
        }
        .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); box-shadow: 0 4px 15px rgba(234,179,8,0.3); }
        .btn-secondary {
            background: var(--surface-2);
            border-color: var(--border-subtle);
            color: var(--text-dim);
        }
        .btn-secondary:hover { background: var(--surface-3); color: var(--text); }
        .btn-danger {
            background: rgba(239,68,68,0.08);
            border-color: rgba(239,68,68,0.2);
            color: #fca5a5;
        }
        .btn-danger:hover { background: rgba(239,68,68,0.15); }
        .btn-sm { padding: 5px 10px; font-size: 11px; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }

        /* ── Forms ── */
        .form-card {
            background: var(--surface);
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            padding: 24px;
        }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.07em;
            text-transform: uppercase;
            margin-bottom: 7px;
        }
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            background: var(--surface-2);
            border: 1px solid var(--border-subtle);
            color: var(--text);
            font-size: 14px;
            padding: 10px 14px;
            border-radius: 8px;
            outline: none;
            font-family: inherit;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color: rgba(234,179,8,0.4);
            box-shadow: 0 0 0 3px rgba(234,179,8,0.08);
        }
        .form-input::placeholder,
        .form-textarea::placeholder { color: #475569; }
        .form-textarea { resize: vertical; min-height: 120px; line-height: 1.6; }
        .form-select option { background: #1e2d47; }

        /* ── Upload zone ── */
        .upload-zone {
            border: 2px dashed var(--border);
            border-radius: 10px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--surface-2);
            position: relative;
        }
        .upload-zone:hover, .upload-zone.drag-over {
            border-color: var(--gold);
            background: var(--gold-glow);
        }
        .upload-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }
        .upload-icon { color: var(--text-muted); margin-bottom: 8px; }
        .upload-label { font-size: 13px; color: var(--text-dim); }
        .upload-hint { font-size: 11px; color: var(--text-muted); margin-top: 4px; }
        .upload-preview {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            background: var(--surface-2);
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            margin-top: 10px;
        }
        .upload-preview img { width: 48px; height: 36px; object-fit: cover; border-radius: 4px; }
        .upload-preview-name { font-size: 12px; color: var(--text-dim); flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        /* ── Table ── */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th {
            text-align: left;
            font-size: 10px;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-subtle);
        }
        .data-table td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            font-size: 13px;
            color: var(--text-dim);
            vertical-align: middle;
        }
        .data-table tr:hover td { background: rgba(255,255,255,0.02); }
        .data-table-title { color: var(--text); font-weight: 500; }

        /* ── Rejection modal ── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 28px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.5);
        }
        .modal-title { font-size: 17px; font-weight: 700; color: var(--text); margin-bottom: 6px; }
        .modal-sub { font-size: 13px; color: var(--text-muted); margin-bottom: 20px; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }

        /* ── Toast ── */
        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
        }
        .toast {
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
            animation: slideIn 0.2s ease-out;
            max-width: 320px;
        }
        .toast-success { background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.3); color: #86efac; }
        .toast-error { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5; }
        @keyframes slideIn { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }

        /* ── Loading ── */
        .loading-spinner {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            color: var(--text-muted);
            gap: 10px;
            font-size: 13px;
        }
        .spinner {
            width: 18px; height: 18px;
            border: 2px solid var(--border-subtle);
            border-top-color: var(--gold);
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Empty state ── */
        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: var(--text-muted);
        }
        .empty-state svg { margin: 0 auto 12px; opacity: 0.3; display: block; }
        .empty-state p { font-size: 13px; }

        /* ── Pagination ── */
        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 20px;
        }
        .page-btn {
            width: 32px; height: 32px;
            border-radius: 7px;
            border: 1px solid var(--border-subtle);
            background: var(--surface-2);
            color: var(--text-dim);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
        }
        .page-btn:hover, .page-btn.active { background: var(--gold-glow); border-color: var(--border); color: var(--gold); }
        .page-btn:disabled { opacity: 0.4; cursor: not-allowed; }

        /* ── Tabs filter ── */
        .tab-filter { display: flex; gap: 4px; margin-bottom: 20px; }
        .tab-filter-btn {
            padding: 6px 16px;
            border-radius: 8px;
            border: 1px solid var(--border-subtle);
            background: transparent;
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
        }
        .tab-filter-btn:hover { background: var(--surface-2); color: var(--text-dim); }
        .tab-filter-btn.active { background: var(--gold-glow); border-color: var(--border); color: var(--gold); }

        /* ── Richtext toolbar ── */
        .editor-toolbar {
            display: flex;
            gap: 4px;
            padding: 8px 12px;
            background: var(--surface-2);
            border: 1px solid var(--border-subtle);
            border-bottom: none;
            border-radius: 8px 8px 0 0;
        }
        .editor-btn {
            width: 28px; height: 28px;
            border-radius: 5px;
            border: 1px solid transparent;
            background: transparent;
            color: var(--text-dim);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            transition: all 0.1s;
        }
        .editor-btn:hover { background: var(--surface-3); border-color: var(--border-subtle); color: var(--text); }
        .editor-content {
            border-radius: 0 0 8px 8px !important;
            border-top: none !important;
        }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.25s; }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; }
            .form-grid { grid-template-columns: 1fr; }
            .mobile-toggle {
                display: flex !important;
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 200;
            }
        }
        .mobile-toggle {
            display: none;
            width: 48px; height: 48px;
            background: var(--gold);
            border-radius: 50%;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(234,179,8,0.4);
            border: none;
        }
    </style>
</head>
<body x-data="adminPanel()" x-init="init()">

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Rejection modal -->
<div class="modal-overlay" x-show="rejectModal.open" x-cloak>
    <div class="modal">
        <div class="modal-title">Reject Property</div>
        <p class="modal-sub">Provide a reason so the owner can fix and resubmit (optional).</p>
        <textarea class="form-textarea" x-model="rejectModal.reason" placeholder="e.g. Incorrect information, missing photos, wrong pricing..." style="min-height:100px"></textarea>
        <div class="modal-actions">
            <button class="btn btn-secondary" @click="rejectModal.open = false">Cancel</button>
            <button class="btn btn-reject" @click="confirmReject()" :disabled="rejectModal.loading">
                <span x-show="!rejectModal.loading">Reject Property</span>
                <span x-show="rejectModal.loading" class="spinner" style="width:14px;height:14px;margin:0"></span>
            </button>
        </div>
    </div>
</div>

<!-- Mobile toggle -->
<button class="mobile-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">
    <svg width="20" height="20" fill="none" stroke="#000" stroke-width="2.5" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
</button>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <?php if ($logo): ?>
            <img src="<?php echo esc_url($logo); ?>" alt="Logo">
        <?php else: ?>
            <div style="width:32px;height:32px;background:var(--gold);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:#000;flex-shrink:0;">A</div>
        <?php endif; ?>
        <div>
            <div class="sidebar-logo-text"><?php bloginfo('name'); ?></div>
            <div class="sidebar-logo-sub">Admin Portal</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <button class="nav-item" :class="{active: tab === 'overview'}" @click="setTab('overview')">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            Dashboard
        </button>

        <div class="nav-section-label" style="margin-top:8px">Properties</div>
        <button class="nav-item" :class="{active: tab === 'pending'}" @click="setTab('pending')">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Pending Approval
            <span class="nav-badge" x-show="stats.pending_properties > 0" x-text="stats.pending_properties"></span>
        </button>
        <button class="nav-item" :class="{active: tab === 'all-properties'}" @click="setTab('all-properties')">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            All Properties
        </button>

        <div class="nav-section-label" style="margin-top:8px">Content</div>
        <button class="nav-item" :class="{active: tab === 'add-blog'}" @click="setTab('add-blog')">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            Add Blog Post
        </button>
        <button class="nav-item" :class="{active: tab === 'blogs'}" @click="setTab('blogs')">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            All Blogs
        </button>
        <button class="nav-item" :class="{active: tab === 'add-resource'}" @click="setTab('add-resource')">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Upload Resource
        </button>
        <button class="nav-item" :class="{active: tab === 'resources'}" @click="setTab('resources')">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            All Resources
        </button>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?php echo strtoupper(substr($current_user->display_name, 0, 1)); ?></div>
            <div>
                <div class="sidebar-user-name"><?php echo esc_html($current_user->display_name); ?></div>
                <div class="sidebar-user-role">Administrator</div>
            </div>
        </div>
        <a href="<?php echo esc_url(wp_logout_url(home_url('/admin-login'))); ?>" class="btn-logout" style="margin-top:12px">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Sign Out
        </a>
    </div>
</aside>

<!-- Main -->
<div class="main">
    <div class="topbar">
        <div class="topbar-title" x-text="tabTitle()"></div>
        <div class="topbar-actions">
            <a href="<?php echo esc_url(home_url('/')); ?>" target="_blank" class="btn-site">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                View Site
            </a>
        </div>
    </div>

    <div class="content">

        <!-- ══ OVERVIEW ══ -->
        <div class="tab-panel" :class="{active: tab === 'overview'}">
            <div class="stats-grid" x-show="!statsLoading">
                <div class="stat-card">
                    <div class="stat-label">Live Properties</div>
                    <div class="stat-value green" x-text="stats.live_properties ?? '—'"></div>
                    <div class="stat-sub">Published & approved</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pending Review</div>
                    <div class="stat-value gold" x-text="stats.pending_properties ?? '—'"></div>
                    <div class="stat-sub">Awaiting your approval</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Blog Posts</div>
                    <div class="stat-value" x-text="stats.total_blogs ?? '—'"></div>
                    <div class="stat-sub">Published articles</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Resources</div>
                    <div class="stat-value" x-text="stats.total_resources ?? '—'"></div>
                    <div class="stat-sub">PDFs & docs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value" x-text="stats.total_users ?? '—'"></div>
                    <div class="stat-sub">Registered accounts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Active Subs</div>
                    <div class="stat-value gold" x-text="stats.active_subscriptions ?? '—'"></div>
                    <div class="stat-sub">Paying subscribers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">New Users (30d)</div>
                    <div class="stat-value" x-text="stats.recent_signups ?? '—'"></div>
                    <div class="stat-sub">Last 30 days</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Page Views (30d)</div>
                    <div class="stat-value" x-text="stats.page_views_30d ?? '—'"></div>
                    <div class="stat-sub">Property analytics</div>
                </div>
            </div>
            <div class="loading-spinner" x-show="statsLoading"><div class="spinner"></div> Loading stats…</div>

            <!-- Pending properties quick view -->
            <div class="section-header" x-show="!statsLoading && (stats.pending_properties ?? 0) > 0">
                <div>
                    <div class="section-title">Needs Your Attention</div>
                    <div class="section-subtitle" x-text="(stats.pending_properties ?? 0) + ' properties awaiting approval'"></div>
                </div>
                <button class="btn btn-primary btn-sm" @click="setTab('pending')">Review All</button>
            </div>

            <template x-if="!statsLoading && pendingProperties.length > 0">
                <div class="property-list">
                    <template x-for="prop in pendingProperties.slice(0,3)" :key="prop.id">
                        <div class="property-card">
                            <template x-if="prop.image">
                                <img :src="prop.image" class="property-thumb" alt="">
                            </template>
                            <template x-if="!prop.image">
                                <div class="property-thumb-placeholder">
                                    <svg width="22" height="22" fill="none" stroke="#475569" stroke-width="1.5" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                                </div>
                            </template>
                            <div class="property-info">
                                <div class="property-title" x-text="prop.title"></div>
                                <div class="property-meta">
                                    <span class="property-meta-item" x-show="prop.price">
                                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                        JMD <span x-text="parseInt(prop.price).toLocaleString()"></span>
                                    </span>
                                    <span class="property-meta-item" x-show="prop.city" x-text="prop.city"></span>
                                </div>
                                <div class="property-author">
                                    <svg width="11" height="11" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/></svg>
                                    <span x-text="prop.author_name"></span>
                                    <span x-text="' · ' + prop.date"></span>
                                </div>
                            </div>
                            <div class="property-actions">
                                <button class="btn btn-approve btn-sm" @click="approveProperty(prop.id)">Approve</button>
                                <button class="btn btn-reject btn-sm" @click="openRejectModal(prop.id)">Reject</button>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- ══ PENDING PROPERTIES ══ -->
        <div class="tab-panel" :class="{active: tab === 'pending'}">
            <div class="section-header">
                <div>
                    <div class="section-title">Pending Properties</div>
                    <div class="section-subtitle">Review and approve or reject user-submitted listings</div>
                </div>
                <button class="btn btn-secondary btn-sm" @click="loadPending()">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                    Refresh
                </button>
            </div>

            <div class="loading-spinner" x-show="pendingLoading"><div class="spinner"></div> Loading…</div>

            <div class="property-list" x-show="!pendingLoading">
                <template x-for="prop in pendingProperties" :key="prop.id">
                    <div class="property-card">
                        <template x-if="prop.image">
                            <img :src="prop.image" class="property-thumb" alt="">
                        </template>
                        <template x-if="!prop.image">
                            <div class="property-thumb-placeholder">
                                <svg width="22" height="22" fill="none" stroke="#475569" stroke-width="1.5" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                            </div>
                        </template>
                        <div class="property-info">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                                <div class="property-title" style="margin:0" x-text="prop.title"></div>
                                <span class="badge badge-pending">Pending</span>
                            </div>
                            <div class="property-meta">
                                <span class="property-meta-item" x-show="prop.price">
                                    JMD <span x-text="parseInt(prop.price).toLocaleString()"></span>
                                </span>
                                <span class="property-meta-item" x-show="prop.city" x-text="prop.city"></span>
                                <span class="property-meta-item" x-show="prop.address" x-text="prop.address"></span>
                            </div>
                            <div class="property-author">
                                Submitted by: <strong x-text="prop.author_name" style="color:var(--text-dim);margin-left:4px"></strong>
                                &nbsp;(<span x-text="prop.author_email"></span>)
                                <span x-text="' · ' + prop.date"></span>
                            </div>
                        </div>
                        <div class="property-actions" style="flex-direction:column;gap:6px">
                            <a :href="prop.permalink" target="_blank" class="btn btn-secondary btn-sm">Preview</a>
                            <button class="btn btn-approve btn-sm" @click="approveProperty(prop.id)">Approve</button>
                            <button class="btn btn-reject btn-sm" @click="openRejectModal(prop.id)">Reject</button>
                        </div>
                    </div>
                </template>
                <div class="empty-state" x-show="!pendingLoading && pendingProperties.length === 0">
                    <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <p>No pending properties — all caught up!</p>
                </div>
            </div>
        </div>

        <!-- ══ ALL PROPERTIES ══ -->
        <div class="tab-panel" :class="{active: tab === 'all-properties'}">
            <div class="section-header">
                <div>
                    <div class="section-title">All Properties</div>
                    <div class="section-subtitle">Complete property listing inventory</div>
                </div>
            </div>

            <div class="tab-filter">
                <button class="tab-filter-btn" :class="{active: propFilter === 'all'}" @click="propFilter='all'; loadAllProperties()">All</button>
                <button class="tab-filter-btn" :class="{active: propFilter === 'publish'}" @click="propFilter='publish'; loadAllProperties()">Published</button>
                <button class="tab-filter-btn" :class="{active: propFilter === 'pending'}" @click="propFilter='pending'; loadAllProperties()">Pending</button>
                <button class="tab-filter-btn" :class="{active: propFilter === 'draft'}" @click="propFilter='draft'; loadAllProperties()">Rejected</button>
            </div>

            <div class="loading-spinner" x-show="allPropsLoading"><div class="spinner"></div> Loading…</div>

            <div style="background:var(--surface);border:1px solid var(--border-subtle);border-radius:12px;overflow:hidden" x-show="!allPropsLoading">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Status</th>
                            <th>Price</th>
                            <th>Owner</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="prop in allProperties" :key="prop.id">
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px">
                                        <img x-show="prop.image" :src="prop.image" style="width:40px;height:32px;object-fit:cover;border-radius:5px;border:1px solid var(--border-subtle)" alt="">
                                        <div>
                                            <div class="data-table-title" x-text="prop.title"></div>
                                            <div style="font-size:11px;color:var(--text-muted)" x-text="prop.city"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" :class="{'badge-published': prop.status==='publish','badge-pending': prop.status==='pending','badge-rejected': prop.status==='draft'}" x-text="prop.status === 'publish' ? 'Live' : prop.status === 'pending' ? 'Pending' : 'Rejected'"></span>
                                </td>
                                <td x-text="prop.price ? 'JMD ' + parseInt(prop.price).toLocaleString() : '—'"></td>
                                <td x-text="prop.author_name"></td>
                                <td x-text="prop.date"></td>
                                <td>
                                    <div style="display:flex;gap:6px">
                                        <a :href="prop.permalink" target="_blank" class="btn btn-secondary btn-sm">View</a>
                                        <button class="btn btn-approve btn-sm" x-show="prop.status !== 'publish'" @click="approveProperty(prop.id)">Approve</button>
                                        <button class="btn btn-reject btn-sm" x-show="prop.status !== 'draft'" @click="openRejectModal(prop.id)">Reject</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!allPropsLoading && allProperties.length === 0">
                            <td colspan="6" style="text-align:center;padding:32px;color:var(--text-muted)">No properties found</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ══ ADD BLOG ══ -->
        <div class="tab-panel" :class="{active: tab === 'add-blog'}">
            <div class="section-header">
                <div>
                    <div class="section-title">New Blog Post</div>
                    <div class="section-subtitle">Write and publish an article to the blog</div>
                </div>
            </div>

            <div class="form-card">
                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Post Title *</label>
                        <input type="text" class="form-input" x-model="blogForm.title" placeholder="Enter a compelling title…">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select class="form-select" x-model="blogForm.category_id">
                            <option value="">No category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat->term_id; ?>"><?php echo esc_html($cat->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" x-model="blogForm.status">
                            <option value="publish">Publish Now</option>
                            <option value="draft">Save as Draft</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Excerpt / Summary</label>
                        <textarea class="form-textarea" x-model="blogForm.excerpt" placeholder="Short summary shown in blog listings…" style="min-height:80px"></textarea>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Content *</label>
                        <div class="editor-toolbar">
                            <button type="button" class="editor-btn" title="Bold" onclick="execFormat('bold')"><strong>B</strong></button>
                            <button type="button" class="editor-btn" title="Italic" onclick="execFormat('italic')"><em>I</em></button>
                            <button type="button" class="editor-btn" title="Underline" onclick="execFormat('underline')"><u>U</u></button>
                            <button type="button" class="editor-btn" title="H2" onclick="execFormat('formatBlock','h2')" style="font-size:10px;width:auto;padding:0 6px">H2</button>
                            <button type="button" class="editor-btn" title="H3" onclick="execFormat('formatBlock','h3')" style="font-size:10px;width:auto;padding:0 6px">H3</button>
                            <button type="button" class="editor-btn" title="Unordered List" onclick="execFormat('insertUnorderedList')">• –</button>
                            <button type="button" class="editor-btn" title="Link" onclick="insertLink()">🔗</button>
                        </div>
                        <div id="blogEditor" class="form-textarea editor-content" contenteditable="true" style="min-height:240px;border-radius:0 0 8px 8px;border-top:none"
                             placeholder="Write your article here…"
                             oninput="document.getElementById('blogContentHidden').value = this.innerHTML"></div>
                        <input type="hidden" id="blogContentHidden" x-model="blogForm.content">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Featured Image</label>
                        <div class="upload-zone" @dragover.prevent="$el.classList.add('drag-over')" @dragleave="$el.classList.remove('drag-over')" @drop.prevent="handleImageDrop($event, 'blog')">
                            <input type="file" accept="image/*" @change="uploadBlogImage($event)" x-show="!blogForm.image_url">
                            <div class="upload-icon">
                                <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            </div>
                            <div class="upload-label">Click or drag image here</div>
                            <div class="upload-hint">JPG, PNG, WebP · Max 5MB</div>
                        </div>
                        <div class="upload-preview" x-show="blogForm.image_url">
                            <img :src="blogForm.image_url" alt="">
                            <span class="upload-preview-name" x-text="blogForm.image_name"></span>
                            <button class="btn btn-danger btn-sm" @click="blogForm.image_url='';blogForm.image_id=0">Remove</button>
                        </div>
                    </div>
                </div>

                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px">
                    <button class="btn btn-secondary" @click="resetBlogForm()">Reset</button>
                    <button class="btn btn-primary" @click="submitBlog()" :disabled="blogSubmitting">
                        <span x-show="!blogSubmitting">Publish Post</span>
                        <span x-show="blogSubmitting" style="display:flex;align-items:center;gap:6px"><span class="spinner" style="width:14px;height:14px;border-width:2px;border-top-color:#000"></span> Publishing…</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ ALL BLOGS ══ -->
        <div class="tab-panel" :class="{active: tab === 'blogs'}">
            <div class="section-header">
                <div class="section-title">Blog Posts</div>
                <button class="btn btn-primary btn-sm" @click="setTab('add-blog')">+ New Post</button>
            </div>

            <div class="loading-spinner" x-show="blogsLoading"><div class="spinner"></div> Loading…</div>

            <div style="background:var(--surface);border:1px solid var(--border-subtle);border-radius:12px;overflow:hidden" x-show="!blogsLoading">
                <table class="data-table">
                    <thead>
                        <tr><th>Title</th><th>Status</th><th>Date</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <template x-for="post in blogPosts" :key="post.id">
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px">
                                        <img x-show="post.image" :src="post.image" style="width:40px;height:32px;object-fit:cover;border-radius:5px;border:1px solid var(--border-subtle)" alt="">
                                        <div>
                                            <div class="data-table-title" x-text="post.title"></div>
                                            <div style="font-size:11px;color:var(--text-muted)" x-text="post.excerpt"></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge" :class="post.status === 'publish' ? 'badge-published' : 'badge-draft'" x-text="post.status === 'publish' ? 'Published' : 'Draft'"></span></td>
                                <td x-text="post.date"></td>
                                <td>
                                    <div style="display:flex;gap:6px">
                                        <a :href="post.permalink" target="_blank" class="btn btn-secondary btn-sm">View</a>
                                        <button class="btn btn-danger btn-sm" @click="deleteBlog(post.id)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!blogsLoading && blogPosts.length === 0">
                            <td colspan="4" style="text-align:center;padding:32px;color:var(--text-muted)">No blog posts yet</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ══ ADD RESOURCE ══ -->
        <div class="tab-panel" :class="{active: tab === 'add-resource'}">
            <div class="section-header">
                <div>
                    <div class="section-title">Upload Resource</div>
                    <div class="section-subtitle">Add PDFs, guides, or documents for users to download</div>
                </div>
            </div>

            <div class="form-card">
                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Resource Title *</label>
                        <input type="text" class="form-input" x-model="resourceForm.title" placeholder="e.g. Jamaica Property Buying Guide 2025">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select class="form-select" x-model="resourceForm.category_id">
                            <option value="">No category</option>
                            <?php foreach ($res_cats as $cat): ?>
                                <option value="<?php echo $cat->term_id; ?>"><?php echo esc_html($cat->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">File Type</label>
                        <select class="form-select" x-model="resourceForm.file_type">
                            <option value="pdf">PDF</option>
                            <option value="doc">Word Document</option>
                            <option value="xls">Spreadsheet</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Description</label>
                        <textarea class="form-textarea" x-model="resourceForm.description" placeholder="Describe what this resource contains…" style="min-height:100px"></textarea>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">PDF / Document File *</label>
                        <div class="upload-zone" @dragover.prevent="$el.classList.add('drag-over')" @dragleave="$el.classList.remove('drag-over')">
                            <input type="file" accept=".pdf,.doc,.docx,.xls,.xlsx" @change="uploadResourceFile($event)" x-show="!resourceForm.file_url">
                            <div class="upload-icon">
                                <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                            </div>
                            <div class="upload-label">Click or drag PDF/document here</div>
                            <div class="upload-hint">PDF, DOC, DOCX, XLS · Max 20MB</div>
                        </div>
                        <div class="upload-preview" x-show="resourceForm.file_url">
                            <svg width="32" height="32" fill="none" stroke="#eab308" stroke-width="1.5" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <span class="upload-preview-name" x-text="resourceForm.file_name"></span>
                            <a :href="resourceForm.file_url" target="_blank" class="btn btn-secondary btn-sm">Preview</a>
                            <button class="btn btn-danger btn-sm" @click="resourceForm.file_url='';resourceForm.file_name=''">Remove</button>
                        </div>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Thumbnail Image (optional)</label>
                        <div class="upload-zone">
                            <input type="file" accept="image/*" @change="uploadResourceThumbnail($event)" x-show="!resourceForm.image_url">
                            <div class="upload-icon">
                                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            </div>
                            <div class="upload-label">Click to add cover image</div>
                        </div>
                        <div class="upload-preview" x-show="resourceForm.image_url">
                            <img :src="resourceForm.image_url" alt="">
                            <span class="upload-preview-name" x-text="resourceForm.image_name"></span>
                            <button class="btn btn-danger btn-sm" @click="resourceForm.image_url='';resourceForm.image_id=0">Remove</button>
                        </div>
                    </div>
                </div>

                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px">
                    <button class="btn btn-secondary" @click="resetResourceForm()">Reset</button>
                    <button class="btn btn-primary" @click="submitResource()" :disabled="resourceSubmitting">
                        <span x-show="!resourceSubmitting">Upload Resource</span>
                        <span x-show="resourceSubmitting" style="display:flex;align-items:center;gap:6px"><span class="spinner" style="width:14px;height:14px;border-width:2px;border-top-color:#000"></span> Uploading…</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ ALL RESOURCES ══ -->
        <div class="tab-panel" :class="{active: tab === 'resources'}">
            <div class="section-header">
                <div class="section-title">Resources</div>
                <button class="btn btn-primary btn-sm" @click="setTab('add-resource')">+ Upload</button>
            </div>

            <div class="loading-spinner" x-show="resourcesLoading"><div class="spinner"></div> Loading…</div>

            <div style="background:var(--surface);border:1px solid var(--border-subtle);border-radius:12px;overflow:hidden" x-show="!resourcesLoading">
                <table class="data-table">
                    <thead>
                        <tr><th>Title</th><th>Type</th><th>Status</th><th>Date</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <template x-for="res in resourceList" :key="res.id">
                            <tr>
                                <td>
                                    <div class="data-table-title" x-text="res.title"></div>
                                    <div style="font-size:11px;color:var(--text-muted)" x-text="res.file_url ? 'Has file attached' : 'No file'"></div>
                                </td>
                                <td><span class="badge badge-draft" x-text="(res.file_type || 'pdf').toUpperCase()"></span></td>
                                <td><span class="badge" :class="res.status === 'publish' ? 'badge-published' : 'badge-draft'" x-text="res.status === 'publish' ? 'Live' : 'Draft'"></span></td>
                                <td x-text="res.date"></td>
                                <td>
                                    <div style="display:flex;gap:6px">
                                        <a :href="res.permalink" target="_blank" class="btn btn-secondary btn-sm">View</a>
                                        <a x-show="res.file_url" :href="res.file_url" target="_blank" class="btn btn-secondary btn-sm">Download</a>
                                        <button class="btn btn-danger btn-sm" @click="deleteResource(res.id)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!resourcesLoading && resourceList.length === 0">
                            <td colspan="5" style="text-align:center;padding:32px;color:var(--text-muted)">No resources yet</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /content -->
</div><!-- /main -->

<script>
function execFormat(cmd, val) {
    document.execCommand(cmd, false, val || null);
    document.getElementById('blogContentHidden').value = document.getElementById('blogEditor').innerHTML;
    document.getElementById('blogEditor').focus();
}
function insertLink() {
    const url = prompt('Enter URL:');
    if (url) execFormat('createLink', url);
}

function adminPanel() {
    return {
        tab: 'overview',
        stats: {},
        statsLoading: true,
        pendingProperties: [],
        pendingLoading: false,
        allProperties: [],
        allPropsLoading: false,
        propFilter: 'all',
        blogPosts: [],
        blogsLoading: false,
        resourceList: [],
        resourcesLoading: false,
        rejectModal: { open: false, propertyId: null, reason: '', loading: false },
        blogForm: { title: '', content: '', excerpt: '', status: 'publish', category_id: '', image_id: 0, image_url: '', image_name: '' },
        blogSubmitting: false,
        resourceForm: { title: '', description: '', file_url: '', file_name: '', file_type: 'pdf', category_id: '', image_id: 0, image_url: '', image_name: '' },
        resourceSubmitting: false,

        restUrl: '<?php echo esc_js($rest_url); ?>',
        nonce: '<?php echo esc_js($nonce); ?>',

        async init() {
            await this.loadStats();
            await this.loadPending();
        },

        tabTitle() {
            const titles = {
                overview: 'Dashboard Overview',
                pending: 'Pending Approvals',
                'all-properties': 'All Properties',
                'add-blog': 'New Blog Post',
                blogs: 'Blog Management',
                'add-resource': 'Upload Resource',
                resources: 'Resource Library',
            };
            return titles[this.tab] || 'Admin Panel';
        },

        setTab(t) {
            this.tab = t;
            if (t === 'pending' && !this.pendingProperties.length) this.loadPending();
            if (t === 'all-properties') this.loadAllProperties();
            if (t === 'blogs') this.loadBlogs();
            if (t === 'resources') this.loadResources();
        },

        async loadStats() {
            this.statsLoading = true;
            try {
                const r = await fetch(this.restUrl + 'property-theme/v1/admin/stats', {
                    headers: { 'X-WP-Nonce': this.nonce }
                });
                const d = await r.json();
                if (d.success) this.stats = d;
            } catch(e) {}
            this.statsLoading = false;
        },

        async loadPending() {
            this.pendingLoading = true;
            try {
                const r = await fetch(this.restUrl + 'property-theme/v1/admin/pending-properties?per_page=50', {
                    headers: { 'X-WP-Nonce': this.nonce }
                });
                const d = await r.json();
                if (d.success) this.pendingProperties = d.properties;
            } catch(e) {}
            this.pendingLoading = false;
        },

        async loadAllProperties() {
            this.allPropsLoading = true;
            try {
                const r = await fetch(this.restUrl + 'property-theme/v1/admin/all-properties?status=' + this.propFilter + '&per_page=50', {
                    headers: { 'X-WP-Nonce': this.nonce }
                });
                const d = await r.json();
                if (d.success) this.allProperties = d.properties;
            } catch(e) {}
            this.allPropsLoading = false;
        },

        async approveProperty(id) {
            const r = await fetch(this.restUrl + 'property-theme/v1/admin/approve-property', {
                method: 'POST',
                headers: { 'X-WP-Nonce': this.nonce, 'Content-Type': 'application/json' },
                body: JSON.stringify({ property_id: id })
            });
            const d = await r.json();
            if (d.success) {
                this.pendingProperties = this.pendingProperties.filter(p => p.id !== id);
                this.allProperties = this.allProperties.map(p => p.id === id ? {...p, status: 'publish'} : p);
                this.stats.pending_properties = Math.max(0, (this.stats.pending_properties || 1) - 1);
                this.stats.live_properties = (this.stats.live_properties || 0) + 1;
                this.showToast('Property approved and published!', 'success');
            } else {
                this.showToast(d.message || 'Failed to approve', 'error');
            }
        },

        openRejectModal(id) {
            this.rejectModal = { open: true, propertyId: id, reason: '', loading: false };
        },

        async confirmReject() {
            this.rejectModal.loading = true;
            const r = await fetch(this.restUrl + 'property-theme/v1/admin/reject-property', {
                method: 'POST',
                headers: { 'X-WP-Nonce': this.nonce, 'Content-Type': 'application/json' },
                body: JSON.stringify({ property_id: this.rejectModal.propertyId, reason: this.rejectModal.reason })
            });
            const d = await r.json();
            this.rejectModal.loading = false;
            this.rejectModal.open = false;
            if (d.success) {
                this.pendingProperties = this.pendingProperties.filter(p => p.id !== this.rejectModal.propertyId);
                this.stats.pending_properties = Math.max(0, (this.stats.pending_properties || 1) - 1);
                this.showToast('Property rejected. Owner notified.', 'success');
            } else {
                this.showToast('Failed to reject', 'error');
            }
        },

        async uploadBlogImage(e) {
            const file = e.target.files[0];
            if (!file) return;
            const fd = new FormData();
            fd.append('file', file);
            const r = await fetch(this.restUrl + 'property-theme/v1/admin/upload-file', {
                method: 'POST',
                headers: { 'X-WP-Nonce': this.nonce },
                body: fd
            });
            const d = await r.json();
            if (d.success) {
                this.blogForm.image_id = d.id;
                this.blogForm.image_url = d.url;
                this.blogForm.image_name = d.name;
            } else {
                this.showToast('Image upload failed', 'error');
            }
        },

        async uploadResourceFile(e) {
            const file = e.target.files[0];
            if (!file) return;
            const fd = new FormData();
            fd.append('file', file);
            this.showToast('Uploading file…', 'success');
            const r = await fetch(this.restUrl + 'property-theme/v1/admin/upload-file', {
                method: 'POST',
                headers: { 'X-WP-Nonce': this.nonce },
                body: fd
            });
            const d = await r.json();
            if (d.success) {
                this.resourceForm.file_url = d.url;
                this.resourceForm.file_name = d.name;
                this.showToast('File uploaded successfully', 'success');
            } else {
                this.showToast('File upload failed', 'error');
            }
        },

        async uploadResourceThumbnail(e) {
            const file = e.target.files[0];
            if (!file) return;
            const fd = new FormData();
            fd.append('file', file);
            const r = await fetch(this.restUrl + 'property-theme/v1/admin/upload-file', {
                method: 'POST',
                headers: { 'X-WP-Nonce': this.nonce },
                body: fd
            });
            const d = await r.json();
            if (d.success) {
                this.resourceForm.image_id = d.id;
                this.resourceForm.image_url = d.url;
                this.resourceForm.image_name = d.name;
            }
        },

        async submitBlog() {
            if (!this.blogForm.title.trim()) { this.showToast('Title is required', 'error'); return; }
            // sync editor content
            const editor = document.getElementById('blogEditor');
            if (editor) this.blogForm.content = editor.innerHTML;
            this.blogSubmitting = true;
            const r = await fetch(this.restUrl + 'property-theme/v1/admin/add-blog', {
                method: 'POST',
                headers: { 'X-WP-Nonce': this.nonce, 'Content-Type': 'application/json' },
                body: JSON.stringify(this.blogForm)
            });
            const d = await r.json();
            this.blogSubmitting = false;
            if (d.success) {
                this.showToast('Blog post published!', 'success');
                this.resetBlogForm();
                this.stats.total_blogs = (this.stats.total_blogs || 0) + 1;
            } else {
                this.showToast(d.message || 'Failed to publish', 'error');
            }
        },

        resetBlogForm() {
            this.blogForm = { title: '', content: '', excerpt: '', status: 'publish', category_id: '', image_id: 0, image_url: '', image_name: '' };
            const editor = document.getElementById('blogEditor');
            if (editor) editor.innerHTML = '';
        },

        async submitResource() {
            if (!this.resourceForm.title.trim()) { this.showToast('Title is required', 'error'); return; }
            if (!this.resourceForm.file_url) { this.showToast('Please upload a file', 'error'); return; }
            this.resourceSubmitting = true;
            const r = await fetch(this.restUrl + 'property-theme/v1/admin/add-resource', {
                method: 'POST',
                headers: { 'X-WP-Nonce': this.nonce, 'Content-Type': 'application/json' },
                body: JSON.stringify(this.resourceForm)
            });
            const d = await r.json();
            this.resourceSubmitting = false;
            if (d.success) {
                this.showToast('Resource uploaded successfully!', 'success');
                this.resetResourceForm();
                this.stats.total_resources = (this.stats.total_resources || 0) + 1;
            } else {
                this.showToast(d.message || 'Failed to upload', 'error');
            }
        },

        resetResourceForm() {
            this.resourceForm = { title: '', description: '', file_url: '', file_name: '', file_type: 'pdf', category_id: '', image_id: 0, image_url: '', image_name: '' };
        },

        async loadBlogs() {
            this.blogsLoading = true;
            try {
                const r = await fetch(this.restUrl + 'property-theme/v1/admin/blogs?per_page=50', {
                    headers: { 'X-WP-Nonce': this.nonce }
                });
                const d = await r.json();
                if (d.success) this.blogPosts = d.posts;
            } catch(e) {}
            this.blogsLoading = false;
        },

        async deleteBlog(id) {
            if (!confirm('Move this post to trash?')) return;
            const r = await fetch(this.restUrl + 'property-theme/v1/admin/delete-blog', {
                method: 'POST',
                headers: { 'X-WP-Nonce': this.nonce, 'Content-Type': 'application/json' },
                body: JSON.stringify({ post_id: id })
            });
            const d = await r.json();
            if (d.success) {
                this.blogPosts = this.blogPosts.filter(p => p.id !== id);
                this.showToast('Post deleted', 'success');
            }
        },

        async loadResources() {
            this.resourcesLoading = true;
            try {
                const r = await fetch(this.restUrl + 'property-theme/v1/admin/resources?per_page=50', {
                    headers: { 'X-WP-Nonce': this.nonce }
                });
                const d = await r.json();
                if (d.success) this.resourceList = d.resources;
            } catch(e) {}
            this.resourcesLoading = false;
        },

        async deleteResource(id) {
            if (!confirm('Move this resource to trash?')) return;
            const r = await fetch(this.restUrl + 'property-theme/v1/admin/delete-resource', {
                method: 'POST',
                headers: { 'X-WP-Nonce': this.nonce, 'Content-Type': 'application/json' },
                body: JSON.stringify({ post_id: id })
            });
            const d = await r.json();
            if (d.success) {
                this.resourceList = this.resourceList.filter(r => r.id !== id);
                this.showToast('Resource deleted', 'success');
            }
        },

        showToast(msg, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            const icon = type === 'success'
                ? '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>'
                : '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
            toast.innerHTML = icon + msg;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }
    };
}
</script>
<?php wp_footer(); ?>
</body>
</html>
