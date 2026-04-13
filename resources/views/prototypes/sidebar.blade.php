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
            background: linear-gradient(180deg, #0b1424 0%, #101b31 100%);
            color: #d9e5ff;
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
            background: linear-gradient(180deg, #10213d 0%, #0d1a31 100%);
            border: 1px solid rgba(148,175,255,0.16);
            box-shadow: 0 24px 48px rgba(3, 8, 20, 0.34), inset 0 1px 0 rgba(255,255,255,0.06);
            overflow: hidden;
        }
        .proto-sidebar-top, .proto-sidebar-bottom { flex-shrink: 0; }
        .proto-sidebar-middle { flex: 1 1 auto; min-height: 0; }
        .proto-project-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 11px;
            border-radius: 22px;
            background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.03));
            border: 1px solid rgba(148,175,255,0.14);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.08);
        }
        .proto-project-symbol-wrap {
            width: 46px;
            height: 46px;
            border-radius: 16px;
            background: linear-gradient(180deg, rgba(255,255,255,0.12), rgba(159,193,255,0.08));
            display: grid;
            place-items: center;
            box-shadow: 0 10px 20px rgba(4,10,24,0.20);
            flex-shrink: 0;
        }
        .proto-project-symbol {
            width: 22px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 3px;
        }
        .proto-project-symbol span {
            display: block;
            width: 5px;
            border-radius: 999px;
            background: linear-gradient(180deg, #8eb3ff 0%, #5d82d6 100%);
        }
        .proto-project-symbol span:nth-child(1) { height: 10px; opacity: 0.7; }
        .proto-project-symbol span:nth-child(2) { height: 16px; }
        .proto-project-symbol span:nth-child(3) { height: 13px; opacity: 0.85; }
        .proto-project-copy {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .proto-project-kicker {
            font-size: 0.50rem;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            color: rgba(205,223,255,0.74);
        }
        .proto-project-title {
            font-size: 0.80rem;
            font-weight: 700;
            line-height: 1.06;
            color: #f5f9ff;
            white-space: nowrap;
        }
        .proto-project-sub {
            font-size: 0.56rem;
            line-height: 1.25;
            color: rgba(201,217,245,0.78);
        }
        .proto-footer-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        .proto-nav-scroll {
            height: 100%;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 4px;
        }
        .proto-nav-scroll::-webkit-scrollbar { width: 5px; }
        .proto-nav-scroll::-webkit-scrollbar-thumb {
            background: rgba(114,147,212,0.34);
            border-radius: 999px;
        }
        .proto-nav-group {
            padding: 10px 8px 8px;
            border-radius: 20px;
            background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
            border: 1px solid rgba(148,175,255,0.10);
        }
        .proto-nav-group + .proto-nav-group { margin-top: 10px; }
        .proto-nav-group-soft {
            background: linear-gradient(180deg, rgba(122,152,214,0.12), rgba(255,255,255,0.02));
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
            color: rgba(208,224,255,0.84);
        }
        .proto-nav-group-line {
            flex: 1;
            height: 1px;
            background: rgba(164,188,238,0.22);
        }
        .proto-nav-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .proto-nav-item {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 42px;
            padding: 7px 10px;
            border-radius: 17px;
            background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
            color: #d8e4ff;
            font-size: 0.67rem;
            font-weight: 500;
            transition: background 0.18s ease, box-shadow 0.18s ease, color 0.18s ease;
        }
        .proto-nav-item:hover {
            background: linear-gradient(180deg, rgba(95,126,189,0.30), rgba(57,84,137,0.22));
            color: #eef4ff;
            box-shadow: 0 10px 20px rgba(3,10,24,0.16);
        }
        .proto-nav-item.active {
            background: linear-gradient(180deg, rgba(120,151,219,0.34), rgba(70,99,160,0.30));
            color: #f5f9ff;
            box-shadow: 0 12px 22px rgba(4,12,28,0.24), inset 0 1px 0 rgba(255,255,255,0.08);
        }
        .proto-nav-item.active::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 16px;
            box-shadow: inset 0 0 0 1px rgba(163,191,247,0.36);
            pointer-events: none;
        }
        .proto-nav-icon {
            width: 26px;
            height: 26px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(121,151,212,0.08));
            color: rgba(193,214,255,0.86);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.10);
        }
        .proto-nav-item.active .proto-nav-icon {
            background: linear-gradient(180deg, rgba(255,255,255,0.16), rgba(183,206,255,0.10));
            color: #f5f9ff;
        }
        .proto-nav-text {
            flex: 1;
            min-width: 0;
            letter-spacing: 0.01em;
        }
        .proto-footer-card {
            padding: 16px 12px 14px;
            border-radius: 22px;
            background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(120,148,204,0.06));
            border: 1px solid rgba(148,175,255,0.16);
            text-align: center;
            box-shadow: 0 16px 26px rgba(3,10,24,0.18), inset 0 1px 0 rgba(255,255,255,0.08);
        }
        .proto-footer-logo {
            width: 46px;
            height: 46px;
            margin: 0 auto 10px;
            padding: 8px;
            border-radius: 14px;
            background: linear-gradient(180deg, rgba(255,255,255,0.12), rgba(160,189,247,0.10));
            display: grid;
            place-items: center;
            box-shadow: 0 10px 18px rgba(4,10,24,0.18);
        }
        .proto-footer-text {
            display: flex;
            flex-direction: column;
            gap: 4px;
            align-items: center;
            line-height: 1.15;
            color: #d6e3ff;
            font-weight: 600;
        }
        .proto-footer-text span:first-child {
            font-size: 0.62rem;
            letter-spacing: 0.08em;
            color: #9bbcff;
            text-transform: uppercase;
        }
        .proto-footer-text span:last-child {
            display: block;
            white-space: nowrap;
            font-size: 0.58rem;
            letter-spacing: 0.015em;
            color: #dbe7ff;
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
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(140,170,230,0.12);
            box-shadow: 0 20px 44px rgba(2,8,20,0.24);
        }
        .proto-kicker {
            display: inline-flex;
            padding: 0.4rem 0.7rem;
            border-radius: 999px;
            background: rgba(120,151,219,0.14);
            color: #9ebeff;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .proto-title {
            margin: 16px 0 10px;
            font-size: 2rem;
            font-weight: 800;
            color: #f3f7ff;
        }
        .proto-copy {
            max-width: 760px;
            color: #bed0f4;
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
            background: linear-gradient(180deg, rgba(255,255,255,0.05), rgba(116,145,201,0.04));
            border: 1px solid rgba(140,170,230,0.12);
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
                <div class="proto-project-card">
                    <div class="proto-project-symbol-wrap">
                        <div class="proto-project-symbol">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                    <div class="proto-project-copy">
                        <div class="proto-project-kicker">Project Navigation</div>
                        <div class="proto-project-title">Workspace Panel</div>
                        <div class="proto-project-sub">Soft compact rail for admin flow</div>
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
