<?php
session_start();
require_once __DIR__ . '/../dbh.inc.php';
require_once __DIR__ . '/../log_reg_inc/auth.inc.php';

$auth = parbauditAutorizaciju('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kartesanasId = (int) ($_POST['id'] ?? 0);

    if ($kartesanasId <= 0) {
        $_SESSION['admin_flash'] = [
            'tips' => 'kluda',
            'teksts' => 'Nepareiza kartēšanas ID.',
        ];
        header('Location: ../../Skats/admin.php#kartosana');
        exit;
    }

    try {
        // Iegūst plaukta ID pirms dzēšanas
        $getPlaukt = $pdo->prepare('SELECT plaukts_id FROM preces_plauktos WHERE id = ? LIMIT 1');
        $getPlaukt->execute([$kartesanasId]);
        $plauktsId = $getPlaukt->fetchColumn();

        // Dzēš kartēšanu
        $dzest = $pdo->prepare('DELETE FROM preces_plauktos WHERE id = ?');
        $dzest->execute([$kartesanasId]);

        // Dzēš plauktu, ja tas vairs nav izmantots
        if ($plauktsId !== false && $plauktsId > 0) {
            $parbaudePlauku = $pdo->prepare('SELECT id FROM preces_plauktos WHERE plaukts_id = ? LIMIT 1');
            $parbaudePlauku->execute([$plauktsId]);

            if ($parbaudePlauku->fetchColumn() === false) {
                $dziestPlauktu = $pdo->prepare('DELETE FROM plaukti WHERE id = ?');
                $dziestPlauktu->execute([$plauktsId]);
            }
        }

        $_SESSION['admin_flash'] = [
            'tips' => 'ok',
            'teksts' => 'Kartēšana veiksmīgi izdzēsta.',
        ];
        header('Location: ../../Skats/admin.php#kartosana');
    } catch (Throwable $e) {
        $_SESSION['admin_flash'] = [
            'tips' => 'kluda',
            'teksts' => 'Kļūda dzēšanā: ' . $e->getMessage(),
        ];
        header('Location: ../../Skats/admin.php#kartosana');
    }
}
?>