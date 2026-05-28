<?php
require_once __DIR__ . '/../log_reg_inc/auth.inc.php';
require_once __DIR__ . '/../dbh.inc.php';
require_once __DIR__ . '/admin_paligs.php';

$auth = parbauditAutorizaciju('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: ../../Skats/admin.php#pasutit');
	exit;
}

function cleanText(string $value): string
{
	return trim($value);
}

$productId = (int) ($_POST['product_id'] ?? 0);
$quantity = (int) ($_POST['quantity'] ?? 0);
$userId = (int) ($_POST['user_id'] ?? ($auth['user_id'] ?? 0));

try {
	if (!isset($pdo) || !($pdo instanceof PDO)) {
		throw new RuntimeException('Nav atrasts datubāzes pieslēgums.');
	}

	if ($productId <= 0) {
		throw new RuntimeException('Izvēlies preci pasūtījumam.');
	}

	if ($quantity <= 0) {
		throw new RuntimeException('Pasūtījuma daudzumam jābūt lielākam par 0.');
	}

	if ($quantity > 1000000000) {
		throw new RuntimeException('Pasūtījuma daudzums ir pārāk liels.');
	}

	if ($userId <= 0) {
		throw new RuntimeException('Nav atrasts lietotāja ID pasūtījumam.');
	}

	$pdo->beginTransaction();

	$productStmt = $pdo->prepare('SELECT quantity FROM products WHERE id = ? FOR UPDATE');
	$productStmt->execute([$productId]);
	$product = $productStmt->fetch(PDO::FETCH_ASSOC);

	if (!$product) {
		throw new RuntimeException('Izvēlētā prece nav atrasta.');
	}

	$availableQuantity = (int) ($product['quantity'] ?? 0);
	if ($quantity > $availableQuantity) {
		throw new RuntimeException('Noliktavā nav pietiekams preces daudzums. Pieejams: ' . $availableQuantity . '.');
	}

	$updateStmt = $pdo->prepare('UPDATE products SET quantity = quantity - ? WHERE id = ?');
	$updateStmt->execute([$quantity, $productId]);

	$insertStmt = $pdo->prepare('INSERT INTO orders (product_id, user_id, quantity, status) VALUES (?, ?, ?, ?)');
	$insertStmt->execute([$productId, $userId, $quantity, 'jauns']);

	$pdo->commit();

	adminNovirzitArZinu('pasutit', 'ok', 'Pasūtījums izveidots.');
} catch (Throwable $e) {
	if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
		$pdo->rollBack();
	}

	adminNovirzitArZinu('pasutit', 'kluda', $e->getMessage());
}
