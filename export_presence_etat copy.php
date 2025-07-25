<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;

$colloque_id = isset($_GET['colloque_id']) ? intval($_GET['colloque_id']) : 0;

$stmt = $pdo->prepare("SELECT * FROM colloques WHERE id = ?");
$stmt->execute([$colloque_id]);
$colloque = $stmt->fetch();

if (!$colloque) {
    die("Colloque introuvable.");
}

$stmt = $pdo->prepare("SELECT * FROM participants WHERE colloque_id = ? ORDER BY nom_complet ASC");
$stmt->execute([$colloque_id]);
$participants = $stmt->fetchAll();

ob_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; }
    h2 { text-align: center; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #000; padding: 5px; font-size: 12px; }
    th { background-color: #f2f2f2; }
  </style>
</head>
<body>
  <h2>Feuille de présence - <?= htmlspecialchars($colloque['titre']) ?></h2>
<p><strong>Date :</strong> <?= $colloque['date_colloque'] ?> &nbsp;&nbsp; <strong>Heure :</strong> <?= $colloque['heure_colloque'] ?> &nbsp;&nbsp; <strong>Lieu :</strong> <?= htmlspecialchars($colloque['lieu']) ?></p>
     <strong>Lieu :</strong> <?= htmlspecialchars($colloque['lieu']) ?></p>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Nom complet</th>
        <th>Email</th>
        <th>Institution</th>
        <th>Fonction</th>
        <th>Présence</th>
      </tr>
    </thead>
    <tbody>
    <?php $i = 1; foreach ($participants as $p): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><?= htmlspecialchars($p['nom_complet']) ?></td>
        <td><?= htmlspecialchars($p['email']) ?></td>
        <td><?= htmlspecialchars($p['institution']) ?></td>
        <td><?= htmlspecialchars($p['fonction']) ?></td>
        <td>
          <?php if ($p['presence'] === 'oui'): ?>
            Présent
          <?php elseif ($p['presence'] === 'non'): ?>
            Absent
          <?php else: ?>
            Non marqué
          <?php endif; ?>
        </td>
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
