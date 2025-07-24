<?php
require __DIR__ . '/vendor/autoload.php';

// Création d'un nouveau fichier Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Écriture de quelques données
$sheet->setCellValue('A1', 'Nom');
$sheet->setCellValue('B1', 'Email');
$sheet->setCellValue('A2', 'Jean Dupont');
$sheet->setCellValue('B2', 'jean@example.com');
$sheet->setCellValue('A3', 'Marie Claire');
$sheet->setCellValue('B3', 'marie@example.com');

// Sauvegarde dans un fichier Excel
$writer = new Xlsx($spreadsheet);
$filename = 'participants.xlsx';

$writer->save($filename);

echo "✅ Fichier Excel '$filename' généré avec succès.";