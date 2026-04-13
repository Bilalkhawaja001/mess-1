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
    <link rel="stylesheet" href="{{ asset('branding/nodesky-theme.css') }}">
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
