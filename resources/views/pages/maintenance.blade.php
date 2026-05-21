<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance | Les Zheros</title>
    <meta name="description" content="Le site Les Zheros est temporairement en maintenance.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700;800&family=Crimson+Pro:wght@700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>
<body class="maintenance-page">
    <main class="maintenance-shell">
        <section class="maintenance-card" aria-labelledby="maintenance-title">
            <img class="maintenance-card__logo" src="{{ asset('assets/img/logo.png') }}" alt="Les Zheros">
            <span class="maintenance-card__eyebrow"><i class="fa-solid fa-screwdriver-wrench"></i> Maintenance</span>
            <h1 id="maintenance-title">Le repaire est en travaux</h1>
            <p>{{ $message }}</p>
            <div class="maintenance-card__status">
                <span></span>
                <strong>Retour prochainement</strong>
            </div>
            <a class="btn btn--outline" href="{{ route('connexion') }}">Connexion admin</a>
        </section>
    </main>
</body>
</html>
