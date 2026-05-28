<?php
session_start();
require_once __DIR__ . '/../dbh.inc.php';
require_once __DIR__ . '/../log_reg_inc/auth.inc.php';

$auth = parbauditAutorizaciju('admin');

function adminIegutPrecesDaudzumu(PDO $pdo, int $productId): ?int
{
    if ($productId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT quantity FROM products WHERE id = ? LIMIT 1');
    $stmt->execute([$productId]);
    $daudzums = $stmt->fetchColumn();

    if ($daudzums === false || $daudzums === null) {
        return null;
    }

    return max(0, (int) $daudzums);
}

function adminParbauditPrecesDaudzumuPlaukta(PDO $pdo, int $productId, int $jaunaisDaudzums): void
{
    if ($jaunaisDaudzums < 0) {
        throw new RuntimeException('Daudzums nedrīkst būt mazāks par 0.');
    }

    $precesDaudzums = adminIegutPrecesDaudzumu($pdo, $productId);
    if ($precesDaudzums === null) {
        throw new RuntimeException('Prece netika atrasta vai tai nav norādīts kopējais daudzums.');
    }

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(daudzums), 0) FROM preces_plauktos WHERE product_id = ?');
    $stmt->execute([$productId]);
    $jauPlauktos = max(0, (int) $stmt->fetchColumn());

    $pecIzmainam = $jauPlauktos + $jaunaisDaudzums;
    if ($pecIzmainam > $precesDaudzums) {
        $atlikums = max(0, $precesDaudzums - $jauPlauktos);
        throw new RuntimeException('Plauktā nevar ielikt vairāk nekā ir noliktavā. Pieejams ievietošanai: ' . $atlikums . ', preces kopējais daudzums: ' . $precesDaudzums . '.');
    }
}

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

        adminParbauditPrecesDaudzumuPlaukta($pdo, $productId, $daudzums);

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