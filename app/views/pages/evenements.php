<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../models/Evenement.php';

$db = (new Database())->getConnection();
$evenementModel = new Evenement($db);
$evenements = [];
try {
    $evenements = $evenementModel->getAll();
} catch (Throwable $e) {
    // ignore
}
?>

<main class="container-fluid p-0">
    <section class="p-5">
        <h1>Événements</h1>
        <p>Participez à nos événements gourmands et ateliers culinaires pour découvrir de nouvelles saveurs et partager un moment convivial.</p>

        <div class="row">
            <?php if (empty($evenements)): ?>
                <p>Aucun événement pour le moment.</p>
            <?php else: ?>
                <?php foreach ($evenements as $e): ?>
                    <?php
                        $titre = htmlspecialchars($e['nom'] ?? $e['titre'] ?? '');
                        $desc = htmlspecialchars($e['description'] ?? '');
                        $rawDate = $e['date_event'] ?? '';
                        $dateFormatted = '';
                        if (!empty($rawDate)) {
                            try {
                                $dt = new DateTime($rawDate);
                                $dateFormatted = $dt->format('d/m/Y');
                            } catch (Exception $ex) {
                                $dateFormatted = htmlspecialchars($rawDate);
                            }
                        }
                        $prix = isset($e['prix_place']) && $e['prix_place'] !== null && $e['prix_place'] !== '' ? htmlspecialchars($e['prix_place']) . '€' : 'Gratuit';
                        $nb_places = isset($e['nb_places']) && $e['nb_places'] !== null ? htmlspecialchars($e['nb_places']) : '';
                    ?>
                    <div class="col-md-4">
                        <div class="card product-card mb-3">
                            <div class="card-body">
                                <h5 class="item-title"><?php echo $titre; ?></h5>
                                <?php if (!empty($desc)): ?><p class="item-meta"><?php echo $desc; ?></p><?php endif; ?>
                                <?php if (!empty($dateFormatted)): ?><p class="item-meta">Date : <?php echo $dateFormatted; ?></p><?php endif; ?>
                                <span class="item-price"><?php echo $prix; ?></span>
                                <?php
                                  $disp = $nb_places;
                                  $zeroClass = (is_numeric($disp) && (int)$disp <= 0) ? ' zero' : '';
                                ?>
                                <?php if (!empty($nb_places)): ?><p class="card-text"><small><span class="item-dispo item-availability<?php echo $zeroClass; ?>" data-item-type="evenement" data-item-id="<?php echo htmlspecialchars($e['id'] ?? ''); ?>"><?php echo $nb_places; ?></span> places</small></p><?php endif; ?>
                                                <button class="btn btn-reserver js-open-reservation"
                                                                data-reserve-type="evenement"
                                                                data-item-id="<?php echo htmlspecialchars($e['id'] ?? ''); ?>"
                                                                data-title="<?php echo $titre; ?>"
                                                                data-date-event="<?php echo htmlspecialchars($rawDate ?? ''); ?>">
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
