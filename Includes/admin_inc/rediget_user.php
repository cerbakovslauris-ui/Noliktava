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

$userId = (int) ($_POST['lietotaja_id'] ?? 0);
$username = trim((string) ($_POST['username'] ?? ''));
$newPassword = (string) ($_POST['jauna_parole'] ?? '');

try {
    if ($userId <= 0 || $username === '') {
        throw new RuntimeException('Nevar saglabāt lietotāja datus.');
    }

    $parbaudeStmt = $pdo->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
    $parbaudeStmt->execute([':id' => $userId]);
    if (!$parbaudeStmt->fetch()) {
        throw new RuntimeException('Lietotājs nav atrasts.');
    }

    $unikalsStmt = $pdo->prepare('SELECT id FROM users WHERE username = :username AND id <> :id LIMIT 1');
    $unikalsStmt->execute([
        ':username' => $username,
        ':id' => $userId,
    ]);
    if ($unikalsStmt->fetch()) {
        throw new RuntimeException('Šis lietotājvārds jau tiek izmantots.');
    }

    if ($newPassword !== '') {
        if (!paroleAtbilstPrasibam($newPassword)) {
            throw new RuntimeException('Jaunajai parolei jābūt vismaz 8 rakstzīmēm ar lielo burtu, ciparu un speciālo rakstzīmi.');
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare('UPDATE users SET username = :username, password = :password WHERE id = :id LIMIT 1');
        $updateStmt->execute([
            ':username' => $username,
            ':password' => $passwordHash,
            ':id' => $userId,
        ]);
    } else {
        $updateStmt = $pdo->prepare('UPDATE users SET username = :username WHERE id = :id LIMIT 1');
        $updateStmt->execute([
            ':username' => $username,
            ':id' => $userId,
        ]);
    }

    if ($userId === $aktivaAdminaId) {
        $_SESSION['vards'] = $username;
    }

    adminNovirzitArZinu('lietotaji', 'ok', 'Lietotāja dati veiksmīgi atjaunoti.');
} catch (Throwable $e) {
    adminNovirzitArZinu('lietotaji', 'kluda', $e->getMessage());
}
