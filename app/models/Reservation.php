<?php
class Reservation {
    private $db;
    private $table = 'reservations';
    private $lastError = null;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // Get all reservations of a given type (e.g. 'salle')
    // If $type is null, return all reservations; otherwise filter by type
    public function getAll($type = null) {
        // detect a date column to order by if present
        $dateCol = null;
        foreach (['start_dt', 'date', 'start'] as $c) { if ($this->hasColumn($c)) { $dateCol = $c; break; } }
        $order = $dateCol ? "ORDER BY `{$dateCol}` ASC" : "ORDER BY id ASC";

        if ($type === null) {
            $sql = "SELECT * FROM {$this->table} {$order}";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } else {
            $sql = "SELECT * FROM {$this->table} WHERE `type` = :type {$order}";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':type' => $type]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Create a reservation
    // Note: signature extended to accept entity_id, quantity and an info field to match actual schema
    public function create($title, $date, $name = '', $email = '', $phone = '', $notes = '', $type = 'salle', $address = null, $entity_id = null, $quantity = null, $info = null) {
        // Read table columns to map logical names to actual columns
        $cols = [];
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM {$this->table}");
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $this->lastError = 'SHOW COLUMNS failed: ' . $e->getMessage();
            @file_put_contents(__DIR__ . '/../../storage/reservation-errors.log', '[' . date('c') . '] ' . $this->lastError . "\n", FILE_APPEND | LOCK_EX);
            return false;
        }

        $available = array_map(function($c){ return $c['Field']; }, $cols);

        // mapping of logical names to possible column names in DB
        $mapCandidates = [
            'title' => ['title', 'objet', 'subject'],
            'date' => ['start_dt', 'date', 'start', 'date_debut', 'date_event'],
            'name' => ['name', 'nom', 'nom_client', 'nom_client', 'nom_client', 'nom_client', 'nom_client', 'nom_client', 'nom_client', 'nom_client', 'nom_client', 'nom_client', 'nom_client', 'nom_client', 'nom_client', 'nom_client', 'nom_client', 'nom_client', 'nom_client', 'nom_client', 'nom_client', 'nom_client', 'nom_client', 'nom_client', 'nom_client', 'nom_client', 'nom_client'],
            'email' => ['email', 'mail', 'email_client'],
            'phone' => ['phone', 'telephone', 'tel', 'tel_client', 'tel_client', 'tel_client', 'tel_client', 'tel_client', 'tel_client', 'tel_client', 'tel_client', 'tel_client', 'tel_client', 'tel_client', 'tel_client', 'tel_client', 'tel_client', 'tel_client', 'tel_client', 'tel_client', 'tel_client', 'tel_client', 'tel_client', 'tel_client', 'tel_client'],
            'notes' => ['notes', 'commentaires', 'note', 'info_complementaire', 'info_complementaire', 'info_complementaire'],
            'type' => ['type', 'reservation_type'],
            'address' => ['adresse_client', 'address', 'adresse', 'lieu'],
            'entity_id' => ['entity_id', 'item_id', 'entity', 'resource_id'],
            'quantity' => ['quantite', 'quantity', 'qty', 'nombre']
        ];

        $columns = [];
        $params = [];
        $placeholders = [];

        // Helper to pick first candidate that exists
        $pick = function($cands) use ($available) {
            foreach ($cands as $c) if (in_array($c, $available)) return $c;
            return null;
        };

        // map fields
        $colTitle = $pick($mapCandidates['title']);
        $colDate = $pick($mapCandidates['date']);
        $colName = $pick($mapCandidates['name']);
        $colEmail = $pick($mapCandidates['email']);
        $colPhone = $pick($mapCandidates['phone']);
        $colNotes = $pick($mapCandidates['notes']);
        $colType = $pick($mapCandidates['type']);
        $colAddress = $pick($mapCandidates['address']);

        // For your schema we require at least a date column; title is optional
        if (!$colDate) {
            $this->lastError = 'Required column missing in reservations table: date';
            @file_put_contents(__DIR__ . '/../../storage/reservation-errors.log', '[' . date('c') . '] ' . $this->lastError . " availableCols=" . json_encode($available) . "\n", FILE_APPEND | LOCK_EX);
            return false;
        }

        // Build SQL parts with unique placeholders
        $idx = 0;
        $add = function($col, $val) use (&$columns, &$placeholders, &$params, &$idx) {
            $ph = ':p' . $idx++;
            $columns[] = "`$col`";
            $placeholders[] = $ph;
            $params[$ph] = $val;
        };

        // If title column exists, add it (some schemas don't have it)
        if ($colTitle) $add($colTitle, $title);
        $add($colDate, $date);
        if ($colName) $add($colName, $name);
        if ($colEmail) $add($colEmail, $email);
        if ($colPhone) $add($colPhone, $phone);
        if ($colNotes) $add($colNotes, $notes);
        if ($colType) $add($colType, $type);
        if ($colAddress && $address !== null) $add($colAddress, $address);
        // include entity_id and quantity/info if columns exist
        $colEntity = $pick($mapCandidates['entity_id']);
        $colQuantity = $pick($mapCandidates['quantity']);
        if ($colEntity && $entity_id !== null) $add($colEntity, $entity_id);
        if ($colQuantity && $quantity !== null) $add($colQuantity, $quantity);
        // map info_complementaire / info to notes-like column if present
        if ($info !== null && $colNotes === null) {
            // if there's a dedicated info_complementaire column, prefer it
            $colInfo = null;
            foreach (['info_complementaire', 'info', 'details'] as $c) { if (in_array($c, $available)) { $colInfo = $c; break; } }
            if ($colInfo) $add($colInfo, $info);
        }

        // created_at handling: if the column exists and has no default, use NOW() expression instead of parameter
        $hasCreatedAt = in_array('created_at', $available);
        $sqlCols = $columns;
        $sqlPlaceholders = $placeholders;
        if ($hasCreatedAt) {
            $sqlCols[] = '`created_at`';
            $sqlPlaceholders[] = 'NOW()';
        }

        $sql = "INSERT INTO `{$this->table}` (" . implode(',', $sqlCols) . ") VALUES (" . implode(',', $sqlPlaceholders) . ")";

        try {
            $stmt = $this->db->prepare($sql);
            $ok = $stmt->execute($params);
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            $logMsg = '[' . date('c') . '] Reservation::create exception: ' . $this->lastError . " SQL= " . $sql . " params=" . json_encode($params) . "\n";
            @file_put_contents(__DIR__ . '/../../storage/reservation-errors.log', $logMsg, FILE_APPEND | LOCK_EX);
            error_log($logMsg);
            return false;
        }

        if ($ok) return (int)$this->db->lastInsertId();

        $err = $stmt->errorInfo();
        $this->lastError = isset($err[2]) ? $err[2] : 'Unknown DB error';
        $logMsg = '[' . date('c') . '] Reservation::create execute failed: ' . $this->lastError . " SQL= " . $sql . " params=" . json_encode($params) . "\n";
        @file_put_contents(__DIR__ . '/../../storage/reservation-errors.log', $logMsg, FILE_APPEND | LOCK_EX);
        error_log($logMsg);
        return false;
    }

    // Helper: check if the table has a given column
    private function hasColumn($col) {
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
            $stmt->execute([$col]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row !== false;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function getLastError() {
        return $this->lastError;
    }

    // Check if there is an existing reservation for a given day (YYYY-MM-DD) and type
    public function existsForDate(string $ymd, string $type = 'salle') {
        // pick date column similarly to getAll
        $dateCol = null;
        foreach (['date', 'start_dt', 'start', 'date_event'] as $c) { if ($this->hasColumn($c)) { $dateCol = $c; break; } }
        if (!$dateCol) return false; // cannot determine date column, be permissive

        try {
            $sql = "SELECT COUNT(*) as cnt FROM {$this->table} WHERE `type` = :type AND DATE(`{$dateCol}`) = :d";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':type' => $type, ':d' => $ymd]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return isset($row['cnt']) && (int)$row['cnt'] > 0;
        } catch (Throwable $e) {
            // on DB error, assume not exists to avoid false blocking; log error
            $this->lastError = 'existsForDate failed: ' . $e->getMessage();
            @file_put_contents(__DIR__ . '/../../storage/reservation-errors.log', '[' . date('c') . '] ' . $this->lastError . "\n", FILE_APPEND | LOCK_EX);
            return false;
        }
    }

    // Delete reservation
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}

?>
