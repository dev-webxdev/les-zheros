<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Les Zheros')</title>
    <meta name="description" content="@yield('description', '')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    @if (session('toast'))
        <meta name="site-toast-title" content="{{ session('toast.title') }}">
        <meta name="site-toast-text" content="{{ session('toast.text') }}">
        <meta name="site-toast-type" content="{{ session('toast.type', 'success') }}">
    @elseif ($errors->any())
        <meta name="site-toast-title" content="Action impossible">
        <meta name="site-toast-text" content="{{ $errors->first() }}">
        <meta name="site-toast-type" content="danger">
    @endif
    <script src="{{ asset('assets/js/main.js') }}" defer></script>
</head>
<body class="{{ $bodyClass ?? 'auth-body' }}">
    @yield('content')
</body>
</html>
