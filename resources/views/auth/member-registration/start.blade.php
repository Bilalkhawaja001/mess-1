<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Member Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Register as Member</h5>
                    @include('partials.flash')
                    <form method="POST" action="{{ route('member.register.start.submit') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Member ID</label>
                            <input type="text" name="member_id" value="{{ old('member_id') }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Registered Mobile Number</label>
                            <input type="text" name="mobile_number" value="{{ old('mobile_number') }}" class="form-control" required>
                        </div>
                        <button class="btn btn-primary w-100">Send OTP</button>
                    </form>
                    <a href="{{ route('login') }}" class="btn btn-link w-100 mt-2">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
