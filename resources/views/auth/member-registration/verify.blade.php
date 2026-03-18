<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify OTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-2">Verify OTP</h5>
                    <p class="text-muted small">OTP sent to {{ $maskedMobile }}</p>
                    @include('partials.flash')
                    <form method="POST" action="{{ route('member.register.verify.submit') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">6-digit OTP</label>
                            <input type="text" name="otp_code" maxlength="6" class="form-control" required>
                        </div>
                        <button class="btn btn-primary w-100">Verify OTP</button>
                    </form>
                    <form method="POST" action="{{ route('member.register.resend') }}" class="mt-2">
                        @csrf
                        <button class="btn btn-outline-secondary w-100" @disabled($cooldownSeconds > 0)>Resend OTP @if($cooldownSeconds > 0) ({{ $cooldownSeconds }}s) @endif</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
