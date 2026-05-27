<?php
require_once __DIR__ . '/../log_reg_inc/auth.inc.php';
require_once __DIR__ . '/../dbh.inc.php';
require_once __DIR__ . '/admin_paligs.php';

parbauditAutorizaciju('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: ../../Skats/admin.php#rediget-preces');
	exit;
}

$id = (int) ($_POST['id'] ?? 0);
$name = trim((string) ($_POST['name'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$quantity = (int) ($_POST['quantity'] ?? 0);
$shelfLocation = trim((string) ($_POST['shelf_location'] ?? ''));

try {
	if ($id <= 0) {
		throw new RuntimeException('Nav atrasta prece, kuru rediģēt.');
	}

	if ($name === '') {
		throw new RuntimeException('Preces nosaukums nedrīkst būt tukšs.');
	}

	if ($quantity < 0) {
		throw new RuntimeException('Daudzums nevar būt negatīvs.');
	}

	$shelfLocation = normalizeShelfLocation($shelfLocation);

	$stmt = $pdo->prepare('UPDATE products SET name = ?, description = ?, quantity = ?, shelf_location = ? WHERE id = ?');
	$stmt->execute([$name, $description, $quantity, $shelfLocation, $id]);

	adminNovirzitArZinu('rediget-preces', 'ok', 'Prece veiksmīgi atjaunota.');
} catch (Throwable $e) {
	adminNovirzitArZinu('rediget-preces', 'kluda', $e->getMessage());
}
