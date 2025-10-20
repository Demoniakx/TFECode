<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../models/PlancheApero.php';

$db = (new Database())->getConnection();
$plancheModel = new PlancheApero($db);
$planches = [];
try {
  $planches = $plancheModel->getAll();
} catch (Throwable $e) {
  // ignore
}
?>

<main class="container-fluid p-0">
  <section class="p-5">
    <h1>Planches apéros</h1>
    <p>Découvrez nos planches apéritives, idéales pour vos événements et moments conviviaux.</p>
  
    <div class="row">
      <?php if (empty($planches)): ?>
        <p>Aucune planche disponible pour le moment.</p>
      <?php else: ?>
        <?php foreach ($planches as $planche): ?>
          <?php
            $nom = htmlspecialchars($planche['nom']);
            $ingredients = htmlspecialchars($planche['ingredients'] ?? '');
            $prix = isset($planche['prix']) ? htmlspecialchars($planche['prix']) . '€' : '';
            $nb = htmlspecialchars($planche['nb_personnes'] ?? '');
      $allerg = [];
      try {
        $allerg = $plancheModel->getAllergenes($planche['id']);
      } catch (Throwable $e) {}
          ?>
          <div class="col-md-4">
            <div class="card product-card mb-3">
              <div class="card-body">
                <h5 class="item-title"><?php echo $nom; ?></h5>
                <p class="item-meta">Composition : <?php echo $ingredients; ?></p>
                <span class="item-price"><?php echo $prix; ?></span>
                <p class="item-meta"><?php echo $nb; ?> pers.</p>
                <?php
                  $disp = $planche['disponible'] ?? null;
                  $zeroClass = (is_numeric($disp) && (int)$disp <= 0) ? ' zero' : '';
                ?>
                <p class="card-text"><small>Quantité disponible : <span class="item-dispo item-availability<?php echo $zeroClass; ?>" data-item-type="planche" data-item-id="<?php echo htmlspecialchars($planche['id'] ?? ''); ?>"><?php echo htmlspecialchars($planche['disponible'] ?? 'N/A'); ?></span></small></p>
                <?php if (!empty($allerg)): ?>
                  <p class="item-meta">Allergènes : <?php echo htmlspecialchars(implode(', ', $allerg)); ?></p>
                <?php endif; ?>
                <button class="btn btn-reserver js-open-reservation"
                        data-reserve-type="planche"
                        data-item-id="<?php echo htmlspecialchars($planche['id'] ?? ''); ?>"
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