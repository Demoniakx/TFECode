<?php
// Inclure le modèle PanierRepas en utilisant un chemin basé sur __DIR__ pour plus de robustesse
require_once __DIR__ . "/../models/PanierRepas.php";

class PanierController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new PanierRepas($db);
    }

    // Ajouter un panier repas
    public function ajouterPanier($nom, $ingredients, $allergies, $nbPersonnes, $prix, $disponible) {
        // Accept array of allergene ids (preferred) or names from admin UI.
        // Pass array through to model so it can link allergenes in panier_allergenes when supported.
        return $this->model->ajouter($nom, $ingredients, $allergies, $nbPersonnes, $prix, $disponible);
    }

    // Lister les paniers
    public function afficherPaniers() {
        $paniers = $this->model->getAll();
        foreach ($paniers as $panier) {
            // fetch allergenes names and ids for admin UI
            $allergeneNames = [];
            $allergeneIds = [];
            if (method_exists($this->model, 'getAllergenes')) {
                $allergeneNames = $this->model->getAllergenes($panier['id']);
            }
            if (method_exists($this->model, 'getAllergeneIds')) {
                $allergeneIds = $this->model->getAllergeneIds($panier['id']);
            }

            $data = array_merge($panier, ['allergenes' => $allergeneNames, 'allergene_ids' => $allergeneIds]);
            $dataAttr = htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8');

            echo "<div data-resource-id='paniers-{$panier['id']}' data-item='{$dataAttr}' style='margin-bottom:10px;'>";
            echo "🍽️ {$panier['nom']} ({$panier['nb_personnes']} pers.) - {$panier['prix']}€<br>";
            echo "🥗 Ingrédients : {$panier['ingredients']}<br>";
            echo "⚠️ Allergènes : " . (empty($allergeneNames) ? "Aucun" : implode(", ", $allergeneNames)) . "<br>";
            echo "🛒 Quantité disponible : {$panier['disponible']}<br>";
            echo "<button class='btn btn-danger me-2' onclick='supprimerPanier({$panier['id']})'>Supprimer</button>";
            echo "<button class='btn btn-success' onclick=\"openModal('paniers', {$panier['id']})\">Modifier</button>";
            echo "</div>";
        }
    }

    // Réserver un panier
    public function reserverPanier($id, $quantite = 1) {
        if ($this->model->reserver($id, $quantite)) {
            echo "✅ Réservation réussie !";
        } else {
            echo "❌ Impossible de réserver (stock insuffisant ou erreur).";
        }
    }

    // Supprimer un panier (API)
    public function supprimerPanier($id) {
        if (method_exists($this->model, 'supprimer')) {
            return $this->model->supprimer($id);
        }
        return false;
    }

    // Mettre à jour un panier (API)
    public function mettreAJourPanier($id, $nom, $ingredients, $allergies, $nbPersonnes, $prix, $disponible) {
        if (method_exists($this->model, 'mettreAJour')) {
            // Pass through allergy array to model so it can update panier_allergenes when supported
            return $this->model->mettreAJour($id, $nom, $ingredients, $allergies, $nbPersonnes, $prix, $disponible);
        }
        return false;
    }
}
