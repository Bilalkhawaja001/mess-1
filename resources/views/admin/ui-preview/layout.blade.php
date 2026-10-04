<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Mess Billing UI Preview' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@300..700,0..1&display=swap" rel="stylesheet">
    @include('admin.ui-preview.partials.styles')
</head>
<body class="mess-ui-body">
<div class="mess-ui-shell">
    @include('admin.ui-preview.partials.sidebar', ['active' => $active ?? 'dashboard'])
    <main class="mess-ui-main">
        @include('admin.ui-preview.partials.topbar')
        <section class="mess-ui-content">@yield('content')</section>
    </main>
</div>
</body>
</html>