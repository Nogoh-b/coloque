<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/header.php';

if (!isset($_GET['id'])) {
  header("Location: liste_colloques.php");
  exit;
}

$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM colloques WHERE id = ?");
$stmt->execute([$id]);
$colloque = $stmt->fetch();

if (!$colloque) {
  echo "Colloque introuvable.";
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $titre = $_POST['titre'];
  $lieu = $_POST['lieu'];
  $date = $_POST['date'];
  $heure = $_POST['heure'];
  $description = $_POST['description'];
  $duree = $_POST['duree'];

  $stmt = $pdo->prepare("UPDATE colloques SET titre = ?, lieu = ?, date_colloque = ?, heure_colloque = ?, description = ?, duree = ? WHERE id = ?");
  $stmt->execute([$titre, $lieu, $date, $heure, $description, $duree, $id]);

  header("Location: liste_colloques.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Modifier un colloque</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <h2 class="mb-4">Modifier le colloque</h2>
  <form method="post">
    <div class="mb-3">
      <label for="titre" class="form-label">Titre</label>
      <input type="text" class="form-control" id="titre" name="titre" value="<?= htmlspecialchars($colloque['titre']) ?>" required>
    </div>
    <div class="mb-3">
      <label for="lieu" class="form-label">Lieu</label>
      <input type="text" class="form-control" id="lieu" name="lieu" value="<?= htmlspecialchars($colloque['lieu']) ?>" required>
    </div>
    <div class="mb-3">
      <label for="date" class="form-label">Date</label>
      <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($colloque['date_colloque']) ?>" required>
    </div>
    <div class="mb-3">
      <label for="heure" class="form-label">Heure</label>
      <input type="time" class="form-control" id="heure" name="heure" value="<?= htmlspecialchars($colloque['heure_colloque']) ?>" required>
    </div>
    <div class="mb-3">
      <label for="duree" class="form-label">Durée (jours)</label>
      <input type="number" class="form-control" id="duree" name="duree" value="<?= htmlspecialchars($colloque['duree']) ?>" required>
    </div>
    <div class="mb-3">
      <label for="description" class="form-label">Description</label>
      <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($colloque['description']) ?></textarea>
    </div>
    <button type="submit" class="btn btn-success">Enregistrer les modifications</button>
    <a href="liste_colloques.php" class="btn btn-secondary">Annuler</a>
  </form>
</body>
</html>
