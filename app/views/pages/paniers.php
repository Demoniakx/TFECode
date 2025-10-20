<?php
// Afficher les paniers depuis la base de données en utilisant le controller PanierController si disponible
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../models/PanierRepas.php';

$db = (new Database())->getConnection();
$panierModel = new PanierRepas($db);

?>
<main class="container-fluid p-0">
  <section class="p-5">
    <h1>Paniers repas</h1>
    <p>Découvrez nos paniers gourmands, préparés avec soin pour vos déjeuners et événements.</p>

    <div class="row">
      <?php
      // Récupérer les paniers via le modèle
      $paniers = [];
      try {
          $paniers = $panierModel->getAll();
      } catch (Throwable $e) {
          // fallback: laisser vide
      }
      ?>

      <?php if (empty($paniers)): ?>
        <p>Aucun panier disponible pour le moment.</p>
      <?php else: ?>
  <?php foreach ($paniers as $panier): ?>
          <?php
            $nom = htmlspecialchars($panier['nom']);
            $ingredients = htmlspecialchars($panier['ingredients'] ?? '');
            $prix = isset($panier['prix']) ? htmlspecialchars($panier['prix']) . '€' : '';
            $nb = htmlspecialchars($panier['nb_personnes'] ?? '');
      // parse allergies which may be JSON array or CSV string
      $allergiesRaw = $panier['allergies'] ?? '';
      $allergiesArr = [];
      if (!empty($allergiesRaw)) {
        if (is_string($allergiesRaw) && (strpos(trim($allergiesRaw), '[') === 0 || strpos(trim($allergiesRaw), '{') === 0)) {
          $decoded = json_decode($allergiesRaw, true);
          if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $allergiesArr = $decoded;
          }
        }
        if (empty($allergiesArr)) {
          if (is_string($allergiesRaw)) {
            $parts = array_filter(array_map('trim', explode(',', $allergiesRaw)));
            if (!empty($parts)) $allergiesArr = $parts;
          } elseif (is_array($allergiesRaw)) {
            $allergiesArr = $allergiesRaw;
          }
        }
      }

      // If no allergies found in the column, try fetching linked allergenes from relation table
      if (empty($allergiesArr) && isset($panier['id']) && method_exists($panierModel, 'getAllergenes')) {
        try {
          $linked = $panierModel->getAllergenes($panier['id']);
          if (is_array($linked) && !empty($linked)) {
            $allergiesArr = $linked;
          }
        } catch (Throwable $_) {
          // ignore fetch errors and keep allergies empty
        }
      }
          ?>
          <div class="col-md-4">
            <div class="card product-card mb-3">
              <div class="card-body">
                <h5 class="item-title"><?php echo $nom; ?></h5>
                <p class="item-meta">Composition : <?php echo $ingredients; ?></p>
                <span class="item-price"><?php echo $prix; ?></span>
                <p class="item-meta"><?php echo $nb; ?> pers.</p>
                <?php
                  $disp = $panier['disponible'] ?? null;
                  $zeroClass = (is_numeric($disp) && (int)$disp <= 0) ? ' zero' : '';
                ?>
                <p class="card-text"><small>Quantité disponible : <span class="item-dispo item-availability<?php echo $zeroClass; ?>" data-item-type="panier" data-item-id="<?php echo htmlspecialchars($panier['id'] ?? ''); ?>"><?php echo htmlspecialchars($panier['disponible'] ?? 'N/A'); ?></span></small></p>
                <?php if (!empty($allergiesArr)): ?>
                  <p class="item-meta">Allergènes : <?php echo htmlspecialchars(implode(', ', $allergiesArr)); ?></p>
                <?php endif; ?>
                <button class="btn btn-reserver js-open-reservation" 
                        data-reserve-type="panier"
                        data-item-id="<?php echo htmlspecialchars($panier['id'] ?? ''); ?>"
                        data-title="<?php echo $nom; ?>">
                  Réserver
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>
</main>