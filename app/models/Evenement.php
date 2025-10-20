<?php
class Evenement {
    private $db;
    private $table = 'evenements';

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // Ajouter un événement (conforme à la table: nom, date_event, description, nb_places, prix_place, infos_complementaires, duree, date_fin_inscription)
    public function ajouter($nom, $date_event, $description = '', $nb_places = null, $prix_place = null, $infos_complementaires = null, $duree = null, $date_fin_inscription = null) {
        $sql = "INSERT INTO {$this->table} (nom, date_event, description, nb_places, prix_place, infos_complementaires, duree, date_fin_inscription, created_at)
                VALUES (:nom, :date_event, :description, :nb_places, :prix_place, :infos_complementaires, :duree, :date_fin_inscription, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nom' => $nom,
            ':date_event' => $date_event,
            ':description' => $description,
            ':nb_places' => $nb_places,
            ':prix_place' => $prix_place,
            ':infos_complementaires' => $infos_complementaires,
            ':duree' => $duree,
            ':date_fin_inscription' => $date_fin_inscription
        ]);
        return $this->db->lastInsertId();
    }

    // Mettre à jour un événement
    public function mettreAJour($id, $nom, $date_event, $description = '', $nb_places = null, $prix_place = null, $infos_complementaires = null, $duree = null, $date_fin_inscription = null) {
        $sql = "UPDATE {$this->table} SET nom = :nom, date_event = :date_event, description = :description, nb_places = :nb_places, prix_place = :prix_place, infos_complementaires = :infos_complementaires, duree = :duree, date_fin_inscription = :date_fin_inscription WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':nom' => $nom,
            ':date_event' => $date_event,
            ':description' => $description,
            ':nb_places' => $nb_places,
            ':prix_place' => $prix_place,
            ':infos_complementaires' => $infos_complementaires,
            ':duree' => $duree,
            ':date_fin_inscription' => $date_fin_inscription
        ]);
    }

    // Supprimer un événement
    public function supprimer($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    // Récupérer tous les événements
    public function getAll() {
        $sql = "SELECT * FROM {$this->table} ORDER BY date_event DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer un événement par ID
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Réserver des places pour un événement (diminue nb_places si suffisant)
    public function reserver($id, $quantite = 1) {
        $sql = "UPDATE {$this->table} SET nb_places = nb_places - :qte WHERE id = :id AND nb_places >= :qte";
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([':id' => $id, ':qte' => $quantite]);
        return $ok ? $stmt->rowCount() > 0 : false;
    }

    // Remettre des places (rollback)
    public function release($id, $quantite = 1) {
        $sql = "UPDATE {$this->table} SET nb_places = nb_places + :qte WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([':id' => $id, ':qte' => $quantite]);
        return $ok ? $stmt->rowCount() > 0 : false;
    }
}
?>
