<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/header.php';

$colloque_id = isset($_GET['colloque_id']) ? intval($_GET['colloque_id']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("SELECT date_colloque, duree FROM colloques WHERE id = ?");
$stmt->execute([$colloque_id]);
$colloque = $stmt->fetch();

if (!$colloque) {
  die("Colloque introuvable.");
}

$dates = [];
$start = new DateTime($colloque['date_colloque']);
for ($i = 0; $i < $colloque['duree']; $i++) {
  $d = clone $start;
  $d->modify("+{$i} days");
  $dates[] = $d->format('Y-m-d');
}

$today = new DateTime();
$date_selected = new DateTime($date);
$can_emargement = $today->format('Y-m-d') === $date_selected->format('Y-m-d');


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_emargement) {
  if (isset($_POST['mark_presence'])) {
    $id = intval($_POST['mark_presence']);
    $stmt = $pdo->prepare("UPDATE presences SET status = 'present' WHERE participant_id = ? AND date_presence = ?");
    $stmt->execute([$id, $date]);
  }
  if (isset($_POST['mark_absence'])) {
    $id = intval($_POST['mark_absence']);
    $stmt = $pdo->prepare("UPDATE presences SET status = 'absent' WHERE participant_id = ? AND date_presence = ?");
    $stmt->execute([$id, $date]);
  }
  header("Location: presence.php?colloque_id=$colloque_id&date=$date&search=" . urlencode($search) . "&page=$page");
  exit;
}

$params = [$date, $colloque_id];
$sql_search = "";
if ($search !== '') {
  $sql_search = "AND (u.nom_complet LIKE ? OR u.email LIKE ? OR u.institution LIKE ? OR u.fonction LIKE ?)";
  $like = "%$search%";
  array_push($params, $like, $like, $like, $like);
}

$count_sql = "SELECT COUNT(*) FROM participants p JOIN users u ON u.id = p.user_id WHERE p.colloque_id = ? $sql_search";
$count_params = [$colloque_id];
if ($search !== '') {
  $like = "%$search%";
  array_push($count_params, $like, $like, $like, $like);
}
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($count_params);
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $limit);

$sql = "SELECT p.id AS participant_id, u.nom_complet, u.email, u.institution, u.fonction, pr.status 
  FROM participants p
  JOIN users u ON u.id = p.user_id
  LEFT JOIN presences pr ON pr.participant_id = p.id AND pr.date_presence = ?
  WHERE p.colloque_id = ? $sql_search
  ORDER BY u.nom_complet ASC
  LIMIT $limit OFFSET $offset";
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
<h2 class="mb-4">Emargement pour le <?= htmlspecialchars($date) ?></h2>
<a href="liste_colloques.php" class="btn btn-secondary mb-3">Retour aux colloques</a>

<form method="get" class="mb-4">
  <input type="hidden" name="colloque_id" value="<?= $colloque_id ?>">
  <label for="date" class="form-label">Choisir une date :</label>
  <select name="date" id="date" class="form-select mb-3" onchange="this.form.submit()">
    <?php foreach ($dates as $d): ?>
      <option value="<?= $d ?>" <?= $d === $date ? 'selected' : '' ?>><?= $d ?></option>
    <?php endforeach; ?>
  </select>
  <div class="input-group">
    <input type="text" name="search" class="form-control" placeholder="Rechercher un participant..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-outline-secondary">Rechercher</button>
  </div>
</form>

<table class="table table-bordered">
  <thead>
    <tr>
      <th>Nom</th>
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
      <td><?= htmlspecialchars($p['nom_complet']) ?></td>
      <td><?= htmlspecialchars($p['email']) ?></td>
      <td><?= htmlspecialchars($p['institution']) ?></td>
      <td><?= htmlspecialchars($p['fonction']) ?></td>
      <td>
        <?php if ($p['status'] === 'present'): ?>
          <span class="badge bg-success">Présent</span>
        <?php elseif ($p['status'] === 'absent'): ?>
          <span class="badge bg-danger">Absent</span>
        <?php else: ?>
          <span class="badge bg-secondary">Non marqué</span>
        <?php endif ?>
      </td>
      <td>
        <?php if ($can_emargement): ?>
          <?php if ($p['status'] !== 'present'): ?>
            <form method="post" style="display:inline">
              <input type="hidden" name="mark_presence" value="<?= $p['participant_id'] ?>">
              <button type="submit" class="btn btn-sm btn-success">Marquer présent</button>
            </form>
          <?php else: ?>
            <form method="post" style="display:inline">
              <input type="hidden" name="mark_absence" value="<?= $p['participant_id'] ?>">
              <button type="submit" class="btn btn-sm btn-danger">Marquer absent</button>
            </form>
          <?php endif ?>
        <?php endif ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<nav>
  <ul class="pagination justify-content-center">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
      <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
        <a class="page-link" href="?colloque_id=<?= $colloque_id ?>&date=<?= $date ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>">Page <?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>

<script>
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
