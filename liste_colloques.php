<?php
require __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['terminate_colloque'])) {
  $id = intval($_POST['terminate_colloque']);
  $stmt = $pdo->prepare("UPDATE colloques SET statut = 'termine' WHERE id = ?");
  $stmt->execute([$id]);
  header("Location: liste_colloques.php");
  exit;
}

$stmt = $pdo->query("SELECT * FROM colloques ORDER BY date_colloque DESC, heure_colloque DESC");
$colloques = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Liste des colloques</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <h2 class="mb-4">Liste des colloques enregistrés</h2>
  <a href="import_colloque_participants.php" class="btn btn-primary mb-3">Créer un nouveau colloque</a>

  <?php if (count($colloques) > 0): ?>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Titre</th>
          <th>Date</th>
          <th>Heure</th>
          <th>Lieu</th>
          <th>Statut</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($colloques as $c): ?>
          <tr>
            <td><?= htmlspecialchars($c['titre']) ?></td>
            <td><?= htmlspecialchars($c['date_colloque']) ?></td>
            <td><?= htmlspecialchars($c['heure_colloque']) ?></td>
            <td><?= htmlspecialchars($c['lieu']) ?></td>
            <td>
              <?php if ($c['statut'] === 'termine'): ?>
                <span class="badge bg-danger">Terminé</span>
              <?php else: ?>
                <span class="badge bg-success">Actif</span>
              <?php endif ?>
            </td>
            <td>
              <a href="participants.php?colloque_id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary">Participants</a>
              <a href="presence.php?colloque_id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-success">Émargement</a>
              <?php if ($c['statut'] !== 'termine'): ?>
                <form method="post" style="display:inline">
                  <input type="hidden" name="terminate_colloque" value="<?= $c['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger">Terminer</button>
                </form>
              <?php else: ?>
                <a href="export_presence_pdf.php?colloque_id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">Exporter PDF</a>
              <?php endif ?>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="alert alert-info">Aucun colloque enregistré pour le moment.</div>
  <?php endif ?>
</body>
</html>
