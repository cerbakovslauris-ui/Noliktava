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

	$vecaStmt = $pdo->prepare('SELECT name, description, quantity, shelf_location FROM products WHERE id = ?');
	$vecaStmt->execute([$id]);
	$vecaPrece = $vecaStmt->fetch(PDO::FETCH_ASSOC);

	if (!$vecaPrece) {
		throw new RuntimeException('Prece netika atrasta.');
	}

	$stmt = $pdo->prepare('UPDATE products SET name = ?, description = ?, quantity = ?, shelf_location = ? WHERE id = ?');
	$stmt->execute([$name, $description, $quantity, $shelfLocation, $id]);

	$mainitieLauki = [];

	if ((string) ($vecaPrece['name'] ?? '') !== $name) {
		$mainitieLauki[] = 'nosaukums';
	}

	if ((string) ($vecaPrece['description'] ?? '') !== $description) {
		$mainitieLauki[] = 'apraksts';
	}

	if ((int) ($vecaPrece['quantity'] ?? 0) !== $quantity) {
		$mainitieLauki[] = 'daudzums';
	}

	if ((string) ($vecaPrece['shelf_location'] ?? '') !== $shelfLocation) {
		$mainitieLauki[] = 'plaukts';
	}

	if (empty($mainitieLauki)) {
		adminNovirzitArZinu('rediget-preces', 'ok', 'Izmaiņas nav veiktas.');
	}

	$teksti = [
		'nosaukums' => 'Preces nosaukums nomainīts',
		'apraksts' => 'Preces apraksts nomainīts',
		'plaukts' => 'Preces plaukts nomainīts',
		'daudzums' => 'Preces daudzums nomainīts',
	];

	if (count($mainitieLauki) === 1) {
		adminNovirzitArZinu('rediget-preces', 'ok', $teksti[$mainitieLauki[0]] . '.');
	}

	$laukuNosaukumi = [
		'nosaukums' => 'nosaukums',
		'apraksts' => 'apraksts',
		'plaukts' => 'plaukts',
		'daudzums' => 'daudzums',
	];

	$mainitieNosaukumi = [];
	foreach ($mainitieLauki as $lauks) {
		$mainitieNosaukumi[] = $laukuNosaukumi[$lauks];
	}

	$pedejais = array_pop($mainitieNosaukumi);
	$zina = 'Preces ' . implode(', ', $mainitieNosaukumi) . ' un ' . $pedejais . ' nomainīts.';

	adminNovirzitArZinu('rediget-preces', 'ok', $zina);
} catch (Throwable $e) {
	adminNovirzitArZinu('rediget-preces', 'kluda', $e->getMessage());
}
