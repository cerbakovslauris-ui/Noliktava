<?php
session_start();
require_once __DIR__ . '/../dbh.inc.php';
require_once __DIR__ . '/../log_reg_inc/auth.inc.php';

$auth = parbauditAutorizaciju('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int) ($_POST['product_id'] ?? 0);
    $plauktaNosaukums = (string) ($_POST['plaukts'] ?? '');
    $daudzums = (int) ($_POST['daudzums'] ?? 0);
    $piezime = (string) ($_POST['piezime'] ?? '');

    if ($productId <= 0 || $plauktaNosaukums === '') {
        $_SESSION['admin_flash'] = [
            'tips' => 'kluda',
            'teksts' => 'Jāievada prece un plaukts.',
        ];
        header('Location: ../../Skats/admin.php#kartosana');
        exit;
    }

    try {
        // Normalizē plaukta nosaukumu
        $plauktaNosaukums = strtoupper(trim($plauktaNosaukums));
        $plauktaNosaukums = preg_replace('/\s+/', '', $plauktaNosaukums);

        if (!preg_match('/^([A-F])-?([1-9]|[12][0-9]|30)$/', $plauktaNosaukums, $sakritiba)) {
            throw new RuntimeException('Plauktu var ievadīt tikai no A līdz F un no 1 līdz 30, piemēram, A-1 vai F-30.');
        }

        $plauktaNosaukums = $sakritiba[1] . '-' . $sakritiba[2];

        // Meklē vai izveido plauktu
        $meklet = $pdo->prepare('SELECT id FROM plaukti WHERE nosaukums = ? LIMIT 1');
        $meklet->execute([$plauktaNosaukums]);
        $plauktsId = $meklet->fetchColumn();

        if ($plauktsId === false) {
            $izveidot = $pdo->prepare('INSERT INTO plaukti (nosaukums, apraksts) VALUES (?, ?)');
            $izveidot->execute([$plauktaNosaukums, '']);
            $plauktsId = $pdo->lastInsertId();
        }

        // Pārbaudā vai produkts jau ir piesaistīts šim plauktam
        $parbaudePiesaisti = $pdo->prepare('SELECT id FROM preces_plauktos WHERE product_id = ? LIMIT 1');
        $parbaudePiesaisti->execute([$productId]);

        if ($parbaudePiesaisti->fetchColumn() !== false) {
            $_SESSION['admin_flash'] = [
                'tips' => 'kluda',
                'teksts' => 'Šī prece jau ir piesaistīta plauktam.',
            ];
            header('Location: ../../Skats/admin.php#kartosana');
            exit;
        }

        // Piesaista preci plauktam
        $insert = $pdo->prepare('INSERT INTO preces_plauktos (product_id, plaukts_id, daudzums, piezime) VALUES (?, ?, ?, ?)');
        $insert->execute([$productId, $plauktsId, $daudzums, $piezime]);

        $_SESSION['admin_flash'] = [
            'tips' => 'ok',
            'teksts' => 'Prece veiksmīgi piesaistīta plauktam.',
        ];
        header('Location: ../../Skats/admin.php#kartosana');
    } catch (Throwable $e) {
        $_SESSION['admin_flash'] = [
            'tips' => 'kluda',
            'teksts' => 'Kļūda: ' . $e->getMessage(),
        ];
        header('Location: ../../Skats/admin.php#kartosana');
    }
}
?>