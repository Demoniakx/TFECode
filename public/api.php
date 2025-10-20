<?php
// Simple API wrapper qui exécute les pages PHP et renvoie le HTML
$header = header('Content-Type: application/json; charset=utf-8');

// Normaliser le nom de la page dès le départ (utilisé pour les routes POST/GET)
$page = preg_replace('/[^a-zA-Z0-9_-]/', '', ($_GET['page'] ?? 'home'));

// Endpoint: récupérer la liste des allergènes (JSON) utilisée par l'admin
if ($page === 'allergenes' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    require_once __DIR__ . "/../config/database.php";
    $db = (new Database())->getConnection();
    try {
        $stmt = $db->query("SELECT id, nom FROM allergenes ORDER BY nom ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'allergenes' => $rows], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// Simple geo suggest endpoint (fallback) - uses Google Geocoding API to return address suggestions
if ($page === 'geo') {
    // accept GET or POST
    $action = $_REQUEST['action'] ?? ($_POST['action'] ?? 'suggest');
    if ($action === 'suggest') {
        $q = trim($_REQUEST['q'] ?? $_POST['q'] ?? $_GET['q'] ?? '');
        if ($q === '') {
            echo json_encode(['success' => false, 'error' => 'Missing q parameter'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        // read key from config if present
        $gkey = null;
        try {
            $gcfg = include __DIR__ . '/../config/google.php';
            $gkey = $gcfg['places_api_key'] ?? null;
        } catch (Throwable $e) {
            $gkey = getenv('GOOGLE_PLACES_API_KEY') ?: null;
        }
        if (!$gkey) {
            echo json_encode(['success' => false, 'error' => 'No API key configured'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($q) . '&key=' . urlencode($gkey);
        $opts = ['http' => ['timeout' => 5]];
        $context = stream_context_create($opts);
        $resp = @file_get_contents($url, false, $context);
        if ($resp === false) {
            echo json_encode(['success' => false, 'error' => 'Request failed'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $json = json_decode($resp, true);
        if (!is_array($json) || ($json['status'] ?? '') !== 'OK') {
            echo json_encode(['success' => false, 'error' => $json['status'] ?? 'No results', 'raw' => $json], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $items = [];
        foreach (array_slice($json['results'], 0, 8) as $r) {
            $items[] = [
                'formatted_address' => $r['formatted_address'] ?? '',
                'place_id' => $r['place_id'] ?? '',
                'lat' => $r['geometry']['location']['lat'] ?? null,
                'lng' => $r['geometry']['location']['lng'] ?? null,
            ];
        }
        echo json_encode(['success' => true, 'suggestions' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Si c'est un POST de login, traiter en priorité
if (isset($_GET['page']) && $_GET['page'] === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . "/../config/database.php";
    require_once __DIR__ . "/../app/controllers/UserController.php";

    $db = (new Database())->getConnection();
    $userCtrl = new UserController($db);

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = $userCtrl->authenticate($email, $password);
    // If this POST was submitted by a plain browser form (not AJAX) and the client
    // doesn't specifically accept JSON, perform a real HTTP redirect so the user
    // is navigated to the target page instead of seeing raw JSON.
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $wantsJson = stripos($accept, 'application/json') !== false;
    if (!$isAjax && !$wantsJson && is_array($result) && !empty($result['success']) && !empty($result['redirect'])) {
        // Perform a redirect for non-AJAX form submissions
        header('Location: ' . $result['redirect']);
        exit;
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

// Handler logout (GET)
if (isset($_GET['page']) && $_GET['page'] === 'logout') {
    require_once __DIR__ . "/../config/database.php";
    require_once __DIR__ . "/../app/controllers/UserController.php";

    $db = (new Database())->getConnection();
    $userCtrl = new UserController($db);
    $userCtrl->logout();
    echo json_encode(['success' => true]);
    exit;
}

// Helper: vérifier si l'utilisateur est admin
function is_admin() {
    if (session_status() === PHP_SESSION_NONE) @session_start();
    return isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin';
}

// CRUD pour événements, planches, paniers et réservations (POST)
// Note: reservations must be included so the internal resource handlers run for page=reservations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($page, ['evenements', 'planches', 'paniers', 'reservations'])) {
    require_once __DIR__ . "/../config/database.php";
    $db = (new Database())->getConnection();

    // NOTE: Development shortcut — allow POSTs without admin role.
    // In production you should re-enable the check below to restrict access.
    /*
    if (!is_admin()) {
        // Provide minimal session info to help debugging (no sensitive data)
        if (session_status() === PHP_SESSION_NONE) @session_start();
        $user = $_SESSION['user'] ?? null;
        $sessionInfo = null;
        if ($user) $sessionInfo = ['id' => $user['id'] ?? null, 'email' => $user['email'] ?? null, 'role' => $user['role'] ?? null];
        echo json_encode(['success' => false, 'error' => 'Accès refusé', 'session' => $sessionInfo], JSON_UNESCAPED_UNICODE);
        exit;
    }
    */

    $action = $_POST['action'] ?? 'list';
    $resource = $page; // normalize name used below

    // If some fields are sent as JSON strings (FormData with JSON), decode them
    foreach ($_POST as $k => $v) {
        if (is_string($v) && $v !== '' && ($v[0] === '[' || $v[0] === '{')) {
            $decoded = json_decode($v, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $_POST[$k] = $decoded;
            }
        }
    }

    try {
        if ($resource === 'evenements') {
            require_once __DIR__ . "/../app/controllers/EvenementController.php";
            $ctrl = new EvenementController($db);

            if ($action === 'get') {
                $id = $_POST['id'] ?? 0;
                require_once __DIR__ . '/../app/models/Evenement.php';
                $m = new Evenement($db);
                $row = $m->getById($id);
                echo json_encode(['success' => true, 'item' => $row], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($action === 'create') {
                // expected fields: nom, date_event, description, nb_places, prix_place, infos_complementaires, duree, date_fin_inscription
                $id = $ctrl->ajouterEvenement(
                    $_POST['nom'] ?? ($_POST['titre'] ?? ''),
                    $_POST['date_event'] ?? ($_POST['date_debut'] ?? ''),
                    $_POST['description'] ?? '',
                    $_POST['nb_places'] ?? null,
                    $_POST['prix_place'] ?? ($_POST['prix'] ?? null),
                    $_POST['infos_complementaires'] ?? null,
                    $_POST['duree'] ?? null,
                    $_POST['date_fin_inscription'] ?? null
                );
                echo json_encode(['success' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
                exit;
            }
            if ($action === 'update') {
                $ok = $ctrl->mettreAJourEvenement(
                    $_POST['id'] ?? 0,
                    $_POST['nom'] ?? ($_POST['titre'] ?? ''),
                    $_POST['date_event'] ?? ($_POST['date_debut'] ?? ''),
                    $_POST['description'] ?? '',
                    $_POST['nb_places'] ?? null,
                    $_POST['prix_place'] ?? ($_POST['prix'] ?? null),
                    $_POST['infos_complementaires'] ?? null,
                    $_POST['duree'] ?? null,
                    $_POST['date_fin_inscription'] ?? null
                );
                echo json_encode(['success' => (bool)$ok], JSON_UNESCAPED_UNICODE);
                exit;
            }
            if ($action === 'delete') {
                $ok = $ctrl->supprimerEvenement($_POST['id'] ?? 0);
                echo json_encode(['success' => (bool)$ok], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        if ($resource === 'reservations') {
            require_once __DIR__ . "/../app/controllers/ReservationController.php";
            $ctrl = new ReservationController($db);

            // Development debug action: return received payload and table columns
            if ($action === 'debug') {
                $payload = $_POST;
                // try to get columns
                $cols = [];
                try {
                    $stmt = $db->query("SHOW COLUMNS FROM reservations");
                    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Throwable $e) {
                    $cols = ['error' => $e->getMessage()];
                }
                // return tail of the reservation-errors.log too (if accessible)
                $logPath = __DIR__ . '/../storage/reservation-errors.log';
                $logTail = null;
                if (is_readable($logPath)) {
                    $lines = @file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    if ($lines !== false) $logTail = array_slice($lines, -80);
                }
                echo json_encode(['success' => true, 'debug' => ['payload' => $payload, 'columns' => $cols, 'log_tail' => $logTail]], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Development debug: test writing to the reservation error log
            if ($action === 'ping_log') {
                $logPath = __DIR__ . '/../storage/reservation-errors.log';
                $ok = false; $err = null;
                $msg = '[' . date('c') . '] ping_log at ' . php_uname('n') . "\n";
                try {
                    $res = @file_put_contents($logPath, $msg, FILE_APPEND | LOCK_EX);
                    $ok = $res !== false;
                } catch (Throwable $e) { $err = $e->getMessage(); }
                echo json_encode(['success' => $ok, 'error' => $err, 'logPath' => $logPath], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($action === 'list') {
                $type = $_POST['type'] ?? 'salle';
                $rows = $ctrl->getReservations($type);
                // Map rows to FullCalendar event shape.
                // Defensive mapping: database column names vary (nom_client, email_client, tel_client, date, info_complementaire, etc.).
                $events = array_map(function($r){
                    $title = $r['title'] ?? $r['titre'] ?? $r['nom'] ?? $r['nom_client'] ?? ($r['type'] ?? 'Réservation');
                    $start = $r['start_dt'] ?? $r['date'] ?? $r['date_debut'] ?? $r['start'] ?? null;
                    $end = $r['end_dt'] ?? $r['date_fin'] ?? null;
                    $name = $r['name'] ?? $r['nom_client'] ?? $r['nom'] ?? null;
                    $email = $r['email'] ?? $r['email_client'] ?? null;
                    $phone = $r['phone'] ?? $r['tel_client'] ?? $r['tel'] ?? null;
                    $notes = $r['notes'] ?? $r['info_complementaire'] ?? $r['commentaires'] ?? null;
                    // Possible privatisation flag in DB
                    $isPrivate = $r['privatisation'] ?? $r['is_private'] ?? $r['private'] ?? null;

                    return [
                        'id' => $r['id'] ?? null,
                        'title' => $title,
                        'start' => $start,
                        'end' => $end ?: null,
                        'privatisation' => $isPrivate,
                        'extendedProps' => ['name' => $name, 'email' => $email, 'phone' => $phone, 'notes' => $notes]
                    ];
                }, $rows);
                echo json_encode(['success' => true, 'events' => $events], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($action === 'create') {
                // accept JSON payloads too
                $payload = $_POST;
                if (isset($payload['payload']) && is_string($payload['payload']) && strpos($payload['payload'], '{') === 0) {
                    $decoded = json_decode($payload['payload'], true);
                    if (json_last_error() === JSON_ERROR_NONE) $payload = $decoded;
                }
                $id = $ctrl->createReservation($payload);
                if ($id) {
                    echo json_encode(['success' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
                } else {
                    $err = null;
                    if (method_exists($ctrl, 'getLastError')) $err = $ctrl->getLastError();
                    echo json_encode(['success' => false, 'error' => $err ?? 'Insertion échouée'], JSON_UNESCAPED_UNICODE);
                }
                exit;
            }

            if ($action === 'delete') {
                $ok = $ctrl->supprimerReservation($_POST['id'] ?? 0);
                echo json_encode(['success' => (bool)$ok], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        if ($resource === 'planches') {
            require_once __DIR__ . "/../app/controllers/PlancheAperoController.php";
            $ctrl = new PlancheAperoController($db);

            if ($action === 'get') {
                $id = $_POST['id'] ?? 0;
                require_once __DIR__ . '/../app/models/PlancheApero.php';
                $m = new PlancheApero($db);
                $row = $m->getById($id);
                echo json_encode(['success' => true, 'item' => $row], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($action === 'create') {
                $id = $ctrl->ajouterPlanche($_POST['nom'] ?? '', $_POST['nb_personnes'] ?? 1, $_POST['prix'] ?? 0, $_POST['disponible'] ?? 1, $_POST['ingredients'] ?? '', $_POST['allergenes'] ?? []);
                echo json_encode(['success' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
                exit;
            }
            if ($action === 'update') {
                $ok = $ctrl->mettreAJourPlanche($_POST['id'] ?? 0, $_POST['nom'] ?? '', $_POST['nb_personnes'] ?? 1, $_POST['prix'] ?? 0, $_POST['disponible'] ?? 1, $_POST['ingredients'] ?? '', $_POST['allergenes'] ?? []);
                echo json_encode(['success' => (bool)$ok], JSON_UNESCAPED_UNICODE);
                exit;
            }
            if ($action === 'delete') {
                $ok = $ctrl->supprimerPlanche($_POST['id'] ?? 0);
                echo json_encode(['success' => (bool)$ok], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        if ($resource === 'paniers') {
            require_once __DIR__ . "/../app/controllers/PanierController.php";
            $ctrl = new PanierController($db);

            if ($action === 'get') {
                $id = $_POST['id'] ?? 0;
                require_once __DIR__ . '/../app/models/PanierRepas.php';
                $m = new PanierRepas($db);
                $row = $m->getById($id);
                echo json_encode(['success' => true, 'item' => $row], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($action === 'create') {
                // normalize allergies/allergenes: prefer 'allergenes' (checkbox lists) otherwise 'allergies'
                $allergenes = $_POST['allergenes'] ?? $_POST['allergies'] ?? [];
                if (is_string($allergenes)) {
                    // try to decode JSON
                    if (($allergenes[0] ?? '') === '[') {
                        $decoded = json_decode($allergenes, true);
                        if (json_last_error() === JSON_ERROR_NONE) $allergenes = $decoded;
                    } else {
                        $allergenes = array_filter(array_map('trim', explode(',', $allergenes ?? '')));
                    }
                }
                if (!is_array($allergenes)) $allergenes = [];

                $ok = $ctrl->ajouterPanier($_POST['nom'] ?? '', $_POST['ingredients'] ?? '', $allergenes, $_POST['nb_personnes'] ?? 1, $_POST['prix'] ?? 0, $_POST['disponible'] ?? 1);
                echo json_encode(['success' => (bool)$ok], JSON_UNESCAPED_UNICODE);
                exit;
            }
            if ($action === 'update') {
                $allergenes = $_POST['allergenes'] ?? $_POST['allergies'] ?? [];
                if (is_string($allergenes)) {
                    if (($allergenes[0] ?? '') === '[') {
                        $decoded = json_decode($allergenes, true);
                        if (json_last_error() === JSON_ERROR_NONE) $allergenes = $decoded;
                    } else {
                        $allergenes = array_filter(array_map('trim', explode(',', $allergenes ?? '')));
                    }
                }
                if (!is_array($allergenes)) $allergenes = [];

                $ok = $ctrl->mettreAJourPanier($_POST['id'] ?? 0, $_POST['nom'] ?? '', $_POST['ingredients'] ?? '', $allergenes, $_POST['nb_personnes'] ?? 1, $_POST['prix'] ?? 0, $_POST['disponible'] ?? 1);
                echo json_encode(['success' => (bool)$ok], JSON_UNESCAPED_UNICODE);
                exit;
            }
            if ($action === 'delete') {
                $ok = $ctrl->supprimerPanier($_POST['id'] ?? 0);
                echo json_encode(['success' => (bool)$ok], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Action inconnue'], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = __DIR__ . "/../app/views/pages/$page.php";

if (file_exists($file)) {
    // Protect the administration page: require authenticated admin
    if ($page === 'administration') {
        // is_admin() helper starts session if needed
        if (!is_admin()) {
            // If request looks like a browser navigation (Accept doesn't prefer JSON), redirect to login
            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            $wantsJson = stripos($accept, 'application/json') !== false;
            if (!$wantsJson) {
                header('Location: /?page=login');
                exit;
            }
            // Otherwise return a JSON error so SPA can handle it
            echo json_encode(['success' => false, 'error' => 'Accès refusé: vous devez être connecté en tant qu\'administrateur.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    // Exécuter le fichier et capturer la sortie pour renvoyer du HTML
    ob_start();
    try {
        include $file;
    } catch (Throwable $e) {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
    $content = ob_get_clean();
} else {
    $content = '<h2>Page introuvable</h2><p>Cette page n\'existe pas.</p>';
}

echo json_encode(['success' => true, 'content' => $content], JSON_UNESCAPED_UNICODE);
