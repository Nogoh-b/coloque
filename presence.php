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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['terminate_colloque'])) {
        $stmt = $pdo->prepare("UPDATE colloques SET statut = 'termine' WHERE id = ?");
        $stmt->execute([$colloque_id]);
        header("Location: presence.php?colloque_id=$colloque_id");
        exit;
    }
    if (isset($_POST['mark_presence'])) {
        $id = intval($_POST['mark_presence']);
        $stmt = $pdo->prepare("UPDATE participants SET presence = 'oui' WHERE id = ? AND colloque_id = ?");
        $stmt->execute([$id, $colloque_id]);
        header("Location: presence.php?colloque_id=$colloque_id");
        exit;
    }
    if (isset($_POST['mark_absence'])) {
        $id = intval($_POST['mark_absence']);
        $stmt = $pdo->prepare("UPDATE participants SET presence = 'non' WHERE id = ? AND colloque_id = ?");
        $stmt->execute([$id, $colloque_id]);
        header("Location: presence.php?colloque_id=$colloque_id");
        exit;
    }
    if (isset($_POST['mark_selected_presence']) && isset($_POST['selected_participants'])) {
        foreach ($_POST['selected_participants'] as $id) {
            $stmt = $pdo->prepare("UPDATE participants SET presence = 'oui' WHERE id = ? AND colloque_id = ?");
            $stmt->execute([$id, $colloque_id]);
        }
        header("Location: presence.php?colloque_id=$colloque_id");
        exit;
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM participants WHERE colloque_id = ?");
$totalStmt->execute([$colloque_id]);
$totalRows = $totalStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$stmt = $pdo->prepare("SELECT * FROM participants WHERE colloque_id = ? ORDER BY nom_complet ASC LIMIT $limit OFFSET $offset");
$stmt->execute([$colloque_id]);
$participants = $stmt->fetchAll();
?>

<!-- Le reste du HTML -->
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Feuille d'émargement</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <form method="post" class="mb-3 d-flex gap-2">
    <button type="submit" name="terminate_colloque" class="btn btn-danger">✅ Marquer le colloque comme terminé</button>
    <button type="submit" name="mark_selected_presence" class="btn btn-success">✅ Marquer sélectionnés comme présents</button>
  </form>

  <a href="liste_colloques.php" class="btn btn-secondary mb-3">← Retour aux colloques</a>
  <a href="export_presence_pdf.php?colloque_id=<?= $colloque_id ?>" class="btn btn-outline-primary mb-3">📄 Exporter en PDF</a>

  <form method="post">
    <table class="table table-bordered">
      <thead>
        <tr>
          <th><input type="checkbox" id="select_all"></th>
          <th>Nom complet</th>
          <th>Email</th>
          <th>Institution</th>
          <th>Fonction</th>
          <th>Statut</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($participants as $p): ?>
          <tr>
            <td><input type="checkbox" name="selected_participants[]" value="<?= $p['id'] ?>"></td>
            <td><?= htmlspecialchars($p['nom_complet']) ?></td>
            <td><?= htmlspecialchars($p['email']) ?></td>
            <td><?= htmlspecialchars($p['institution']) ?></td>
            <td><?= htmlspecialchars($p['fonction']) ?></td>
            <td>
              <?php if ($p['presence'] === 'oui'): ?>
                <span class="badge bg-success">Présent</span>
              <?php elseif ($colloque['statut'] === 'termine' && $p['presence'] === 'non'): ?>
                <span class="badge bg-danger">Absent</span>
              <?php else: ?>
                <span class="badge bg-secondary">Non marqué</span>
              <?php endif ?>
            </td>
            <td>
              <?php if ($p['presence'] !== 'oui'): ?>
                <form method="post" style="display:inline">
                  <input type="hidden" name="mark_presence" value="<?= $p['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-success">Marquer présent</button>
                </form>
              <?php else: ?>
                <form method="post" style="display:inline">
                  <input type="hidden" name="mark_absence" value="<?= $p['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger">Marquer absent</button>
                </form>
              <?php endif ?>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
    <button type="submit" name="mark_selected_presence" class="btn btn-success">✅ Marquer sélectionnés comme présents</button>
  </form>

  <nav>
    <ul class="pagination justify-content-center">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
          <a class="page-link" href="?colloque_id=<?= $colloque_id ?>&page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>

  <script>
    document.getElementById('select_all').addEventListener('change', function() {
      document.querySelectorAll('input[name=\"selected_participants[]\"]').forEach(cb => cb.checked = this.checked);
    });
  </script>
</body>
</html>
