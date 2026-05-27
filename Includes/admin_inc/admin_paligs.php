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
    $irSpecialaRakstzime = preg_match('/[^a-zA-Z0-9\s]/', $parole) === 1;
    $navAtstarpes = preg_match('/\s/', $parole) === 0;

    return $irVismaz8 && $irLielaisBurts && $irCipars && $irSpecialaRakstzime && $navAtstarpes;
}

function normalizeShelfLocation(string $value): string
{
    $value = strtoupper(trim($value));
    $value = str_replace(' ', '', $value);

    if ($value === '') {
        return '';
    }

    if (!preg_match('/^([A-F])-?([1-9]|[12][0-9]|30)$/', $value, $matches)) {
        throw new RuntimeException('Plaukta vietai jābūt no A līdz F un no 1 līdz 30, piemēram, A-1 vai F-30.');
    }

    return $matches[1] . '-' . $matches[2];
}
