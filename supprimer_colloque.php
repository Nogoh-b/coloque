<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/header.php';

if (!isset($_GET['id'])) {
  header("Location: liste_colloques.php");
  exit;
}

$id = intval($_GET['id']);

// Supprimer les participants liés au colloque
$stmt = $pdo->prepare("DELETE FROM participants WHERE colloque_id = ?");
$stmt->execute([$id]);

// Supprimer le colloque
$stmt = $pdo->prepare("DELETE FROM colloques WHERE id = ?");
$stmt->execute([$id]);

header("Location: liste_colloques.php");
exit;
