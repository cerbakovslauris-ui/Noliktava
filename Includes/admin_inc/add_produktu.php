<?php
require_once __DIR__ . '/../log_reg_inc/auth.inc.php';
require_once __DIR__ . '/../dbh.inc.php';
require_once __DIR__ . '/admin_paligs.php';

parbauditAutorizaciju('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: ../../Skats/admin.php#pievienot-preces');
	exit;
}

$name = trim((string) ($_POST['name'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$quantity = (int) ($_POST['quantity'] ?? 0);
$shelfLocation = trim((string) ($_POST['shelf_location'] ?? ''));

try {
	if ($name === '') {
		throw new RuntimeException('Preces nosaukums nedrīkst būt tukšs.');
	}

	if ($quantity < 0) {
		throw new RuntimeException('Daudzums nevar būt negatīvs.');
	}

	$stmt = $pdo->prepare('INSERT INTO products (name, description, quantity, shelf_location) VALUES (?, ?, ?, ?)');
	$stmt->execute([$name, $description, $quantity, $shelfLocation]);

	adminNovirzitArZinu('pievienot-preces', 'ok', 'Prece veiksmīgi pievienota.');
} catch (Throwable $e) {
	adminNovirzitArZinu('pievienot-preces', 'kluda', $e->getMessage());
}
