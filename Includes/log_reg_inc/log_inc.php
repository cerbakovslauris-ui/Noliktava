<?php
session_start();
require_once __DIR__ . '/../dbh.inc.php';

function novirzitPecLomas(?int $roleId, ?string $roleName = null): void
{
    $normalizetaLoma = strtolower(trim((string) $roleName));

    if ($roleId === 1 || str_contains($normalizetaLoma, 'admin')) {
        header('Location: ../../Skats/admin.php');
        exit;
    }

    if ($roleId === 2 || str_contains($normalizetaLoma, 'darbin')) {
        header('Location: ../../Skats/darbinieks.php');
        exit;
    }

    if ($roleId === 3 || str_contains($normalizetaLoma, 'kartot') || str_contains($normalizetaLoma, 'kārtot')) {
        header('Location: ../../Skats/kartotajs.php');
        exit;
    }

    header('Location: ../../Skats/user.php');
    exit;
}

$lietotajvardsRaw = (string) ($_POST['lietotajvards'] ?? '');
$lietotajvards = trim($lietotajvardsRaw);
$parole = $_POST['parole'] ?? '';

if (preg_match('/\s/', $lietotajvardsRaw) === 1 || preg_match('/\s/', $parole) === 1) {
    die('Nepareizs lietotajvards vai parole.');
}

if ($lietotajvards === '' || $parole === '') {
    die('Nepareizs lietotajvards vai parole.');
}

$sql = 'SELECT u.id, u.username, u.password, u.role_id, r.name AS role_name
        FROM users u
        LEFT JOIN roles r ON r.id = u.role_id
        WHERE u.username = :username
        LIMIT 1';

$stmt = $pdo->prepare($sql);
$stmt->execute([':username' => $lietotajvards]);
$row = $stmt->fetch();

$irParoleDeriga = false;

if ($row) {
    if (password_verify($parole, $row['password'])) {
        $irParoleDeriga = true;
    } elseif ($row['password'] === $parole) {
        $irParoleDeriga = true;

        $jaunaParoleHash = password_hash($parole, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
        $updateStmt->execute([
            ':password' => $jaunaParoleHash,
            ':id' => (int) $row['id'],
        ]);
    }
}

if (!$row || !$irParoleDeriga) {
    die('Nepareizs lietotajvards vai parole.');
}

$_SESSION['user_id'] = (int) $row['id'];
$_SESSION['vards'] = $row['username'];
$_SESSION['role_id'] = (int) $row['role_id'];
$_SESSION['role_name'] = (string) ($row['role_name'] ?? '');

novirzitPecLomas((int) $row['role_id'], (string) ($row['role_name'] ?? ''));