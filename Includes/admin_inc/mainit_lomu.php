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
$jaunaLomaId = (int) ($_POST['jauna_loma_id'] ?? 0);

try {
	if ($merkaLietotajaId <= 0 || $jaunaLomaId <= 0) {
		throw new RuntimeException('Nepietiekami dati lomas maiņai.');
	}

	if ($merkaLietotajaId === $aktivaAdminaId) {
		throw new RuntimeException('Savam kontam lomu no šīs vietas mainīt nedrīkst.');
	}

	$lomaParbaudeStmt = $pdo->prepare('SELECT id FROM roles WHERE id = :id LIMIT 1');
	$lomaParbaudeStmt->execute([':id' => $jaunaLomaId]);

	if (!$lomaParbaudeStmt->fetch()) {
		throw new RuntimeException('Izvēlētā loma neeksistē.');
	}

	$lomaStmt = $pdo->prepare('UPDATE users SET role_id = :role_id WHERE id = :id LIMIT 1');
	$lomaStmt->execute([
		':role_id' => $jaunaLomaId,
		':id' => $merkaLietotajaId,
	]);

	adminNovirzitArZinu('lietotaji', 'ok', 'Loma veiksmīgi nomainīta.');
} catch (Throwable $e) {
	adminNovirzitArZinu('lietotaji', 'kluda', $e->getMessage());
}
