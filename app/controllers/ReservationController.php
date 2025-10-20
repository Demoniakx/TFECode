<?php
require_once __DIR__ . '/../models/Reservation.php';

class ReservationController {
    private $model;
    private $lastError = null;
    private $db;

    public function __construct(PDO $db) {
        $this->model = new Reservation($db);
        $this->db = $db;
    }

    public function getReservations($type = 'salle') {
        return $this->model->getAll($type);
    }

    public function createReservation($data) {
        // expected keys: title, start, end, name, email, phone, notes, type
        $title = $data['title'] ?? 'Réservation';
        // accept multiple possible date keys coming from different forms/clients
        $date = $data['date'] ?? $data['start'] ?? $data['start_dt'] ?? $data['date_event'] ?? null;
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $phone = $data['phone'] ?? '';
        $notes = $data['notes'] ?? '';
        $type = $data['type'] ?? 'salle';
        $address = $data['adresse_client'] ?? $data['address'] ?? $data['adresse'] ?? null;

        if (!$date) {
            $this->lastError = 'Missing required date/start in payload';
            error_log('[ReservationController::createReservation] ' . $this->lastError . ' payload=' . json_encode($data));
            return false;
        }
        // Normalize date: accept ISO 'YYYY-MM-DDTHH:MM' or 'YYYY-MM-DD HH:MM:SS'
        try {
            if (strpos($date, 'T') !== false) {
                $date = str_replace('T', ' ', $date);
                if (strlen($date) === 16) $date .= ':00';
            }
            $dt = new DateTime($date);
            $dateNormalized = $dt->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            $dateNormalized = $date;
        }

    // support additional fields present in your schema
    $entity_id = $data['entity_id'] ?? $data['item_id'] ?? null;
    $quantity = $data['quantite'] ?? $data['quantity'] ?? null;
    // Prepare notes and info fields. We append the traiteur choice to the user notes
    $notes = $data['notes'] ?? '';
    $info = $data['info_complementaire'] ?? $data['info'] ?? null;
    // Determine choice; unchecked checkbox won't be present in POST -> default to 'Non'
    $want = (isset($data['want_traiteur']) && $data['want_traiteur']) ? 'Oui' : 'Non';
    // Append traiteur info to notes so it will be persisted in whichever notes-like column exists
    $notes = trim(($notes ? $notes . "\n" : '') . "Service traiteur: " . $want);

        // If there is already a reservation for this day (type salle), block creation
        $ymd = '';
        try { $ymd = (new DateTime($dateNormalized))->format('Y-m-d'); } catch (Throwable $e) { $ymd = '';} 
        if ($ymd && $type === 'salle' && $this->model->existsForDate($ymd, $type)) {
            $this->lastError = 'Date non disponible: une réservation existe déjà pour ce jour.';
            return false;
        }

        // If this is a reservation for an event, check date_fin_inscription
    if ($type === 'evenement' && $entity_id) {
            try {
        require_once __DIR__ . '/../models/Evenement.php';
        $ev = new Evenement($this->db);
                $event = $ev->getById($entity_id);
                if ($event && !empty($event['date_fin_inscription'])) {
                    // parse date_fin_inscription
                    try {
                        $deadline = new DateTime($event['date_fin_inscription']);
                    } catch (Throwable $e) {
                        // try common alternative field names
                        $deadline = null;
                        foreach (['date_fin', 'fin_inscription'] as $alt) {
                            if (!empty($event[$alt])) {
                                try { $deadline = new DateTime($event[$alt]); break; } catch (Throwable $ie) {}
                            }
                        }
                    }
                    if ($deadline instanceof DateTime) {
                        $now = new DateTime();
                        if ($now > $deadline) {
                            $this->lastError = 'Date limite d\'inscription dépassée pour cet événement.';
                            return false;
                        }
                    }
                }
            } catch (Throwable $e) {
                // If anything goes wrong, log but don't block by default
                error_log('[ReservationController::createReservation] evenement check error: ' . $e->getMessage());
            }
        }

        // If reservation relates to a product/entity, try to decrement stock first
        $stockAdjusted = false;
        $entityModel = null;
        $qty = max(1, (int)($quantity ?? 1));
        if ($entity_id) {
            try {
                if ($type === 'panier') {
                    require_once __DIR__ . '/../models/PanierRepas.php';
                    $m = new PanierRepas($this->db);
                    $stockAdjusted = $m->reserver($entity_id, $qty);
                    $entityModel = $m;
                } elseif ($type === 'planche') {
                    require_once __DIR__ . '/../models/PlancheApero.php';
                    $m = new PlancheApero($this->db);
                    $stockAdjusted = $m->reserver($entity_id, $qty);
                    $entityModel = $m;
                } elseif ($type === 'evenement') {
                    require_once __DIR__ . '/../models/Evenement.php';
                    $m = new Evenement($this->db);
                    $stockAdjusted = $m->reserver($entity_id, $qty);
                    $entityModel = $m;
                }
            } catch (Throwable $e) {
                error_log('[ReservationController::createReservation] stock adjust error: ' . $e->getMessage());
                $stockAdjusted = false;
            }

            if (!$stockAdjusted) {
                $this->lastError = 'Stock insuffisant ou produit introuvable.';
                return false;
            }
        }

        $res = $this->model->create($title, $dateNormalized, $name, $email, $phone, $notes, $type, $address, $entity_id, $quantity, $info);
        if (!$res) {
            // If we adjusted stock earlier, roll it back
            if ($entityModel && method_exists($entityModel, 'release')) {
                try { $entityModel->release($entity_id, $qty); } catch (Throwable $e) { error_log('[ReservationController::createReservation] rollback failed: ' . $e->getMessage()); }
            }
            $last = $this->getLastError();
            error_log('[ReservationController::createReservation] failed: ' . ($last ?? 'unknown'));
        }
        return $res;
    }

    public function supprimerReservation($id) {
        return $this->model->delete($id);
    }

    // Afficher les réservations pour l'admin (HTML simple)
    public function afficherReservations() {
        $rows = $this->model->getAll();
        foreach ($rows as $r) {
            $id = $r['id'] ?? '';
            $type = htmlspecialchars($r['type'] ?? '');
            $entity = htmlspecialchars($r['entity_id'] ?? $r['item_id'] ?? '');
            $nom = htmlspecialchars($r['nom_client'] ?? $r['name'] ?? $r['nom'] ?? '');
            $email = htmlspecialchars($r['email_client'] ?? $r['email'] ?? '');
            $tel = htmlspecialchars($r['tel_client'] ?? $r['tel'] ?? $r['phone'] ?? '');
            $adresse = htmlspecialchars($r['adresse_client'] ?? $r['adresse'] ?? '');
            $dateRaw = $r['date'] ?? $r['start_dt'] ?? $r['start'] ?? '';
            // Try to normalize to a date-only string (dd/mm/YYYY). If parsing fails, keep original raw value.
            $date = '';
            if ($dateRaw) {
                try {
                    $dt = new DateTime($dateRaw);
                    $date = $dt->format('d/m/Y');
                } catch (Throwable $e) {
                    $date = htmlspecialchars($dateRaw);
                }
            }
            $quantite = htmlspecialchars($r['quantite'] ?? $r['quantity'] ?? '');
            $info = nl2br(htmlspecialchars($r['info_complementaire'] ?? $r['info'] ?? $r['notes'] ?? ''));

            echo "<div style='margin-bottom:10px;' data-resource-id='reservations-{$id}'>";
            echo "<strong>#{$id} - " . ($nom ?: 'Réservation') . "</strong><br>";
            echo "Type: {$type} &nbsp <br>";
            echo "📅 Date: {$date}" . "<br>";
            echo "📦 Quantité: {$quantite}<br>";
            // Show name, email with envelope icon, phone with phone icon, and address with pin icon
            $emailHtml = $email ? "<a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a>" : '';
            $telHtml = $tel ? htmlspecialchars($tel) : '';
            $adresseHtml = $adresse ? "<br>📍 " . htmlspecialchars($adresse) : '';

            // Build parts and show only present fields to avoid empty icons
            $parts = [];
            $parts[] = "👤 " . ($nom ?: '');
            if ($emailHtml) $parts[] = "✉️ " . $emailHtml;
            if ($telHtml) $parts[] = "📞 " . $telHtml;

            echo implode(' &nbsp; ', $parts) . $adresseHtml . "<br>";
            if ($info){
            echo "📝 " . $info . "<br>";
            }
            echo "<button class='btn btn-danger me-2' onclick='supprimerReservation({$id})'>Supprimer</button>";
            echo "</div>";
        }
    }

    public function getLastError() {
        // Prefer model error when present, otherwise controller-local error
        if (method_exists($this->model, 'getLastError')) {
            $m = $this->model->getLastError();
            if ($m) return $m;
        }
        return $this->lastError;
    }
}

?>
