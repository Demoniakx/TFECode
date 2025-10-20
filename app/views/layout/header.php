<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Lora:wght@400;600&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Lora:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <title>Thomas Cooking</title>
</head>
<body>

<?php
    // Expose Google Places API key to frontend. Will read from config/google.php which falls back to env var.
    try {
        $gcfg = include __DIR__ . '/../../../config/google.php';
        $gkey = $gcfg['places_api_key'] ?? '';
    } catch (Throwable $e) {
        $gkey = getenv('GOOGLE_PLACES_API_KEY') ?: '';
    }
    ?>
    <script>
        // Window variable used by public/assets/js/app.js to load Maps/Places
        window.GOOGLE_PLACES_API_KEY = '<?php echo htmlspecialchars($gkey ?? '', ENT_QUOTES); ?>';
    </script>
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="/">Thomas Cooking</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="#" data-link="home">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-link="paniers">Paniers repas</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-link="planches">Planches apéros</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-link="evenements">Événements</a></li>
                    <?php if (isset($_SESSION['user'])): ?>
                        <li class="nav-item"><a class="nav-link" href="#" id="logout-link">Se déconnecter</a></li>
                        <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                            <li class="nav-item"><a class="nav-link" href="#" data-link="administration">Administration</a></li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="#" data-link="login">Se connecter</a></li>
                    <?php endif; ?>
                </ul>
                <a href="#" data-link="location" class="btn btn-reserver ms-lg-3">Réserver la salle</a>
            </div>
        </div>
    </nav>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var logout = document.getElementById('logout-link');
            if (!logout) return;
            logout.addEventListener('click', function(e) {
                e.preventDefault();
                // Call server logout then reload the page so server-side session changes are reflected
                fetch('/api.php?page=logout')
                    .finally(function() {
                        // Force a full reload to pick up new header state
                        window.location.href = '/?page=home';
                    });
            });
        });
    </script>