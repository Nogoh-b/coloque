<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/header.php';

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
          <th>Date début</th>
          <th>Date fin</th>
          <th>Durée (jours)</th>
          <th>Heure</th>
          <th>Lieu</th>
          <th>Statut</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($colloques as $c): 
          $date_debut = new DateTime($c["date_colloque"]);
          $date_fin = (clone $date_debut)->modify("+" . ($c["duree"] - 1) . " days");
          $aujourdhui = new DateTime();
        ?>
          <tr>
            <td><?= htmlspecialchars($c['titre']) ?></td>
            <td><?= $date_debut->format("Y-m-d") ?></td>
            <td><?= $date_fin->format("Y-m-d") ?></td>
            <td><?= htmlspecialchars($c["duree"]) ?></td>
            <td><?= htmlspecialchars($c["heure_colloque"]) ?></td>
            <td><?= htmlspecialchars($c["lieu"]) ?></td>
            <td><?= htmlspecialchars($c["statut"]) ?></td>
            <td>
              <?php if ($aujourdhui <= $date_fin): ?>
                <a href="modifier_colloque.php?id=<?= $c["id"] ?>" class="btn btn-primary btn-sm">Éditer</a>
                <a href="supprimer_colloque.php?id=<?= $c["id"] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Confirmer la suppression ?')">Supprimer</a>
              <?php endif; ?>
              <form method="post" style="display:inline;">
                <input type="hidden" name="terminate_colloque" value="<?= $c["id"] ?>">
                <button type="submit" class="btn btn-warning btn-sm">Terminer</button>
              </form>
              <a href="participants.php?colloque_id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary">Participants</a>
              <a href="presence.php?colloque_id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-success">Émargement</a>

              <a href="export_presence_etat.php?colloque_id=<?= $c["id"] ?>" class="btn btn-secondary btn-sm">Exporter</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="alert alert-info">Aucun colloque enregistré pour le moment.</div>
  <?php endif; ?>
</body>
</html>
