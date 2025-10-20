<?php
class PlancheApero {
    private $db;
    private $table = "planches_apero";

    public function __construct($db) {
        $this->db = $db;
    }

    // Ajouter une planche
    public function ajouter($nom, $nbPersonnes, $prix, $disponible, $ingredients, $allergenes = []) {
        $sql = "INSERT INTO {$this->table} (nom, nb_personnes, prix, disponible, ingredients)
                VALUES (:nom, :nb_personnes, :prix, :disponible, :ingredients)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nom' => $nom,
            ':nb_personnes' => $nbPersonnes,
            ':prix' => $prix,
            ':disponible' => $disponible,
            ':ingredients' => $ingredients
        ]);

        $plancheId = $this->db->lastInsertId();

        // Lier les allergènes
        $this->lierAllergenes($plancheId, $allergenes);

        return $plancheId;
    }

    // Mettre à jour une planche
    public function mettreAJour($id, $nom, $nbPersonnes, $prix, $disponible, $ingredients, $allergenes = []) {
        $sql = "UPDATE {$this->table} 
                SET nom = :nom, nb_personnes = :nb_personnes, prix = :prix, disponible = :disponible, ingredients = :ingredients
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':nom' => $nom,
            ':nb_personnes' => $nbPersonnes,
            ':prix' => $prix,
            ':disponible' => $disponible,
            ':ingredients' => $ingredients
        ]);

        // Supprimer les allergènes existants et lier les nouveaux
        $sqlDel = "DELETE FROM planche_allergenes WHERE planche_id = :id";
        $stmtDel = $this->db->prepare($sqlDel);
        $stmtDel->execute([':id' => $id]);

        $this->lierAllergenes($id, $allergenes);

        return true;
    }

    // Supprimer une planche
    public function supprimer($id) {
        // Supprimer les allergènes liés
        $sqlDel = "DELETE FROM planche_allergenes WHERE planche_id = :id";
        $stmtDel = $this->db->prepare($sqlDel);
        $stmtDel->execute([':id' => $id]);

        // Supprimer la planche
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    // Récupérer toutes les planches
    public function getAll() {
        $sql = "SELECT * FROM {$this->table}";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer une planche par ID
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Réserver une planche (diminue le stock de disponible de manière atomique)
    public function reserver($id, $quantite = 1) {
        $sql = "UPDATE {$this->table} SET disponible = disponible - :qte WHERE id = :id AND disponible >= :qte";
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([':id' => $id, ':qte' => $quantite]);
        return $ok ? $stmt->rowCount() > 0 : false;
    }

    // Remet en stock (rollback si besoin)
    public function release($id, $quantite = 1) {
        $sql = "UPDATE {$this->table} SET disponible = disponible + :qte WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([':id' => $id, ':qte' => $quantite]);
        return $ok ? $stmt->rowCount() > 0 : false;
    }

    // Récupérer les allergènes d'une planche
    public function getAllergenes($plancheId) {
        $sql = "SELECT a.nom
                FROM allergenes a
                JOIN planche_allergenes pa ON a.id = pa.allergene_id
                WHERE pa.planche_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $plancheId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Récupérer tous les allergènes (id + nom)
    public function getAllAllergenes() {
        $sql = "SELECT id, nom FROM allergenes ORDER BY nom ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer les IDs des allergènes liés à une planche
    public function getAllergeneIds($plancheId) {
        $sql = "SELECT allergene_id FROM planche_allergenes WHERE planche_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $plancheId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Lier des allergènes à une planche
    private function lierAllergenes($plancheId, $allergenes = []) {
        foreach ($allergenes as $idAllergene) {
            $sql = "INSERT INTO planche_allergenes (planche_id, allergene_id) VALUES (:pid, :aid)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':pid' => $plancheId, ':aid' => $idAllergene]);
        }
    }
}
?>
