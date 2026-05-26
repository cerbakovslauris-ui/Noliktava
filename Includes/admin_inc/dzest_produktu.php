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

try {
	if ($id <= 0) {
		throw new RuntimeException('Nav atrasta prece, kuru dzēst.');
	}

	$parbaudeStmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE product_id = ?');
	$parbaudeStmt->execute([$id]);
	$pasutijumuSkaits = (int) $parbaudeStmt->fetchColumn();

	if ($pasutijumuSkaits > 0) {
		throw new RuntimeException('Nevar dzēst preci, jo tai ir saistīti pasūtījumi.');
	}

	$stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
	$stmt->execute([$id]);

	adminNovirzitArZinu('rediget-preces', 'ok', 'Prece veiksmīgi dzēsta.');
} catch (Throwable $e) {
	adminNovirzitArZinu('rediget-preces', 'kluda', $e->getMessage());
}
