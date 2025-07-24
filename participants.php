<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/header.php';

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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Participants du colloque</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">

  <h2 class="mb-4">Participants – <?= htmlspecialchars($colloque['titre']) ?></h2>
  <a href="liste_colloques.php" class="btn btn-secondary mb-3">← Retour à la liste des colloques</a>
  <a href="export_presence_pdf.php?colloque_id=<?= $colloque_id ?>" class="btn btn-outline-primary mb-3">📄 Exporter en PDF</a>

  <?php if (count($participants) > 0): ?>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Nom complet</th>
          <th>Email</th>
          <th>Institution</th>
          <th>Fonction</th>
          <th>Présence</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($participants as $p): ?>
          <tr>
            <td><?= htmlspecialchars($p['nom_complet']) ?></td>
            <td><?= htmlspecialchars($p['email']) ?></td>
            <td><?= htmlspecialchars($p['institution']) ?></td>
            <td><?= htmlspecialchars($p['fonction']) ?></td>
            <td>
              <?php if ($p['presence'] === 'oui'): ?>
                <span class="badge bg-success">Présent</span>
              <?php elseif ($colloque['statut'] === 'termine' && $p['presence'] === 'non'): ?>
                <span class="badge bg-secondary">Absent</span>
              <?php endif ?>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="alert alert-info">Aucun participant pour ce colloque.</div>
  <?php endif ?>

</body>
</html>
