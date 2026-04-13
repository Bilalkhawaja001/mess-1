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
            background: linear-gradient(180deg, #f8efe9 0%, #f3e3dc 100%);
            color: #694842;
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
            background: linear-gradient(180deg, #c78f87 0%, #b97d77 100%);
            border: 1px solid rgba(255,255,255,0.22);
            box-shadow: 0 24px 48px rgba(139, 84, 71, 0.18), inset 0 1px 0 rgba(255,255,255,0.14);
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
            background: linear-gradient(180deg, rgba(249,233,224,0.32), rgba(244,217,206,0.14));
            border: 1px solid rgba(255,255,255,0.18);
        }
        .proto-brand-logo {
            width: 42px;
            height: 42px;
            border-radius: 13px;
            padding: 6px;
            background: linear-gradient(180deg, rgba(255,250,247,0.98), rgba(248,232,224,0.92));
            display: grid;
            place-items: center;
            box-shadow: 0 10px 20px rgba(132,80,68,0.10);
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
            color: #fff8f3;
        }
        .proto-brand-sub {
            margin-top: 2px;
            font-size: 0.56rem;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: rgba(255,241,233,0.84);
        }
        .proto-nav-scroll {
            height: 100%;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 4px;
        }
        .proto-nav-scroll::-webkit-scrollbar { width: 5px; }
        .proto-nav-scroll::-webkit-scrollbar-thumb {
            background: rgba(136,82,71,0.34);
            border-radius: 999px;
        }
        .proto-nav-group {
            padding: 10px 8px 8px;
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(249,232,223,0.16), rgba(255,255,255,0.04));
            border: 1px solid rgba(255,255,255,0.10);
        }
        .proto-nav-group + .proto-nav-group { margin-top: 10px; }
        .proto-nav-group-soft {
            background: linear-gradient(180deg, rgba(243,218,206,0.20), rgba(255,255,255,0.04));
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
            color: rgba(255,241,232,0.88);
        }
        .proto-nav-group-line {
            flex: 1;
            height: 1px;
            background: rgba(255,242,236,0.24);
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
            background: rgba(255,255,255,0.04);
            color: rgba(102,63,58,0.96);
            font-size: 0.69rem;
            font-weight: 500;
            transition: background 0.18s ease, box-shadow 0.18s ease, color 0.18s ease;
        }
        .proto-nav-item:hover {
            background: rgba(249, 234, 224, 0.58);
            color: #6a4541;
            box-shadow: 0 8px 18px rgba(146,92,80,0.07);
        }
        .proto-nav-item.active {
            background: linear-gradient(180deg, rgba(251,236,226,0.95), rgba(244,220,208,0.92));
            color: #6b4640;
            box-shadow: 0 10px 24px rgba(143,88,76,0.13), inset 0 1px 0 rgba(255,255,255,0.78);
        }
        .proto-nav-icon {
            width: 22px;
            height: 22px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: rgba(255,248,243,0.26);
            color: rgba(126,82,74,0.85);
        }
        .proto-nav-item.active .proto-nav-icon {
            background: rgba(255,255,255,0.46);
            color: #a16459;
        }
        .proto-nav-text {
            flex: 1;
            min-width: 0;
        }
        .proto-footer-card {
            padding: 12px 10px 11px;
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(248,232,223,0.92), rgba(240,214,203,0.82));
            border: 1px solid rgba(255,255,255,0.30);
            text-align: center;
            box-shadow: 0 12px 24px rgba(136,84,72,0.10);
        }
        .proto-footer-logo {
            width: 42px;
            height: 42px;
            margin: 0 auto 8px;
            padding: 7px;
            border-radius: 12px;
            background: linear-gradient(180deg, rgba(255,251,248,0.98), rgba(247,230,222,0.92));
            display: grid;
            place-items: center;
            box-shadow: 0 8px 18px rgba(139,88,76,0.10);
        }
        .proto-footer-text {
            display: flex;
            flex-direction: column;
            gap: 2px;
            align-items: center;
            line-height: 1.18;
            color: #7f544d;
            font-weight: 600;
        }
        .proto-footer-text span:first-child {
            font-size: 0.60rem;
            letter-spacing: 0.05em;
            color: #966259;
        }
        .proto-footer-text span:last-child {
            display: block;
            white-space: nowrap;
            font-size: 0.54rem;
            letter-spacing: 0.01em;
            color: #7d5049;
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
            background: rgba(255,255,255,0.52);
            border: 1px solid rgba(255,255,255,0.44);
            box-shadow: 0 20px 44px rgba(161,112,99,0.10);
        }
        .proto-kicker {
            display: inline-flex;
            padding: 0.4rem 0.7rem;
            border-radius: 999px;
            background: rgba(190,126,112,0.10);
            color: #a1675d;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .proto-title {
            margin: 16px 0 10px;
            font-size: 2rem;
            font-weight: 800;
            color: #5e3f3a;
        }
        .proto-copy {
            max-width: 760px;
            color: #7a5a54;
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
            background: linear-gradient(180deg, rgba(255,255,255,0.74), rgba(248,232,225,0.64));
            border: 1px solid rgba(255,255,255,0.52);
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
