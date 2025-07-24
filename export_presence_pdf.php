<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/header.php';

use Dompdf\Dompdf;
use Dompdf\Options;

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

// HTML pour le PDF
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h2 { text-align: center; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
    </style>
</head>
<body>
<h2>Feuille d'émargement – <?= htmlspecialchars($colloque['titre']) ?></h2>
<p><strong>Date :</strong> <?= $colloque['date_colloque'] ?> &nbsp;&nbsp; <strong>Heure :</strong> <?= $colloque['heure_colloque'] ?> &nbsp;&nbsp; <strong>Lieu :</strong> <?= htmlspecialchars($colloque['lieu']) ?></p>
<br>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nom complet</th>
            <th>Institution</th>
            <th>Fonction</th>
            <th>Email</th>
            <th>Signature</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($participants as $index => $p): ?>
        <tr>
            <td><?= $index + 1 ?></td>
            <td><?= htmlspecialchars($p['nom_complet']) ?></td>
            <td><?= htmlspecialchars($p['institution']) ?></td>
            <td><?= htmlspecialchars($p['fonction']) ?></td>
            <td><?= htmlspecialchars($p['email']) ?></td>
            <td></td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>
</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("presence_colloque_{$colloque_id}.pdf", ["Attachment" => false]);
exit;
