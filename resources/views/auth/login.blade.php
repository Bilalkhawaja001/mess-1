<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Mess Billing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Sign In</h5>
                    @include('partials.flash')
                    <div class="d-flex gap-2 mb-3">
                        <a href="{{ route('login') }}" class="btn btn-sm btn-primary">Login</a>
                        <a href="{{ route('member.register.start') }}" class="btn btn-sm btn-outline-success">Register as Member</a>
                        <a href="#forgot-password" class="btn btn-sm btn-outline-secondary">Forgot Password</a>
                    </div>

                    <form method="POST" action="{{ route('login.attempt') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" value="{{ old('username') }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button class="btn btn-primary w-100">Login</button>
                    </form>

                    <div class="mt-3 pt-3 border-top" id="forgot-password">
                        <form method="POST" action="{{ route('password-reset.request.public') }}" class="mb-2">
                            @csrf
                            <label class="form-label small">Forgot Password (request token)</label>
                            <div class="input-group">
                                <input type="text" name="username" class="form-control" placeholder="Username" required>
                                <button class="btn btn-outline-secondary">Request</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('password-reset.consume.public') }}">
                            @csrf
                            <div class="mb-2"><input type="text" name="token" class="form-control" placeholder="Reset token" required></div>
                            <div class="mb-2"><input type="password" name="new_password" class="form-control" placeholder="New password" required></div>
                            <button class="btn btn-outline-dark w-100">Reset Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
