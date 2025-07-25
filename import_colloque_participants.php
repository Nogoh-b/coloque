<?php
require __DIR__ . '/includes/db.php';
require 'vendor/autoload.php';
require __DIR__ . '/includes/header.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fichier']) && isset($_POST['titre'])) {
  $titre = $_POST['titre'];
  $lieu = $_POST['lieu'];
  $date = $_POST['date'];
  $heure = $_POST['heure'];
  $description = $_POST['description'];
  $duree = $_POST['duree'];

  $stmt = $pdo->prepare("INSERT INTO colloques (titre, lieu, date_colloque, heure_colloque, description, duree) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->execute([$titre, $lieu, $date, $heure, $description, $duree]);
  $colloque_id = $pdo->lastInsertId();

  $spreadsheet = IOFactory::load($_FILES['fichier']['tmp_name']);
  $sheet = $spreadsheet->getActiveSheet();
  $rows = $sheet->toArray();

  for ($i = 1; $i < count($rows); $i++) {
    $r = $rows[$i];
    $nom = trim($r[2]);
    $institution = trim($r[3]);
    $fonction = trim($r[4]);
    $email = trim($r[5]);

    $user_id = null;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE nom_complet = ?");
    $stmt->execute([$nom]);
    $existing_by_name = $stmt->fetch();

    if ($existing_by_name) {
      if (empty($email) || $existing_by_name['email'] === $email) {
        $stmt = $pdo->prepare("UPDATE users SET institution = ?, fonction = ?, email = ? WHERE id = ?");
        $stmt->execute([$institution, $fonction, $email ?: $existing_by_name['email'], $existing_by_name['id']]);
        $user_id = $existing_by_name['id'];
      } else {
        $stmt = $pdo->prepare("INSERT INTO users (nom_complet, institution, fonction, email) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nom, $institution, $fonction, $email]);
        $user_id = $pdo->lastInsertId();
      }
    } else {
      if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existing_by_email = $stmt->fetch();

        if ($existing_by_email) {
          if (
            $existing_by_email['nom_complet'] !== $nom ||
            $existing_by_email['institution'] !== $institution ||
            $existing_by_email['fonction'] !== $fonction
          ) {
            $stmt = $pdo->prepare("INSERT INTO users (nom_complet, institution, fonction, email) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nom, $institution, $fonction, $email]);
            $user_id = $pdo->lastInsertId();
          } else {
            $user_id = $existing_by_email['id'];
          }
        } else {
          $stmt = $pdo->prepare("INSERT INTO users (nom_complet, institution, fonction, email) VALUES (?, ?, ?, ?)");
          $stmt->execute([$nom, $institution, $fonction, $email]);
          $user_id = $pdo->lastInsertId();
        }
      } else {
        $stmt = $pdo->prepare("INSERT INTO users (nom_complet, institution, fonction, email) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nom, $institution, $fonction, null]);
        $user_id = $pdo->lastInsertId();
      }
    }

    $stmt = $pdo->prepare("INSERT INTO participants (colloque_id, user_id) VALUES (?, ?)");
    $stmt->execute([$colloque_id, $user_id]);
    $participant_id = $pdo->lastInsertId();

    $date_debut = new DateTime($date);
    //Fatal error: Maximum execution time of 120 seconds exceeded in C:\xampp\htdocs\coloque\import_colloque_participants.php on line 82
    for ($j = 0; $j < $duree; $j++) {
      $date_presence = clone $date_debut;
      $date_presence->modify("+{$j} days");
      $stmt = $pdo->prepare("INSERT INTO presences (participant_id, date_presence, status) VALUES (?, ?, 'absent')");
      $stmt->execute([$participant_id, $date_presence->format('Y-m-d')]);
    }
  }

  header("Location: liste_colloques.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Importer un colloque</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <h2 class="mb-4">Créer un nouveau colloque</h2>
  <form method="post" enctype="multipart/form-data">
    <div class="mb-3">
      <label for="titre" class="form-label">Titre</label>
      <input type="text" class="form-control" id="titre" name="titre" required>
    </div>
    <div class="mb-3">
      <label for="lieu" class="form-label">Lieu</label>
      <input type="text" class="form-control" id="lieu" name="lieu" required>
    </div>
    <div class="mb-3">
      <label for="date" class="form-label">Date</label>
      <input type="date" class="form-control" id="date" name="date" required>
    </div>
    <div class="mb-3">
      <label for="heure" class="form-label">Heure</label>
      <input type="time" class="form-control" id="heure" name="heure" required>
    </div>
    <div class="mb-3">
      <label for="duree" class="form-label">Durée (en jours)</label>
      <input type="number" class="form-control" id="duree" name="duree" required>
    </div>
    <div class="mb-3">
      <label for="description" class="form-label">Description</label>
      <textarea class="form-control" id="description" name="description"></textarea>
    </div>
    <div class="mb-3">
      <label for="fichier" class="form-label">Fichier Excel</label>
      <input type="file" class="form-control" id="fichier" name="fichier" accept=".xls,.xlsx" required>
    </div>
    <button type="submit" class="btn btn-success">Importer</button>
  </form>
</body>
</html>
