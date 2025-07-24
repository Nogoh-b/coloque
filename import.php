<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/includes/db.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$participants = [];
$message = '';

if (isset($_POST['import'])) {
    // Création du colloque
    $titre = $_POST['titre'] ?? '';
    $description = $_POST['description'] ?? '';
    $lieu = $_POST['lieu'] ?? '';
    $date_colloque = $_POST['date_colloque'] ?? '';
    $heure_colloque = $_POST['heure_colloque'] ?? '';

    if ($titre && $date_colloque && $heure_colloque) {
        $stmt = $pdo->prepare("INSERT INTO colloques (titre, description, lieu, date_colloque, heure_colloque)
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$titre, $description, $lieu, $date_colloque, $heure_colloque]);
        $colloque_id = $pdo->lastInsertId();

        if (!empty($_FILES['excel_file']['tmp_name'])) {
            $file = $_FILES['excel_file']['tmp_name'];

            try {
                $spreadsheet = IOFactory::load($file);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();

                for ($i = 1; $i < count($rows); $i++) {
                    $row = $rows[$i];
                    $participant = [
                        'horodateur'   => $row[0],
                        'username'     => $row[1],
                        'nom_complet'  => $row[2],
                        'institution'  => $row[3],
                        'fonction'     => $row[4],
                        'email'        => $row[5],
                        'presence'     => 'non',
                        'colloque_id'  => $colloque_id,
                    ];

                    $stmt = $pdo->prepare("INSERT INTO participants (horodateur, username, nom_complet, institution, fonction, email, presence, colloque_id)
                                            VALUES (:horodateur, :username, :nom_complet, :institution, :fonction, :email, :presence, :colloque_id)");
                    $stmt->execute($participant);

                    $participants[] = $participant;
                }

                $message = "<div class='alert alert-success'>Colloque et participants importés avec succès.</div>";
            } catch (Exception $e) {
                $message = "<div class='alert alert-danger'>Erreur de lecture du fichier : " . $e->getMessage() . "</div>";
            }
        } else {
            $message = "<div class='alert alert-warning'>Aucun fichier Excel sélectionné.</div>";
        }
    } else {
        $message = "<div class='alert alert-warning'>Veuillez remplir tous les champs obligatoires du colloque.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Créer un colloque et importer les participants</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">

    <h2 class="mb-4">Créer un colloque et importer les participants</h2>

    <?= $message ?>

    <form method="post" enctype="multipart/form-data" class="mb-4">
        <h5>Informations sur le colloque</h5>
        <div class="mb-3">
            <label class="form-label">Titre *</label>
            <input type="text" name="titre" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Lieu</label>
            <input type="text" name="lieu" class="form-control">
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Date *</label>
                <input type="date" name="date_colloque" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Heure *</label>
                <input type="time" name="heure_colloque" class="form-control" required>
            </div>
        </div>

        <h5 class="mt-4">Fichier des participants</h5>
        <div class="mb-3">
            <input type="file" name="excel_file" accept=".xlsx" class="form-control" required>
        </div>

        <button type="submit" name="import" class="btn btn-success">Créer le colloque et importer</button>
    </form>

    <?php if (!empty($participants)): ?>
        <h4>Participants importés :</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Horodateur</th>
                    <th>Nom d'utilisateur</th>
                    <th>Nom complet</th>
                    <th>Institution</th>
                    <th>Fonction</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($participants as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['horodateur']) ?></td>
                        <td><?= htmlspecialchars($p['username']) ?></td>
                        <td><?= htmlspecialchars($p['nom_complet']) ?></td>
                        <td><?= htmlspecialchars($p['institution']) ?></td>
                        <td><?= htmlspecialchars($p['fonction']) ?></td>
                        <td><?= htmlspecialchars($p['email']) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>

</body>
</html>
