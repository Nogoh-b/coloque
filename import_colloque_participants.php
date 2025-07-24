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

    $stmt = $pdo->prepare("INSERT INTO colloques (titre, lieu, date_colloque, heure_colloque, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$titre, $lieu, $date, $heure, $description]);
    $colloque_id = $pdo->lastInsertId();



    $spreadsheet = IOFactory::load($_FILES['fichier']['tmp_name']);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    for ($i = 1; $i < count($rows); $i++) {
        $r = $rows[$i];
        $stmt = $pdo->prepare("INSERT INTO participants (colloque_id, nom_complet, institution, fonction, email) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$colloque_id, $r[2], $r[3], $r[4], $r[5]]);
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
      <label>Titre du colloque</label>
      <input type="text" name="titre" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Lieu</label>
      <input type="text" name="lieu" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Date</label>
      <input type="date" name="date" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Heure</label>
      <input type="time" name="heure" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Description</label>
      <textarea name="description" class="form-control"></textarea>
    </div>
    <div class="mb-3">
      <label>Fichier Excel (.xlsx)</label>
      <input type="file" name="fichier" class="form-control" accept=".csv" required>
    </div>
    <button type="submit" class="btn btn-success">Importer le colloque</button>
  </form>
</body>
</html>
