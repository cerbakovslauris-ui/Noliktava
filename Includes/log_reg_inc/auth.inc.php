<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function nobeigtSesijuUnNovirzitUzLogin(bool $sesijaBeigusies = false): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();

    $params = $sesijaBeigusies ? '?session_expired=1' : '';
    $saknesUrl = preg_replace('#/(Includes|Skats)/.*$#', '', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    header('Location: ' . $saknesUrl . '/Skats/log_reg_skats/login.php' . $params);
    exit;
}

function noteiktLomuAtslegu(int $roleId, string $roleName): string
{
    if ($roleId === 1) {
        return 'admin';
    }

    if ($roleId === 2) {
        return 'darbinieks';
    }

    if ($roleId === 3) {
        return 'kartotajs';
    }

    return 'user';
}

function novirzitUzLomasPaneli(string $lomaAtslega): void
{
    $paneli = [
        'admin' => '../Skats/admin.php',
        'darbinieks' => '../Skats/darbinieks.php',
        'kartotajs' => '../Skats/kartotajs.php',
        'user' => '../Skats/user.php',
    ];

    $panelis = $paneli[$lomaAtslega] ?? $paneli['user'];
    header('Location: ' . $panelis);
    exit;
}

function iegutAktivoLietotaju(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    if (!isset($GLOBALS['pdo']) || !($GLOBALS['pdo'] instanceof PDO)) {
        return null;
    }

    $stmt = $GLOBALS['pdo']->prepare('SELECT u.id, u.username, u.role_id, r.name AS role_name FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $lietotajsNoDb = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lietotajsNoDb) {
        nobeigtSesijuUnNovirzitUzLogin(true);
    }

    $roleId = (int) ($_SESSION['role_id'] ?? 0);
    $roleIdNoDb = (int) ($lietotajsNoDb['role_id'] ?? 0);
    $roleName = (string) ($lietotajsNoDb['role_name'] ?? ($_SESSION['role_name'] ?? ''));

    $_SESSION['vards'] = (string) ($lietotajsNoDb['username'] ?? $_SESSION['vards'] ?? '');
    $_SESSION['role_id'] = $roleIdNoDb;
    $_SESSION['role_name'] = $roleName;

    return [
        'user_id' => (int) $_SESSION['user_id'],
        'vards' => (string) $_SESSION['vards'],
        'role_id' => $roleIdNoDb ?: $roleId,
        'role_name' => $roleName,
        'role_key' => noteiktLomuAtslegu($roleIdNoDb ?: $roleId, $roleName),
    ];
}

function parbauditAutorizaciju(?string $prasitaLoma = null): array
{
    $lietotajs = iegutAktivoLietotaju();

    if ($lietotajs === null) {
        header('Location: log_reg_skats/login.php');
        exit;
    }

    $lomaAtslega = $lietotajs['role_key'];

    if ($prasitaLoma !== null && $lomaAtslega !== $prasitaLoma) {
        novirzitUzLomasPaneli($lomaAtslega);
    }

    return $lietotajs;
}
