<?php
// Inclure le modèle PlancheApero de façon robuste
require_once __DIR__ . "/../models/PlancheApero.php";

class PlancheAperoController {
    private $model;

    public function __construct($db) {
        $this->model = new PlancheApero($db);
    }

    // Ajouter une planche
    public function ajouterPlanche($nom, $nbPersonnes, $prix, $disponible, $ingredients, $allergenes = []) {
        $id = $this->model->ajouter($nom, $nbPersonnes, $prix, $disponible, $ingredients, $allergenes);
        // Retourne l'id pour usage API
        return $id;
    }

    // Mettre à jour une planche
    public function mettreAJourPlanche($id, $nom, $nbPersonnes, $prix, $disponible, $ingredients, $allergenes = []) {
        $success = $this->model->mettreAJour($id, $nom, $nbPersonnes, $prix, $disponible, $ingredients, $allergenes);
        return (bool)$success;
    }

    // Supprimer une planche
    public function supprimerPlanche($id) {
        $success = $this->model->supprimer($id);
        return (bool)$success;
    }

    // Afficher toutes les planches
    public function afficherPlanches() {
        $planches = $this->model->getAll();
        foreach ($planches as $planche) {
            $allergeneNames = $this->model->getAllergenes($planche['id']);
            $allergeneIds = $this->model->getAllergeneIds($planche['id']);
            $dataAttr = htmlspecialchars(json_encode(array_merge($planche, ['allergenes' => $allergeneNames, 'allergene_ids' => $allergeneIds])), ENT_QUOTES, 'UTF-8');
            echo "<div data-resource-id='planches-{$planche['id']}' data-item='{$dataAttr}' style='margin-bottom:10px;'>";
            echo "🍽️ {$planche['nom']} ({$planche['nb_personnes']} pers.) - {$planche['prix']}€<br>";
            echo "🥗 Ingrédients : {$planche['ingredients']}<br>";
            echo "⚠️ Allergènes : " . (empty($allergeneNames) ? "Aucun" : implode(", ", $allergeneNames)) . "<br>";
            echo "🛒 Quantité disponible : {$planche['disponible']}<br>";
            echo "<button class='btn btn-danger me-2' onclick='supprimerPlanche({$planche['id']})'>Supprimer</button>";
            echo "<button class='btn btn-success' onclick=\"openModal('planches', {$planche['id']})\">Modifier</button>";
            echo "</div>";
        }
    }
}
?>
