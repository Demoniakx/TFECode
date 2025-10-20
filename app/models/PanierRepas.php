<?php
class PanierRepas {
    private $db;
    private $hasAllergiesColumn = false;

    public function __construct(PDO $db) {
        $this->db = $db;
        // detect if column 'allergies' exists in paniers_repas to remain compatible with different schemas
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM paniers_repas LIKE 'allergies'");
            $stmt->execute();
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->hasAllergiesColumn = !empty($col);
        } catch (Throwable $e) {
            // if any error, assume column missing
            $this->hasAllergiesColumn = false;
        }
    }

    // Ajouter un panier repas
    public function ajouter($nom, $ingredients, $allergies, $nbPersonnes, $prix, $disponible) {
        if ($this->hasAllergiesColumn) {
            $sql = "INSERT INTO paniers_repas (nom, ingredients, allergies, nb_personnes, prix, disponible) ";
            $sql .= "VALUES (:nom, :ingredients, :allergies, :nb_personnes, :prix, :disponible)";
            $stmt = $this->db->prepare($sql);
            $ok = $stmt->execute([
                ':nom' => $nom,
                ':ingredients' => $ingredients,
                ':allergies' => is_array($allergies) ? implode(', ', $allergies) : $allergies,
                ':nb_personnes' => $nbPersonnes,
                ':prix' => $prix,
                ':disponible' => $disponible
            ]);
        } else {
            // schema without allergies column: skip that column
            $sql = "INSERT INTO paniers_repas (nom, ingredients, nb_personnes, prix, disponible) ";
            $sql .= "VALUES (:nom, :ingredients, :nb_personnes, :prix, :disponible)";
            $stmt = $this->db->prepare($sql);
            $ok = $stmt->execute([
                ':nom' => $nom,
                ':ingredients' => $ingredients,
                ':nb_personnes' => $nbPersonnes,
                ':prix' => $prix,
                ':disponible' => $disponible
            ]);
        }

        // If insert succeeded, link allergenes if provided as array of IDs
        if ($ok) {
            $id = (int)$this->db->lastInsertId();
            if (is_array($allergies) && !empty($allergies)) {
                $this->lierAllergenes($id, $allergies);
            }
            return $id;
        }
        return false;
    }

    // Récupérer tous les paniers
    public function getAll() {
        $sql = "SELECT * FROM paniers_repas";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Réserver un panier (diminue le stock)
    public function reserver($id, $quantite = 1) {
        $sql = "UPDATE paniers_repas 
                SET disponible = disponible - :qte 
                WHERE id = :id AND disponible >= :qte";
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([
            ':id' => $id,
            ':qte' => $quantite
        ]);
        if ($ok) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }

    // Mettre à jour un panier
    public function mettreAJour($id, $nom, $ingredients, $allergies, $nbPersonnes, $prix, $disponible) {
        if ($this->hasAllergiesColumn) {
            $sql = "UPDATE paniers_repas SET nom = :nom, ingredients = :ingredients, allergies = :allergies, nb_personnes = :nb_personnes, prix = :prix, disponible = :disponible WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $ok = $stmt->execute([
                ':id' => $id,
                ':nom' => $nom,
                ':ingredients' => $ingredients,
                ':allergies' => is_array($allergies) ? implode(', ', $allergies) : $allergies,
                ':nb_personnes' => $nbPersonnes,
                ':prix' => $prix,
                ':disponible' => $disponible
            ]);
        } else {
            // schema without allergies column: update without that column
            $sql = "UPDATE paniers_repas SET nom = :nom, ingredients = :ingredients, nb_personnes = :nb_personnes, prix = :prix, disponible = :disponible WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $ok = $stmt->execute([
                ':id' => $id,
                ':nom' => $nom,
                ':ingredients' => $ingredients,
                ':nb_personnes' => $nbPersonnes,
                ':prix' => $prix,
                ':disponible' => $disponible
            ]);
        }

        if ($ok) {
            // remove existing links and re-link if provided
            $sqlDel = "DELETE FROM panier_allergenes WHERE panier_id = :id";
            $stmtDel = $this->db->prepare($sqlDel);
            $stmtDel->execute([':id' => $id]);
            if (is_array($allergies) && !empty($allergies)) {
                $this->lierAllergenes($id, $allergies);
            }
        }
        return $ok;
    }

    // Récupérer un panier spécifique
    public function getById($id) {
        $sql = "SELECT * FROM paniers_repas WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lier des allergènes à un panier (table panier_allergenes)
    // Implementation simplified to mirror PlancheApero::lierAllergenes: expects an array of allergene IDs.
    private function lierAllergenes($panierId, $allergenes = []) {
        if (empty($allergenes)) return;

        $sql = "INSERT INTO panier_allergenes (panier_id, allergene_id) VALUES (:pid, :aid)";
        $stmt = $this->db->prepare($sql);
        foreach ($allergenes as $idAllergene) {
            // ensure numeric id
            $aid = (int)$idAllergene;
            if ($aid <= 0) continue;
            $stmt->execute([':pid' => $panierId, ':aid' => $aid]);
        }
    }

    // Récupérer les noms des allergènes liés à un panier
    public function getAllergenes($panierId) {
        $sql = "SELECT a.nom FROM allergenes a JOIN panier_allergenes pa ON a.id = pa.allergene_id WHERE pa.panier_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $panierId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Récupérer les IDs des allergènes liés
    public function getAllergeneIds($panierId) {
        $sql = "SELECT allergene_id FROM panier_allergenes WHERE panier_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $panierId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Supprimer un panier et ses liens d'allergènes
    public function supprimer($id) {
        try {
            // Supprimer les liens d'allergènes si la table existe
            $stmt = $this->db->prepare("DELETE FROM panier_allergenes WHERE panier_id = :id");
            $stmt->execute([':id' => $id]);
        } catch (Throwable $e) {
            // ignore; proceed to try deleting the panier itself
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM paniers_repas WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (Throwable $e) {
            // log error and return false
            error_log('[PanierRepas::supprimer] ' . $e->getMessage());
            return false;
        }
    }
}
