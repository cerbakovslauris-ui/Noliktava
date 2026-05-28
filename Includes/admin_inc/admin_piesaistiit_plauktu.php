<?php
session_start();
require_once __DIR__ . '/../dbh.inc.php';
require_once __DIR__ . '/../log_reg_inc/auth.inc.php';

parbauditAutorizaciju('admin');

function adminNovirzitArFlash(string $tips, string $teksts): void
{
    $_SESSION['admin_flash'] = [
        'tips' => $tips,
        'teksts' => $teksts,
    ];

    header('Location: ../../Skats/admin.php#kartosana');
    exit;
}

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

function adminParbauditPrecesDaudzumuPlaukta(PDO $pdo, int $productId, int $jaunaisDaudzums, int $iznemotKartesanasId = 0): void
{
    if ($jaunaisDaudzums < 0) {
        throw new RuntimeException('Daudzums nedrīkst būt mazāks par 0.');
    }

    $precesDaudzums = adminIegutPrecesDaudzumu($pdo, $productId);
    if ($precesDaudzums === null) {
        throw new RuntimeException('Prece netika atrasta vai tai nav norādīts kopējais daudzums.');
    }

    if ($iznemotKartesanasId > 0) {
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(daudzums), 0) FROM preces_plauktos WHERE product_id = ? AND id <> ?');
        $stmt->execute([$productId, $iznemotKartesanasId]);
    } else {
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(daudzums), 0) FROM preces_plauktos WHERE product_id = ?');
        $stmt->execute([$productId]);
    }

    $jauPlauktos = max(0, (int) $stmt->fetchColumn());
    $pecIzmainam = $jauPlauktos + $jaunaisDaudzums;

    if ($pecIzmainam > $precesDaudzums) {
        $atlikums = max(0, $precesDaudzums - $jauPlauktos);
        throw new RuntimeException('Plauktā nevar ielikt vairāk nekā ir noliktavā. Pieejams ievietošanai: ' . $atlikums . ', preces kopējais daudzums: ' . $precesDaudzums . '.');
    }
}

function adminNormalizePlauktaNosaukums(string $plauktaNosaukums): string
{
    $plauktaNosaukums = strtoupper(trim($plauktaNosaukums));
    $plauktaNosaukums = preg_replace('/\s+/', '', $plauktaNosaukums) ?? '';

    if (!preg_match('/^([A-F])-?([1-9]|[12][0-9]|30)$/', $plauktaNosaukums, $sakritiba)) {
        throw new RuntimeException('Plauktu var ievadīt tikai no A līdz F un no 1 līdz 30, piemēram, A-1 vai F-30.');
    }

    return $sakritiba[1] . '-' . $sakritiba[2];
}

function adminAtrastVaiIzveidotPlauktu(PDO $pdo, string $plauktaNosaukums): int
{
    $meklet = $pdo->prepare('SELECT id FROM plaukti WHERE nosaukums = ? LIMIT 1');
    $meklet->execute([$plauktaNosaukums]);
    $plauktsId = $meklet->fetchColumn();

    if ($plauktsId === false) {
        $izveidot = $pdo->prepare('INSERT INTO plaukti (nosaukums, apraksts) VALUES (?, ?)');
        $izveidot->execute([$plauktaNosaukums, '']);
        return (int) $pdo->lastInsertId();
    }

    return (int) $plauktsId;
}

function adminPlauktsJauIzmantots(PDO $pdo, int $plauktsId, int $iznemotKartesanasId = 0): bool
{
    if ($iznemotKartesanasId > 0) {
        $stmt = $pdo->prepare('SELECT id FROM preces_plauktos WHERE plaukts_id = ? AND id <> ? LIMIT 1');
        $stmt->execute([$plauktsId, $iznemotKartesanasId]);
    } else {
        $stmt = $pdo->prepare('SELECT id FROM preces_plauktos WHERE plaukts_id = ? LIMIT 1');
        $stmt->execute([$plauktsId]);
    }

    return $stmt->fetchColumn() !== false;
}

function adminDzestTuksuPlauktu(PDO $pdo, int $plauktsId): void
{
    if ($plauktsId <= 0) {
        return;
    }

    $stmt = $pdo->prepare('SELECT id FROM preces_plauktos WHERE plaukts_id = ? LIMIT 1');
    $stmt->execute([$plauktsId]);

    if ($stmt->fetchColumn() === false) {
        $dzest = $pdo->prepare('DELETE FROM plaukti WHERE id = ?');
        $dzest->execute([$plauktsId]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'add_mapping');

    if ($action === 'update_mapping') {
        $id = (int) ($_POST['id'] ?? 0);
        $plauktaNosaukums = (string) ($_POST['plaukts'] ?? '');
        $daudzums = max(0, (int) ($_POST['daudzums'] ?? 0));
        $piezime = (string) ($_POST['piezime'] ?? '');

        if ($id <= 0 || $plauktaNosaukums === '') {
            adminNovirzitArFlash('kluda', 'Jānorāda korekti kārtošanas dati.');
        }

        try {
            $vecieDatiStmt = $pdo->prepare('SELECT product_id, plaukts_id FROM preces_plauktos WHERE id = ? LIMIT 1');
            $vecieDatiStmt->execute([$id]);
            $vecieDati = $vecieDatiStmt->fetch(PDO::FETCH_ASSOC);

            if (!$vecieDati) {
                throw new RuntimeException('Prece plauktā netika atrasta.');
            }

            $productId = (int) ($vecieDati['product_id'] ?? 0);
            $vecaisPlauktsId = (int) ($vecieDati['plaukts_id'] ?? 0);

            $plauktaNosaukums = adminNormalizePlauktaNosaukums($plauktaNosaukums);
            $plauktsId = adminAtrastVaiIzveidotPlauktu($pdo, $plauktaNosaukums);

            if (adminPlauktsJauIzmantots($pdo, $plauktsId, $id)) {
                throw new RuntimeException('Šis plaukts jau ir izmantots citai precei. Izvēlies citu plauktu.');
            }

            adminParbauditPrecesDaudzumuPlaukta($pdo, $productId, $daudzums, $id);

            $update = $pdo->prepare('UPDATE preces_plauktos SET plaukts_id = ?, daudzums = ?, piezime = ? WHERE id = ?');
            $update->execute([$plauktsId, $daudzums, $piezime, $id]);

            if ($vecaisPlauktsId !== $plauktsId) {
                adminDzestTuksuPlauktu($pdo, $vecaisPlauktsId);
            }

            adminNovirzitArFlash('ok', 'Kārtošanas ieraksts atjaunots.');
        } catch (Throwable $e) {
            adminNovirzitArFlash('kluda', 'Kļūda: ' . $e->getMessage());
        }
    }

    $productId = (int) ($_POST['product_id'] ?? 0);
    $plauktaNosaukums = (string) ($_POST['plaukts'] ?? '');
    $daudzums = max(0, (int) ($_POST['daudzums'] ?? 0));
    $piezime = (string) ($_POST['piezime'] ?? '');

    if ($productId <= 0 || $plauktaNosaukums === '') {
        adminNovirzitArFlash('kluda', 'Jāievada prece un plaukts.');
    }

    try {
        $plauktaNosaukums = adminNormalizePlauktaNosaukums($plauktaNosaukums);
        $plauktsId = adminAtrastVaiIzveidotPlauktu($pdo, $plauktaNosaukums);

        if (adminPlauktsJauIzmantots($pdo, $plauktsId)) {
            throw new RuntimeException('Šis plaukts jau ir izmantots. Izvēlies citu plauktu.');
        }

        $parbaudePiesaisti = $pdo->prepare('SELECT id FROM preces_plauktos WHERE product_id = ? LIMIT 1');
        $parbaudePiesaisti->execute([$productId]);

        if ($parbaudePiesaisti->fetchColumn() !== false) {
            adminNovirzitArFlash('kluda', 'Šī prece jau ir piesaistīta plauktam.');
        }

        adminParbauditPrecesDaudzumuPlaukta($pdo, $productId, $daudzums);


        $insert = $pdo->prepare('INSERT INTO preces_plauktos (product_id, plaukts_id, daudzums, piezime) VALUES (?, ?, ?, ?)');
        $insert->execute([$productId, $plauktsId, $daudzums, $piezime]);

        adminNovirzitArFlash('ok', 'Prece veiksmīgi piesaistīta plauktam.');
    } catch (Throwable $e) {
        adminNovirzitArFlash('kluda', 'Kļūda: ' . $e->getMessage());
    }
}
?>