<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NodeSky Mess — App Download</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f4f7fb; --bg-soft: #eef4ff; --surface: #fff;
            --border: #e2e8f0; --text: #0f172a; --muted: #64748b; --primary: #2563eb;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: linear-gradient(180deg, var(--bg-soft) 0%, var(--bg) 100%);
            color: var(--text); min-height: 100vh;
            display: flex; align-items: center; justify-content: center; padding: 24px;
        }
        .card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 26px; box-shadow: 0 18px 45px rgba(15,23,42,.08);
            padding: 40px 32px; max-width: 400px; width: 100%; text-align: center;
        }
        .logo { height: 72px; margin-bottom: 16px; }
        h1 { font-size: 26px; font-weight: 700; margin-bottom: 6px; }
        .sub { color: var(--muted); font-size: 14px; margin-bottom: 28px; }
        .meta {
            display: flex; justify-content: center; gap: 24px;
            padding: 16px 0; border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border); margin-bottom: 28px;
        }
        .meta div { text-align: center; }
        .meta .label { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; }
        .meta .value { font-size: 15px; font-weight: 600; margin-top: 2px; }
        .btn {
            display: block; background: var(--primary); color: #fff;
            text-decoration: none; font-weight: 600; font-size: 16px;
            padding: 16px; border-radius: 12px; transition: background .15s;
        }
        .btn:hover { background: #1d4ed8; }
        .note { color: var(--muted); font-size: 12px; margin-top: 16px; line-height: 1.6; }
        .footer { color: var(--muted); font-size: 12px; margin-top: 28px; }
    </style>
</head>
<body>
    <div class="card">
        <img src="{{ asset('images/nodesky-logo.png') }}" alt="NodeSky" class="logo">
        <h1>NodeSky Mess</h1>
        <p class="sub">Mess Management System</p>

        <div class="meta">
            <div>
                <div class="label">Version</div>
                <div class="value">{{ $version }}</div>
            </div>
            <div>
                <div class="label">Size</div>
                <div class="value">{{ $size }}</div>
            </div>
            <div>
                <div class="label">Requires</div>
                <div class="value">Android 7+</div>
            </div>
        </div>

        <a href="{{ url('downloads/nodesky-mess.apk') }}" class="btn">Download App</a>

        <p class="note">
            After downloading, open the file and allow installation from unknown sources if your phone asks.
        </p>

        <p class="footer">Powered by <strong>NodeSky</strong></p>
    </div>
</body>
</html>
