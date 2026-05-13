<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #0f172a, #0369a1);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 20px 50px rgba(0,0,0,.25);
        }
        h2 {
            margin: 0 0 8px;
            color: #0f172a;
        }
        p {
            margin: 0 0 22px;
            color: #64748b;
            font-size: 14px;
        }
        label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 7px;
        }
        input {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            margin-bottom: 14px;
            font-size: 15px;
        }
        button {
            width: 100%;
            border: 0;
            border-radius: 12px;
            padding: 13px;
            background: #0284c7;
            color: #fff;
            font-weight: 800;
            cursor: pointer;
            font-size: 15px;
        }
        .error {
            background: #fef2f2;
            color: #991b1b;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 14px;
        }
        .logout {
            margin-top: 14px;
            text-align: center;
        }
        .logout button {
            background: transparent;
            color: #64748b;
            padding: 0;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Change Your Password</h2>
        <p>For security, please set a new password before continuing.</p>

        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.force.update') }}">
            @csrf

            <label>New Password</label>
            <input type="password" name="password" required autocomplete="new-password">

            <label>Confirm Password</label>
            <input type="password" name="password_confirmation" required autocomplete="new-password">

            <button type="submit">Update Password</button>
        </form>

        <form class="logout" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </div>
</body>
</html>
