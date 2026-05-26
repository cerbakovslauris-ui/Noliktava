<?php

function adminNovirzitArZinu(string $hash, string $tips, string $teksts): void
{
    $_SESSION['admin_flash'] = [
        'tips' => $tips,
        'teksts' => $teksts,
    ];

    header('Location: ../../Skats/admin.php#' . $hash);
    exit;
}

function paroleAtbilstPrasibam(string $parole): bool
{
    $irVismaz8 = strlen($parole) >= 8;
    $irLielaisBurts = preg_match('/[A-Z]/', $parole) === 1;
    $irCipars = preg_match('/[0-9]/', $parole) === 1;
    $irSpecialaRakstzime = preg_match('/[^a-zA-Z0-9]/', $parole) === 1;

    return $irVismaz8 && $irLielaisBurts && $irCipars && $irSpecialaRakstzime;
}
