<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;

$colloque_id = isset($_GET['colloque_id']) ? intval($_GET['colloque_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$stmt = $pdo->prepare("SELECT * FROM colloques WHERE id = ?");
$stmt->execute([$colloque_id]);
$colloque = $stmt->fetch();

if (!$colloque) {
    die("Colloque introuvable.");
}

$start = new DateTime($colloque['date_colloque']);
$dates = [];
for ($i = 0; $i < $colloque['duree']; $i++) {
  $d = clone $start;
  $d->modify("+{$i} days");
  $dates[] = $d->format('Y-m-d');
}

$sql = "SELECT u.nom_complet, u.institution, u.fonction, p.id as participant_id
        FROM participants p
        JOIN users u ON u.id = p.user_id
        WHERE p.colloque_id = ?
        ORDER BY u.nom_complet ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$colloque_id]);
$participants = $stmt->fetchAll();

// Récupérer la présence par date
$presences_by_date = [];
foreach ($dates as $d) {
  $stmt = $pdo->prepare("SELECT participant_id, status FROM presences WHERE date_presence = ?");
  $stmt->execute([$d]);
  while ($row = $stmt->fetch()) {
    $presences_by_date[$row['participant_id']][$d] = $row['status'];
  }
}

ob_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
    h2 { text-align: center; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { border: 1px solid #000; padding: 4px; text-align: center; }
    th { background-color: #f2f2f2; }
    .present { background-color: #c8e6c9; }
    .absent { background-color: #ffcdd2; }
    .non-marque { background-color: #eeeeee; }
  </style>
</head>
<body>
  <h2>Fiche d'émargement - <?= htmlspecialchars($colloque['titre']) ?></h2>
  <p><strong>Date de début :</strong> <?= $colloque['date_colloque'] ?> &nbsp;&nbsp; <strong>Durée :</strong> <?= $colloque['duree'] ?> jours &nbsp;&nbsp; <strong>Heure :</strong> <?= $colloque['heure_colloque'] ?> &nbsp;&nbsp; <strong>Lieu :</strong> <?= htmlspecialchars($colloque['lieu']) ?></p>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Nom</th>
        <th>Institution</th>
        <th>Fonction</th>
        <?php foreach ($dates as $d): ?>
          <th><?= $d ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
    <?php $i = 1; foreach ($participants as $p): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><?= htmlspecialchars($p['nom_complet']) ?></td>
        <td><?= htmlspecialchars($p['institution']) ?></td>
        <td><?= htmlspecialchars($p['fonction']) ?></td>
        <?php foreach ($dates as $d): ?>

          <td class="<?= $class ?>">
           </td>
        <?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
<?php
$html = ob_get_clean();

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("fiche_emargement_colloque_{$colloque_id}.pdf");
exit;
