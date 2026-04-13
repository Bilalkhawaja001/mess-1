<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sidebar Prototype</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body.sidebar-prototype-page {
            min-height: 100vh;
            font-family: 'Inter', system-ui, sans-serif;
            background: linear-gradient(180deg, #fbf4ef 0%, #f6e8e1 100%);
            color: #7a5952;
        }
        .sidebar-prototype-page a { text-decoration: none; }
        .proto-shell { min-height: 100vh; }
        .proto-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: 230px;
            padding: 18px 10px;
            z-index: 30;
        }
        .proto-sidebar-frame {
            height: 100%;
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 12px 10px;
            border-radius: 30px;
            background: linear-gradient(180deg, #cda29b 0%, #bf8d86 100%);
            border: 1px solid rgba(255,255,255,0.28);
            box-shadow: 0 24px 48px rgba(158, 110, 98, 0.14), inset 0 1px 0 rgba(255,255,255,0.18);
            overflow: hidden;
        }
        .proto-sidebar-top, .proto-sidebar-bottom { flex-shrink: 0; }
        .proto-sidebar-middle { flex: 1 1 auto; min-height: 0; }
        .proto-brand-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 10px;
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(250,239,232,0.34), rgba(246,226,216,0.16));
            border: 1px solid rgba(255,255,255,0.22);
        }
        .proto-brand-logo {
            width: 42px;
            height: 42px;
            border-radius: 13px;
            padding: 6px;
            background: linear-gradient(180deg, rgba(255,252,249,0.98), rgba(249,235,227,0.94));
            display: grid;
            place-items: center;
            box-shadow: 0 10px 20px rgba(154,104,92,0.08);
            flex-shrink: 0;
        }
        .proto-brand-logo img, .proto-footer-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        .proto-brand-title {
            font-size: 0.84rem;
            font-weight: 700;
            line-height: 1.1;
            color: #fffaf6;
        }
        .proto-brand-sub {
            margin-top: 2px;
            font-size: 0.56rem;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: rgba(255,246,240,0.84);
        }
        .proto-nav-scroll {
            height: 100%;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 4px;
        }
        .proto-nav-scroll::-webkit-scrollbar { width: 5px; }
        .proto-nav-scroll::-webkit-scrollbar-thumb {
            background: rgba(159,113,101,0.28);
            border-radius: 999px;
        }
        .proto-nav-group {
            padding: 10px 8px 8px;
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(250,238,231,0.16), rgba(255,255,255,0.05));
            border: 1px solid rgba(255,255,255,0.11);
        }
        .proto-nav-group + .proto-nav-group { margin-top: 10px; }
        .proto-nav-group-soft {
            background: linear-gradient(180deg, rgba(247,227,216,0.22), rgba(255,255,255,0.05));
        }
        .proto-nav-group-head {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0 6px 8px;
        }
        .proto-nav-group-label {
            font-size: 0.54rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: rgba(255,247,241,0.88);
        }
        .proto-nav-group-line {
            flex: 1;
            height: 1px;
            background: rgba(255,245,239,0.24);
        }
        .proto-nav-list {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .proto-nav-item {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 36px;
            padding: 6px 9px;
            border-radius: 14px;
            background: rgba(255,250,247,0.08);
            color: #785550;
            font-size: 0.69rem;
            font-weight: 500;
            transition: background 0.18s ease, box-shadow 0.18s ease, color 0.18s ease;
        }
        .proto-nav-item:hover {
            background: rgba(251, 240, 233, 0.62);
            color: #704b46;
            box-shadow: 0 8px 18px rgba(165,113,100,0.06);
        }
        .proto-nav-item.active {
            background: linear-gradient(180deg, rgba(255,245,238,0.98), rgba(246,226,215,0.94));
            color: #6f4a44;
            box-shadow: 0 10px 24px rgba(177,125,111,0.10), inset 0 1px 0 rgba(255,255,255,0.84);
        }
        .proto-nav-icon {
            width: 22px;
            height: 22px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: rgba(255,249,245,0.28);
            color: rgba(141,96,87,0.82);
        }
        .proto-nav-item.active .proto-nav-icon {
            background: rgba(255,255,255,0.62);
            color: #b07b70;
        }
        .proto-nav-text {
            flex: 1;
            min-width: 0;
        }
        .proto-footer-card {
            padding: 12px 10px 11px;
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(251,241,235,0.94), rgba(243,223,213,0.84));
            border: 1px solid rgba(255,255,255,0.34);
            text-align: center;
            box-shadow: 0 12px 24px rgba(168,116,103,0.08);
        }
        .proto-footer-logo {
            width: 42px;
            height: 42px;
            margin: 0 auto 8px;
            padding: 7px;
            border-radius: 12px;
            background: linear-gradient(180deg, rgba(255,252,249,0.98), rgba(249,236,229,0.94));
            display: grid;
            place-items: center;
            box-shadow: 0 8px 18px rgba(170,118,105,0.08);
        }
        .proto-footer-text {
            display: flex;
            flex-direction: column;
            gap: 2px;
            align-items: center;
            line-height: 1.18;
            color: #87615a;
            font-weight: 600;
        }
        .proto-footer-text span:first-child {
            font-size: 0.60rem;
            letter-spacing: 0.05em;
            color: #a2766c;
        }
        .proto-footer-text span:last-child {
            display: block;
            white-space: nowrap;
            font-size: 0.54rem;
            letter-spacing: 0.01em;
            color: #865f58;
        }
        .proto-content {
            margin-left: 230px;
            min-height: 100vh;
            padding: 28px;
        }
        .proto-content-card {
            min-height: calc(100vh - 56px);
            padding: 28px;
            border-radius: 30px;
            background: rgba(255,255,255,0.60);
            border: 1px solid rgba(255,255,255,0.48);
            box-shadow: 0 20px 44px rgba(177,128,114,0.08);
        }
        .proto-kicker {
            display: inline-flex;
            padding: 0.4rem 0.7rem;
            border-radius: 999px;
            background: rgba(201,145,130,0.10);
            color: #b07b70;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .proto-title {
            margin: 16px 0 10px;
            font-size: 2rem;
            font-weight: 800;
            color: #6c4a44;
        }
        .proto-copy {
            max-width: 760px;
            color: #8b6861;
            line-height: 1.7;
        }
        .proto-surface-grid {
            margin-top: 28px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }
        .proto-surface-box {
            min-height: 180px;
            border-radius: 24px;
            background: linear-gradient(180deg, rgba(255,255,255,0.78), rgba(250,238,232,0.68));
            border: 1px solid rgba(255,255,255,0.54);
        }
        .proto-surface-box.tall {
            grid-column: span 2;
            min-height: 240px;
        }
        @media (max-width: 991.98px) {
            .proto-sidebar {
                position: relative;
                width: 100%;
                inset: auto;
                padding: 16px 16px 0;
            }
            .proto-sidebar-frame { height: 760px; }
            .proto-content {
                margin-left: 0;
                padding: 18px 16px 24px;
            }
        }
    </style>
</head>
<body class="sidebar-prototype-page">
<div class="proto-shell">
    <aside class="proto-sidebar">
        <div class="proto-sidebar-frame">
            <div class="proto-sidebar-top">
                <div class="proto-brand-card">
                    <div class="proto-brand-logo">
                        <img src="{{ asset('branding/dashboard_logo.png') }}" alt="Mess Billing logo">
                    </div>
                    <div class="proto-brand-copy">
                        <div class="proto-brand-title">Mess Billing</div>
                        <div class="proto-brand-sub">Corporate Operations Suite</div>
                    </div>
                </div>
            </div>

            <div class="proto-sidebar-middle">
                <div class="proto-nav-scroll">
                    <section class="proto-nav-group">
                        <div class="proto-nav-group-head">
                            <span class="proto-nav-group-label">Operations</span>
                            <span class="proto-nav-group-line"></span>
                        </div>
                        <nav class="proto-nav-list">
                            <a href="#" class="proto-nav-item active"><span class="proto-nav-icon"><i class="bi bi-grid-1x2"></i></span><span class="proto-nav-text">Dashboard</span></a>
                            <a href="#" class="proto-nav-item"><span class="proto-nav-icon"><i class="bi bi-people"></i></span><span class="proto-nav-text">Users</span></a>
                            <a href="#" class="proto-nav-item"><span class="proto-nav-icon"><i class="bi bi-person-lines-fill"></i></span><span class="proto-nav-text">Members</span></a>
                            <a href="#" class="proto-nav-item"><span class="proto-nav-icon"><i class="bi bi-calendar-check"></i></span><span class="proto-nav-text">Attendance</span></a>
                            <a href="#" class="proto-nav-item"><span class="proto-nav-icon"><i class="bi bi-calendar3"></i></span><span class="proto-nav-text">Monthly Attendance</span></a>
                            <a href="#" class="proto-nav-item"><span class="proto-nav-icon"><i class="bi bi-plus-square"></i></span><span class="proto-nav-text">Extras</span></a>
                            <a href="#" class="proto-nav-item"><span class="proto-nav-icon"><i class="bi bi-tags"></i></span><span class="proto-nav-text">Rates</span></a>
                            <a href="#" class="proto-nav-item"><span class="proto-nav-icon"><i class="bi bi-box-seam"></i></span><span class="proto-nav-text">Inventory</span></a>
                            <a href="#" class="proto-nav-item"><span class="proto-nav-icon"><i class="bi bi-truck"></i></span><span class="proto-nav-text">Procurement</span></a>
                            <a href="#" class="proto-nav-item"><span class="proto-nav-icon"><i class="bi bi-egg-fried"></i></span><span class="proto-nav-text">Kitchen</span></a>
                        </nav>
                    </section>

                    <section class="proto-nav-group proto-nav-group-soft">
                        <div class="proto-nav-group-head">
                            <span class="proto-nav-group-label">Billing & Finance</span>
                            <span class="proto-nav-group-line"></span>
                        </div>
                        <nav class="proto-nav-list">
                            <a href="#" class="proto-nav-item"><span class="proto-nav-icon"><i class="bi bi-receipt"></i></span><span class="proto-nav-text">Billing</span></a>
                            <a href="#" class="proto-nav-item"><span class="proto-nav-icon"><i class="bi bi-cash-stack"></i></span><span class="proto-nav-text">Payments</span></a>
                            <a href="#" class="proto-nav-item"><span class="proto-nav-icon"><i class="bi bi-journal-text"></i></span><span class="proto-nav-text">Ledger</span></a>
                            <a href="#" class="proto-nav-item"><span class="proto-nav-icon"><i class="bi bi-clipboard-data"></i></span><span class="proto-nav-text">Summary</span></a>
                            <a href="#" class="proto-nav-item"><span class="proto-nav-icon"><i class="bi bi-bar-chart-line"></i></span><span class="proto-nav-text">Reports</span></a>
                            <a href="#" class="proto-nav-item"><span class="proto-nav-icon"><i class="bi bi-file-earmark-text"></i></span><span class="proto-nav-text">Statement</span></a>
                            <a href="#" class="proto-nav-item"><span class="proto-nav-icon"><i class="bi bi-sliders"></i></span><span class="proto-nav-text">Settings</span></a>
                        </nav>
                    </section>
                </div>
            </div>

            <div class="proto-sidebar-bottom">
                <div class="proto-footer-card">
                    <div class="proto-footer-logo">
                        <img src="{{ asset('branding/nodesky_logo.png') }}" alt="NodeSky logo">
                    </div>
                    <div class="proto-footer-text">
                        <span>Powerd by</span>
                        <span>NodeSky(smc-Private)Limited</span>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <main class="proto-content">
        <div class="proto-content-card">
            <div class="proto-kicker">Prototype Only</div>
            <h1 class="proto-title">Sidebar approval sandbox</h1>
            <p class="proto-copy">Ye page sirf sidebar design approval ke liye hai. Global admin sidebar abhi integrate nahi hua. Agar ye visual direction approve hoti hai to next pass me isi structure ko real menu logic ke sath global sidebar me map kiya jayega.</p>
            <div class="proto-surface-grid">
                <div class="proto-surface-box"></div>
                <div class="proto-surface-box"></div>
                <div class="proto-surface-box tall"></div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
