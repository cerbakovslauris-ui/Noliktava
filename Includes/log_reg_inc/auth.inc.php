<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function noteiktLomuAtslegu(int $roleId, string $roleName): string
{
    $loma = strtolower(trim($roleName));

    if ($roleId === 1 || str_contains($loma, 'admin')) {
        return 'admin';
    }

    if ($roleId === 2 || str_contains($loma, 'darbin')) {
        return 'darbinieks';
    }

    if ($roleId === 3 || str_contains($loma, 'kartot')) {
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

    $roleId = (int) ($_SESSION['role_id'] ?? 0);
    $roleName = (string) ($_SESSION['role_name'] ?? '');

    return [
        'user_id' => (int) $_SESSION['user_id'],
        'vards' => (string) ($_SESSION['vards'] ?? ''),
        'role_id' => $roleId,
        'role_name' => $roleName,
        'role_key' => noteiktLomuAtslegu($roleId, $roleName),
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
