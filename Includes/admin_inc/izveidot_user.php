<?php
require_once __DIR__ . '/../log_reg_inc/auth.inc.php';
require_once __DIR__ . '/../dbh.inc.php';
require_once __DIR__ . '/admin_paligs.php';

parbauditAutorizaciju('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../Skats/admin.php#lietotaji');
    exit;
}

$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$roleId = (int) ($_POST['role_id'] ?? 0);

try {
    if ($username === '' || $password === '' || $roleId <= 0) {
        throw new RuntimeException('Aizpildi lietotājvārdu, paroli un lomu.');
    }

    if (!paroleAtbilstPrasibam($password)) {
        throw new RuntimeException('Parolei jābūt vismaz 8 rakstzīmēm, ar lielo burtu, ciparu un speciālo rakstzīmi, un bez atstarpēm.');
    }

    $lomaStmt = $pdo->prepare('SELECT id FROM roles WHERE id = :id LIMIT 1');
    $lomaStmt->execute([':id' => $roleId]);
    if (!$lomaStmt->fetch()) {
        throw new RuntimeException('Izvēlētā loma neeksistē.');
    }

    $parbaudeStmt = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
    $parbaudeStmt->execute([':username' => $username]);
    if ($parbaudeStmt->fetch()) {
        throw new RuntimeException('Lietotājvārds jau aizņemts.');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $insertStmt = $pdo->prepare('INSERT INTO users (username, password, role_id) VALUES (:username, :password, :role_id)');
    $insertStmt->execute([
        ':username' => $username,
        ':password' => $passwordHash,
        ':role_id' => $roleId,
    ]);

    adminNovirzitArZinu('lietotaji', 'ok', 'Lietotājs veiksmīgi izveidots.');
} catch (Throwable $e) {
    adminNovirzitArZinu('lietotaji', 'kluda', $e->getMessage());
}
