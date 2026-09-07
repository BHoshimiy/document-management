<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>icofex — @yield('title')</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="{{ asset('css/styles.css') }}" rel="stylesheet">
</head>
<body class="auth-body">

<main class="auth-wrap">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="brand">icofex</div>
            <div class="brand-sub">{!! __('app.brand_sub') !!}</div>
        </div>

        @yield('content')
    </div>
</main>

</body>
</html>
