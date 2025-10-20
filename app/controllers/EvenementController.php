<?php
require_once __DIR__ . "/../models/Evenement.php";

class EvenementController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new Evenement($db);
    }

    // Afficher les événements (format simple pour l'administration)
    public function afficherEvenements() {
        $evenements = $this->model->getAll();
        if (empty($evenements)) {
            echo "<p>Aucun événement trouvé.</p>";
            return;
        }

        echo "<ul class='liste-evenements'>";
        foreach ($evenements as $e) {
            $dateDebut = htmlspecialchars($e['date_event'] ?? '');
            $nom = htmlspecialchars($e['nom'] ?? $e['titre'] ?? '');
            $description = htmlspecialchars($e['description'] ?? '');
            $nb_places = isset($e['nb_places']) ? htmlspecialchars($e['nb_places']) : '';
            $prix_place = isset($e['prix_place']) ? htmlspecialchars($e['prix_place']) . '€' : '';
                        $dataAttr = htmlspecialchars(json_encode($e), ENT_QUOTES, 'UTF-8');
                        echo "<li data-resource-id='evenements-{$e['id']}' data-item='{$dataAttr}'>";
                        echo "<strong>{$nom}</strong> — {$dateDebut}";
                        if ($prix_place) echo " — <em>{$prix_place}</em>";
                        echo "<div style='margin-top:6px;'>";
                        echo "<button class='btn btn-danger me-2' onclick='supprimerEvenement({$e['id']})'>Supprimer</button>";
                        echo "<button class='btn btn-success' onclick=\"openModal('evenements', {$e['id']})\">Modifier</button>";
                        echo "</div>";
                        echo "</li>";
        }
        echo "</ul>";
    }

    // Ajouter un événement (mappe aux colonnes: nom, date_event, description, nb_places, prix_place, infos_complementaires, duree, date_fin_inscription)
    public function ajouterEvenement($nom, $date_event, $description = '', $nb_places = null, $prix_place = null, $infos_complementaires = null, $duree = null, $date_fin_inscription = null) {
        return $this->model->ajouter($nom, $date_event, $description, $nb_places, $prix_place, $infos_complementaires, $duree, $date_fin_inscription);
    }

    // Supprimer un événement
    public function supprimerEvenement($id) {
        return $this->model->supprimer($id);
    }

    // Mettre à jour un événement
    public function mettreAJourEvenement($id, $nom, $date_event, $description = '', $nb_places = null, $prix_place = null, $infos_complementaires = null, $duree = null, $date_fin_inscription = null) {
        return $this->model->mettreAJour($id, $nom, $date_event, $description, $nb_places, $prix_place, $infos_complementaires, $duree, $date_fin_inscription);
    }
}
?>