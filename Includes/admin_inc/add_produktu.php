<?php
require_once __DIR__ . '/../log_reg_inc/auth.inc.php';
require_once __DIR__ . '/../dbh.inc.php';
require_once __DIR__ . '/admin_paligs.php';

parbauditAutorizaciju('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: ../../Skats/admin.php#pievienot-preces');
	exit;
}

function cleanText(string $value): string
{
	return trim($value);
}

$name = cleanText((string) ($_POST['name'] ?? ''));
$description = cleanText((string) ($_POST['description'] ?? ''));
$quantity = (int) ($_POST['quantity'] ?? 0);
$shelfLocation = cleanText((string) ($_POST['shelf_location'] ?? ''));

try {
	if (!isset($pdo) || !($pdo instanceof PDO)) {
		throw new RuntimeException('Nav atrasts datubāzes pieslēgums.');
	}

	if ($name === '') {
		throw new RuntimeException('Preces nosaukums nedrīkst būt tukšs.');
	}

	if (mb_strlen($name, 'UTF-8') > 150) {
		throw new RuntimeException('Preces nosaukums ir pārāk garš (maksimāli 150 simboli).');
	}

	if (mb_strlen($description, 'UTF-8') > 5000) {
		throw new RuntimeException('Preces apraksts ir pārāk garš (maksimāli 5000 simboli).');
	}

	if ($quantity < 0) {
		throw new RuntimeException('Daudzums nevar būt negatīvs.');
	}

	if ($quantity > 1000000000) {
		throw new RuntimeException('Daudzums ir pārāk liels.');
	}

	$shelfLocation = normalizeShelfLocation($shelfLocation);

	$stmt = $pdo->prepare('INSERT INTO products (name, description, quantity, shelf_location) VALUES (?, ?, ?, ?)');
	$stmt->execute([$name, $description, $quantity, $shelfLocation]);

	adminNovirzitArZinu('pievienot-preces', 'ok', 'Prece veiksmīgi pievienota.');
} catch (Throwable $e) {
	adminNovirzitArZinu('pievienot-preces', 'kluda', $e->getMessage());
}
