<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/header.php';

$colloque_id = isset($_GET['colloque_id']) ? intval($_GET['colloque_id']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("SELECT * FROM colloques WHERE id = ?");
$stmt->execute([$colloque_id]);
$colloque = $stmt->fetch();

if (!$colloque) {
    die("Colloque introuvable.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baseUrl = "presence.php?colloque_id=$colloque_id" . ($search !== '' ? "&search=" . urlencode($search) : '') . "&page=$page";
    if (isset($_POST['terminate_colloque'])) {
        $stmt = $pdo->prepare("UPDATE colloques SET statut = 'termine' WHERE id = ?");
        $stmt->execute([$colloque_id]);
        header("Location: $baseUrl");
        exit;
    }
    if (isset($_POST['mark_presence'])) {
        $id = intval($_POST['mark_presence']);
        $stmt = $pdo->prepare("UPDATE participants SET presence = 'oui' WHERE id = ? AND colloque_id = ?");
        $stmt->execute([$id, $colloque_id]);
        header("Location: $baseUrl");
        exit;
    }
    if (isset($_POST['mark_absence'])) {
        $id = intval($_POST['mark_absence']);
        $stmt = $pdo->prepare("UPDATE participants SET presence = 'non' WHERE id = ? AND colloque_id = ?");
        $stmt->execute([$id, $colloque_id]);
        header("Location: $baseUrl");
        exit;
    }
    if (isset($_POST['mark_selected_presence']) && isset($_POST['selected_participants'])) {
        foreach ($_POST['selected_participants'] as $id) {
            $stmt = $pdo->prepare("UPDATE participants SET presence = 'oui' WHERE id = ? AND colloque_id = ?");
            $stmt->execute([$id, $colloque_id]);
        }
        header("Location: $baseUrl");
        exit;
    }
}

$params = [$colloque_id];
$sql_where = "colloque_id = ?";

if ($search !== '') {
    $sql_where .= " AND (nom_complet LIKE ? OR email LIKE ? OR institution LIKE ? OR fonction LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like);
}

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM participants WHERE $sql_where");
$count_stmt->execute($params);
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $limit);

$sql = "SELECT * FROM participants WHERE $sql_where ORDER BY nom_complet ASC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$participants = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Feuille d'émargement</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <form method="post" class="mb-3 d-flex gap-2">
    <!-- <button type="submit" name="terminate_colloque" class="btn btn-danger">✅ Marquer le colloque comme terminé</button> -->
    <!-- <button type="submit" name="mark_selected_presence" class="btn btn-success">✅ Marquer sélectionnés comme présents</button> -->
  </form>

  <a href="liste_colloques.php" class="btn btn-secondary mb-3">← Retour aux colloques</a>
  <a href="export_presence_etat.php?colloque_id=<?= $colloque_id ?>" class="btn btn-outline-primary mb-3">📄 Exporter en PDF</a>

  <form method="get" class="mb-4">
    <input type="hidden" name="colloque_id" value="<?= $colloque_id ?>">
    <div class="input-group">
      <input type="text" name="search" class="form-control" placeholder="Rechercher un participant..." value="<?= htmlspecialchars($search) ?>">
      <button type="submit" class="btn btn-outline-secondary">Rechercher</button>
    </div>
  </form>

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
    <!-- <button type="submit" name="mark_selected_presence" class="btn btn-success">✅ Marquer sélectionnés comme présents</button> -->
  </form>

  <nav>
    <ul class="pagination justify-content-center">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
          <a class="page-link" href="?colloque_id=<?= $colloque_id ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>">Page <?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>

  <script>
    document.getElementById('select_all').addEventListener('change', function() {
      document.querySelectorAll('input[name=\"selected_participants[]\"]').forEach(cb => cb.checked = this.checked);
    });

    // Sauvegarde et restauration du scroll
    document.addEventListener('DOMContentLoaded', () => {
      if (sessionStorage.getItem('scrollTop')) {
        window.scrollTo(0, sessionStorage.getItem('scrollTop'));
        sessionStorage.removeItem('scrollTop');
      }
    });
    window.addEventListener('beforeunload', () => {
      sessionStorage.setItem('scrollTop', window.scrollY);
    });
  </script>
</body>
</html>
