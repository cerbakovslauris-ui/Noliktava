<?php
require_once __DIR__ . '/../log_reg_inc/auth.inc.php';
require_once __DIR__ . '/../dbh.inc.php';
require_once __DIR__ . '/admin_paligs.php';

$auth = parbauditAutorizaciju('admin');
$aktivaAdminaId = (int) ($auth['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: ../../Skats/admin.php#lietotaji');
	exit;
}

$merkaLietotajaId = (int) ($_POST['lietotaja_id'] ?? 0);

try {
	if ($merkaLietotajaId <= 0) {
		throw new RuntimeException('Neatradām lietotāju dzēšanai.');
	}

	if ($merkaLietotajaId === $aktivaAdminaId) {
		throw new RuntimeException('Savu kontu dzēst nedrīkst.');
	}

	$dzestStmt = $pdo->prepare('DELETE FROM users WHERE id = :id LIMIT 1');
	$dzestStmt->execute([':id' => $merkaLietotajaId]);

	adminNovirzitArZinu('lietotaji', 'ok', 'Lietotājs izdzēsts.');
} catch (Throwable $e) {
	adminNovirzitArZinu('lietotaji', 'kluda', $e->getMessage());
}
