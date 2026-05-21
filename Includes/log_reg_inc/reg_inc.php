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


$vards = trim($_POST['lietotajvards'] ?? '');
$parole = $_POST['parole'] ?? '';
$paroleApstiprinat = $_POST['parole_apstiprinat'] ?? '';
$roleId = (int) ($_POST['role_id'] ?? 3);


if ($vards === '' || $parole === '' || $paroleApstiprinat === '') {
        die('Ludzu aizpildiet visus laukus.');
}

if ($parole !== $paroleApstiprinat) {
        die('Paroles nesakrit.');
}

$irVismaz8 = strlen($parole) >= 8;
$irLielaisBurts = preg_match('/[A-Z]/', $parole) === 1;
$irCipars = preg_match('/[0-9]/', $parole) === 1;
$irSpecialaRakstzime = preg_match('/[^a-zA-Z0-9]/', $parole) === 1;

if (!$irVismaz8 || !$irLielaisBurts || !$irCipars || !$irSpecialaRakstzime) {
        die('Parolei jabut vismaz 8 rakstzimem un tajaa jabut 1 lielajam burtam, 1 ciparam un 1 specialai rakstzimei.');
}

$roleStmt = $pdo->prepare('SELECT id, name FROM roles WHERE id = :id LIMIT 1');
$roleStmt->execute([':id' => $roleId]);
$role = $roleStmt->fetch();

if (!$role) {
        if ($roleId === 4) {
                $userRoleStmt = $pdo->prepare("SELECT id, name FROM roles WHERE LOWER(name) IN ('lietotajs', 'lietotājs', 'user') LIMIT 1");
                $userRoleStmt->execute();
                $role = $userRoleStmt->fetch();

                if (!$role) {
                        $createRoleStmt = $pdo->prepare('INSERT INTO roles (name) VALUES (:name)');
                        $createRoleStmt->execute([':name' => 'Lietotajs']);

                        $role = [
                                'id' => (int) $pdo->lastInsertId(),
                                'name' => 'Lietotajs',
                        ];
                }

                $roleId = (int) $role['id'];
        }
}

if (!$role) {
        die('Izveleta loma neeksiste.');
}

$parbaudeStmt = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
$parbaudeStmt->execute([':username' => $vards]);
if ($parbaudeStmt->fetch()) {
        die('Lietotajvards jau aiznemts.');
}

$paroleHash = password_hash($parole, PASSWORD_DEFAULT);

$sql = 'INSERT INTO users (username, password, role_id)
                VALUES (:username, :password, :role_id)';

$stmt = $pdo->prepare($sql);
$stmt->execute([
        ':username' => $vards,
        ':password' => $paroleHash,
        ':role_id' => $roleId,
]);

$_SESSION['user_id'] = (int) $pdo->lastInsertId();
$_SESSION['vards'] = $vards;
$_SESSION['role_id'] = $roleId;
$_SESSION['role_name'] = (string) $role['name'];

novirzitPecLomas($roleId, (string) $role['name']);