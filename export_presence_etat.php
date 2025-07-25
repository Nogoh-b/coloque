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

$sql = "SELECT u.nom_complet, u.institution, u.fonction, p.id as participant_id,
        (SELECT COUNT(*) FROM presences pr WHERE pr.participant_id = p.id AND pr.status = 'present') as total_present,
        (SELECT COUNT(*) FROM presences pr WHERE pr.participant_id = p.id AND pr.status = 'absent') as total_absent
        FROM participants p
        JOIN users u ON u.id = p.user_id
        WHERE p.colloque_id = ?
        ORDER BY u.nom_complet ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$colloque_id]);
$participants = $stmt->fetchAll();

// Récupérer la présence par date
$presences_by_date = [];
$stats_by_day = [];
$global_stats = ['present' => 0, 'absent' => 0, 'non_marque' => 0];

foreach ($dates as $d) {
  $stats_by_day[$d] = ['present' => 0, 'absent' => 0, 'non_marque' => 0];
  $stmt = $pdo->prepare("SELECT participant_id, status FROM presences WHERE date_presence = ?");
  $stmt->execute([$d]);
  while ($row = $stmt->fetch()) {
    $presences_by_date[$row['participant_id']][$d] = $row['status'];
    $stats_by_day[$d][$row['status']]++;
    $global_stats[$row['status']]++;
  }
}

$total_participants = count($participants);
$total_jours = count($dates);
$total_possible = $total_participants * $total_jours;
$global_percent = $total_possible > 0 ? round(($global_stats['present'] / $total_possible) * 100, 2) : 0;

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
  <h2>Feuille de présence - <?= htmlspecialchars($colloque['titre']) ?></h2>
  <p><strong>Date de début :</strong> <?= $colloque['date_colloque'] ?> &nbsp;&nbsp; <strong>Durée :</strong> <?= $colloque['duree'] ?> jours &nbsp;&nbsp; <strong>Heure :</strong> <?= $colloque['heure_colloque'] ?> &nbsp;&nbsp; <strong>Lieu :</strong> <?= htmlspecialchars($colloque['lieu']) ?></p>
  <h4>Statistiques par jour :</h4>
  <ul>
    <?php foreach ($stats_by_day as $day => $stats): 
      $total_day = $stats['present'] + $stats['absent'] + $stats['non_marque'];
      $percent = $total_day > 0 ? round(($stats['present'] / $total_day) * 100, 2) : 0;
    ?>
      <li>Jour <?= $day ?> : <?= $stats['present'] ?>/<?= $total_day ?> = <?= $percent ?>%</li>
    <?php endforeach; ?>
  </ul>

  <h4>Statistiques globales :</h4>
  <ul>
    <li>Total de présences : <?= $global_stats['present'] ?></li>
    <li>Total d'absences : <?= $global_stats['absent'] ?></li>
    <li>Total non marqués : <?= $global_stats['non_marque'] ?></li>
    <li>Taux de présence global : <?= $global_percent ?>%</li>
  </ul>
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
        <th>Total Présent</th>
        <th>Total Absent</th>
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
          <?php
            $status = $presences_by_date[$p['participant_id']][$d] ?? 'non_marque';
            $class = $status === 'present' ? 'present' : ($status === 'absent' ? 'absent' : 'non-marque');
          ?>
          <td class="<?= $class ?>">
            <?= $status === 'present' ? '✔' : ($status === 'absent' ? '✘' : '—') ?>
          </td>
        <?php endforeach; ?>
        <td><?= $p['total_present'] ?></td>
        <td><?= $p['total_absent'] ?></td>
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
$dompdf->stream("etat_presence_colloque_{$colloque_id}.pdf");
exit;
