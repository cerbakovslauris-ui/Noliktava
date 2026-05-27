<?php
require_once __DIR__ . '/../Includes/log_reg_inc/auth.inc.php';
$auth = parbauditAutorizaciju('kartotajs');

$lietotajaVards = (string) ($auth['vards'] ?? 'Kartotājs');
$lietotajaVardsRedzams = $lietotajaVards;

/*
    Šis fails izmanto PDO savienojumu $pdo.
    Ja tavā projektā datu bāzes pieslēguma fails saucas citādi,
    pievieno to zemāk $dbFaili sarakstā.
*/
$dbFaili = [
    __DIR__ . '/../Includes/db.inc.php',
    __DIR__ . '/../Includes/dbh.inc.php',
    __DIR__ . '/../Includes/database.inc.php',
    __DIR__ . '/../Includes/log_reg_inc/db.inc.php',
    __DIR__ . '/../Includes/log_reg_inc/dbh.inc.php',
    __DIR__ . '/../Includes/log_reg_inc/database.inc.php',
];

foreach ($dbFaili as $fails) {
    if (file_exists($fails)) {
        require_once $fails;
        break;
    }
}

$zina = '';
$kluda = '';

function kartotajsNovirzitArZinu(string $hash, string $tips, string $teksts): void
{
    $_SESSION['kartotajs_flash'] = [
        'tips' => $tips,
        'teksts' => $teksts,
    ];

    header('Location: kartotajs.php#' . $hash);
    exit;
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function hasPdo(): bool
{
    return isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO;
}

function dbDriver(): string
{
    return hasPdo() ? (string) $GLOBALS['pdo']->getAttribute(PDO::ATTR_DRIVER_NAME) : '';
}

function tabulaEksiste(string $nosaukums): bool
{
    if (!hasPdo()) {
        return false;
    }

    try {
        $stmt = $GLOBALS['pdo']->prepare('SELECT 1 FROM ' . $nosaukums . ' LIMIT 1');
        $stmt->execute();
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function kolonnas(string $tabula): array
{
    if (!hasPdo()) {
        return [];
    }

    try {
        if (dbDriver() === 'sqlite') {
            $stmt = $GLOBALS['pdo']->query('PRAGMA table_info(' . $tabula . ')');
            return array_map(fn($r) => $r['name'], $stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        $stmt = $GLOBALS['pdo']->query('DESCRIBE ' . $tabula);
        return array_map(fn($r) => $r['Field'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Throwable $e) {
        return [];
    }
}

function pirmaKolonna(array $kolonnas, array $iespejas, string $noklusejums): string
{
    foreach ($iespejas as $iespeja) {
        if (in_array($iespeja, $kolonnas, true)) {
            return $iespeja;
        }
    }
    return $noklusejums;
}

function normalizePlauktaNosaukums(string $nosaukums): string
{
    $nosaukums = strtoupper(trim($nosaukums));
    $nosaukums = preg_replace('/\s+/', '', $nosaukums) ?? '';

    if ($nosaukums === '') {
        throw new RuntimeException('Plaukta nosaukums nedrīkst būt tukšs.');
    }

    if (!preg_match('/^([A-F])-?([1-9]|[12][0-9]|30)$/', $nosaukums, $sakritiba)) {
        throw new RuntimeException('Plauktu var ievadīt tikai no A līdz F un no 1 līdz 30, piemēram, A-1 vai F-30.');
    }

    return $sakritiba[1] . '-' . $sakritiba[2];
}

function atrastVaiIzveidotPlauktu(PDO $pdo, string $nosaukums): int
{
    $nosaukums = normalizePlauktaNosaukums($nosaukums);

    $meklet = $pdo->prepare('SELECT id FROM plaukti WHERE nosaukums = ? LIMIT 1');
    $meklet->execute([$nosaukums]);
    $atrastsId = $meklet->fetchColumn();

    if ($atrastsId !== false) {
        return (int) $atrastsId;
    }

    $izveidot = $pdo->prepare('INSERT INTO plaukti (nosaukums, apraksts) VALUES (?, ?)');
    $izveidot->execute([$nosaukums, '']);

    return (int) $pdo->lastInsertId();
}

function plauktsJauIzmantots(PDO $pdo, int $plauktsId, int $iznemotKartesanasId = 0): bool
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

function dzestTuksuPlauktu(PDO $pdo, int $plauktsId): void
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


function sakoptKartotajaDatus(PDO $pdo, ?string $produktuTabula = null): void
{
    if ($produktuTabula) {
        try {
            $pdo->exec('DELETE pp FROM preces_plauktos pp LEFT JOIN ' . $produktuTabula . ' p ON p.id = pp.product_id WHERE p.id IS NULL');
        } catch (Throwable $e) {
            try {
                $pdo->exec('DELETE FROM preces_plauktos WHERE product_id NOT IN (SELECT id FROM ' . $produktuTabula . ')');
            } catch (Throwable $e2) {
                // Ja datu bāze neatbalsta šo sintaksi, vienkārši turpinām bez kļūdas.
            }
        }
    }

    try {
        $pdo->exec('DELETE pl FROM plaukti pl LEFT JOIN preces_plauktos pp ON pp.plaukts_id = pl.id WHERE pp.id IS NULL');
    } catch (Throwable $e) {
        try {
            $pdo->exec('DELETE FROM plaukti WHERE id NOT IN (SELECT DISTINCT plaukts_id FROM preces_plauktos WHERE plaukts_id IS NOT NULL)');
        } catch (Throwable $e2) {
            // Ja datu bāze neatbalsta šo sintaksi, vienkārši turpinām bez kļūdas.
        }
    }
}

function izveidotKartotajaTabulas(): void
{
    if (!hasPdo()) {
        return;
    }

    if (dbDriver() === 'sqlite') {
        $GLOBALS['pdo']->exec("CREATE TABLE IF NOT EXISTS plaukti (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nosaukums TEXT NOT NULL,
            apraksts TEXT DEFAULT '',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        $GLOBALS['pdo']->exec("CREATE TABLE IF NOT EXISTS preces_plauktos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            plaukts_id INTEGER NOT NULL,
            daudzums INTEGER DEFAULT 0,
            piezime TEXT DEFAULT '',
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        return;
    }

    $GLOBALS['pdo']->exec("CREATE TABLE IF NOT EXISTS plaukti (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nosaukums VARCHAR(120) NOT NULL,
        apraksts TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $GLOBALS['pdo']->exec("CREATE TABLE IF NOT EXISTS preces_plauktos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        plaukts_id INT NOT NULL,
        daudzums INT DEFAULT 0,
        piezime TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_product_id (product_id),
        INDEX idx_plaukts_id (plaukts_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

if (!hasPdo()) {
    $kluda = 'Nav atrasts datu bāzes pieslēgums $pdo. Pārbaudi, kā saucas tavs DB pieslēguma fails Includes mapē.';
} else {
    try {
        izveidotKartotajaTabulas();
    } catch (Throwable $e) {
        $kluda = 'Neizdevās sagatavot kartotāja tabulas: ' . $e->getMessage();
    }
}

$produktuTabula = null;
foreach (['products', 'preces', 'produkti'] as $tabula) {
    if (tabulaEksiste($tabula)) {
        $produktuTabula = $tabula;
        break;
    }
}

$produktuKolonnas = $produktuTabula ? kolonnas($produktuTabula) : [];
$produktaNosaukumaKolonna = pirmaKolonna($produktuKolonnas, ['name', 'nosaukums', 'title', 'produkts'], 'name');
$produktaDaudzumaKolonna = pirmaKolonna($produktuKolonnas, ['quantity', 'daudzums', 'stock', 'skaits'], 'quantity');
$produktaAprakstaKolonna = pirmaKolonna($produktuKolonnas, ['description', 'apraksts', 'piezime'], 'description');

if (hasPdo()) {
    sakoptKartotajaDatus($pdo, $produktuTabula);
}

$flashZina = $_SESSION['kartotajs_flash'] ?? null;
unset($_SESSION['kartotajs_flash']);

if (is_array($flashZina) && isset($flashZina['teksts'])) {
    if (($flashZina['tips'] ?? '') === 'ok') {
        $zina = (string) $flashZina['teksts'];
    } else {
        $kluda = (string) $flashZina['teksts'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hasPdo()) {
        kartotajsNovirzitArZinu('kartesana', 'kluda', 'Nav datu bāzes pieslēguma. Mēģini vēlreiz vēlāk.');
    }

    $action = $_POST['action'] ?? '';
    $teksts = '';
    $tips = 'ok';

    try {
        if ($action === 'add_mapping') {
            $productId = (int) ($_POST['product_id'] ?? 0);
            $plauktaNosaukums = (string) ($_POST['plaukts'] ?? '');
            $daudzums = max(0, (int) ($_POST['daudzums'] ?? 0));
            $piezime = trim($_POST['piezime'] ?? '');
            $plauktsId = atrastVaiIzveidotPlauktu($pdo, $plauktaNosaukums);

            if ($productId <= 0) {
                throw new RuntimeException('Izvēlies preci un ievadi plauktu.');
            }

            if (plauktsJauIzmantots($pdo, $plauktsId)) {
                throw new RuntimeException('Šis plaukts jau ir izmantots. Izvēlies citu plauktu.');
            }

            $stmt = $pdo->prepare('INSERT INTO preces_plauktos (product_id, plaukts_id, daudzums, piezime) VALUES (?, ?, ?, ?)');
            $stmt->execute([$productId, $plauktsId, $daudzums, $piezime]);
            $teksts = 'Prece piesaistīta plauktam.';
        }

        if ($action === 'update_mapping') {
            $id = (int) ($_POST['id'] ?? 0);
            $plauktaNosaukums = (string) ($_POST['plaukts'] ?? '');
            $daudzums = max(0, (int) ($_POST['daudzums'] ?? 0));
            $piezime = trim($_POST['piezime'] ?? '');

            if ($id <= 0) {
                throw new RuntimeException('Pārbaudi kartēšanas datus.');
            }

            $vecaisPlauktsStmt = $pdo->prepare('SELECT plaukts_id FROM preces_plauktos WHERE id = ? LIMIT 1');
            $vecaisPlauktsStmt->execute([$id]);
            $vecaisPlauktsId = (int) $vecaisPlauktsStmt->fetchColumn();

            $plauktsId = atrastVaiIzveidotPlauktu($pdo, $plauktaNosaukums);

            if (plauktsJauIzmantots($pdo, $plauktsId, $id)) {
                throw new RuntimeException('Šis plaukts jau ir izmantots citai precei. Izvēlies citu plauktu.');
            }

            $stmt = $pdo->prepare('UPDATE preces_plauktos SET plaukts_id = ?, daudzums = ?, piezime = ? WHERE id = ?');
            $stmt->execute([$plauktsId, $daudzums, $piezime, $id]);

            if ($vecaisPlauktsId !== $plauktsId) {
                dzestTuksuPlauktu($pdo, $vecaisPlauktsId);
            }

            $teksts = 'Preces atrašanās vieta atjaunota.';
        }

        if ($action === 'delete_mapping') {
            $id = (int) ($_POST['id'] ?? 0);

            $plauktsStmt = $pdo->prepare('SELECT plaukts_id FROM preces_plauktos WHERE id = ? LIMIT 1');
            $plauktsStmt->execute([$id]);
            $plauktsId = (int) $plauktsStmt->fetchColumn();

            $stmt = $pdo->prepare('DELETE FROM preces_plauktos WHERE id = ?');
            $stmt->execute([$id]);

            dzestTuksuPlauktu($pdo, $plauktsId);

            $teksts = 'Prece no plaukta noņemta.';
        }

        if ($teksts === '') {
            throw new RuntimeException('Nezināma darbība. Mēģini vēlreiz.');
        }
    } catch (Throwable $e) {
        $tips = 'kluda';
        $teksts = $e->getMessage();
    }

    kartotajsNovirzitArZinu('kartesana', $tips, $teksts);
}

$plaukti = [];
$preces = [];
$kartesanas = [];
$statistika = [
    'plaukti' => 0,
    'kartetas_preces' => 0,
    'kop_daudzums' => 0,
];

if (hasPdo()) {
    try {
        if ($produktuTabula) {
            $precesSql = 'SELECT id, ' . $produktaNosaukumaKolonna . ' AS nosaukums';
            if (in_array($produktaDaudzumaKolonna, $produktuKolonnas, true)) {
                $precesSql .= ', ' . $produktaDaudzumaKolonna . ' AS daudzums_kopa';
            } else {
                $precesSql .= ', NULL AS daudzums_kopa';
            }
            $precesSql .= ' FROM ' . $produktuTabula . ' ORDER BY ' . $produktaNosaukumaKolonna . ' ASC';
            $preces = $pdo->query($precesSql)->fetchAll(PDO::FETCH_ASSOC);

            $sql = 'SELECT pp.id, pp.product_id, pp.plaukts_id, pp.daudzums, pp.piezime, pp.updated_at,
                           p.' . $produktaNosaukumaKolonna . ' AS preces_nosaukums,
                          pl.nosaukums AS plaukts_nosaukums
                    FROM preces_plauktos pp
                    INNER JOIN ' . $produktuTabula . ' p ON p.id = pp.product_id
                    INNER JOIN plaukti pl ON pl.id = pp.plaukts_id
                    ORDER BY pl.nosaukums ASC, p.' . $produktaNosaukumaKolonna . ' ASC';
            $kartesanas = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $statistika['kartetas_preces'] = count($kartesanas);
            $statistika['kop_daudzums'] = array_sum(array_map(fn($r) => (int) $r['daudzums'], $kartesanas));

            $plauktuSaraksts = [];
            foreach ($kartesanas as $rinda) {
                $plauktsId = (int) $rinda['plaukts_id'];
                if (!isset($plauktuSaraksts[$plauktsId])) {
                    $plauktuSaraksts[$plauktsId] = [
                        'id' => $plauktsId,
                        'nosaukums' => $rinda['plaukts_nosaukums'],
                    ];
                }
            }
            $plaukti = array_values($plauktuSaraksts);
            $statistika['plaukti'] = count($plaukti);
        }
    } catch (Throwable $e) {
        $kluda = $kluda ?: 'Neizdevās ielādēt datus: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plauktu kārtotājs</title>
    <link rel="stylesheet" href="../Css/skats.css">
    <link rel="stylesheet" href="../Css/admin.css">
    <link rel="stylesheet" href="../Css/kartotajs.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
    <header class="headers">
        <div class="log_reg">
            <span class="user" title="<?php echo e($lietotajaVards); ?>"><i class="fa fa-user" aria-hidden="true"></i><?php echo e($lietotajaVardsRedzams); ?></span>
            <a href="../Includes/log_reg_inc/logout_inc.php"><i class="fa fa-sign-out"></i>Iziet</a>
        </div>
        <div class="name">
            <h1>Plauktu kārtotājs</h1>
        </div>
    </header>

    <main class="admin-lapa kartotajs-lapa">
        <aside class="admin-nav-bar" aria-label="Kārtotāja navigācija">
            <h2>Kārtotāja panelis</h2>
            <nav>
                <ul>
                    <li><a class="active" href="#kartesana"><i class="fa fa-exchange" aria-hidden="true"></i>Kartēšana</a></li>
                    <li><a href="#atskaites"><i class="fa fa-line-chart" aria-hidden="true"></i>Atskaites</a></li>
                </ul>
            </nav>
        </aside>

        <section class="admin-saturs" aria-live="polite">
            <?php if ($kluda): ?>
                <div class="admin-kluda"><?php echo e($kluda); ?></div>
            <?php endif; ?>

            <?php if ($zina): ?>
                <div class="admin-ok-zina"><?php echo e($zina); ?></div>
            <?php endif; ?>

            <article id="kartesana" class="admin-panel active" data-panel>
                <h2>Kartēšana</h2>
                <p>Šeit var norādīt, kurā plauktā atrodas konkrēta prece un cik daudz vienību ir šajā vietā.</p>

                <?php if (!$produktuTabula): ?>
                    <div class="admin-kluda">Neatradu preču tabulu. Izveido tabulu <strong>products</strong>, <strong>preces</strong> vai <strong>produkti</strong>, lai varētu piesaistīt preces plauktiem.</div>
                <?php else: ?>
                    <div class="admin-tabula-wrap">
                        <h3>Piesaistīt preci plauktam</h3>
                        <form class="kartotajs-form kartotajs-form-4" method="post">
                            <input type="hidden" name="action" value="add_mapping">
                            <div>
                                <label>Prece</label>
                                <select name="product_id" required>
                                    <option value="">Izvēlies preci</option>
                                    <?php foreach ($preces as $prece): ?>
                                        <option value="<?php echo e($prece['id']); ?>"><?php echo e($prece['nosaukums']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label>Plaukts</label>
                                <input class="plaukta-ievade" type="text" name="plaukts" placeholder="Piemēram: A-12" data-kartesanas-id="0" pattern="[A-Fa-f]-?([1-9]|[12][0-9]|30)" title="Atļauts tikai A-F un 1-30, piemēram, A-1 vai F-30" required>
                            </div>
                            <div>
                                <label>Daudzums</label>
                                <input type="number" name="daudzums" min="0" value="0">
                            </div>
                            <div>
                                <label>Piezīme</label>
                                <input type="text" name="piezime" placeholder="Piemēram: apakšējais līmenis">
                            </div>
                            <button class="admin-poga" type="submit"><i class="fa fa-check"></i>Piesaistīt</button>
                        </form>
                    </div>

                    <div class="admin-tabula-wrap">
                        <h3>Preces plauktos</h3>
                        <?php if (!$kartesanas): ?>
                            <p>Vēl nav piesaistīta neviena prece.</p>
                        <?php else: ?>
                            <table class="admin-tabula kartotajs-tabula">
                                <thead>
                                    <tr>
                                        <th>Prece</th>
                                        <th>Plaukts</th>
                                        <th>Daudzums</th>
                                        <th>Piezīme</th>
                                        <th>Darbības</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kartesanas as $rinda): ?>
                                        <tr>
                                            <td><?php echo e($rinda['preces_nosaukums']); ?></td>
                                            <td>
                                                <input class="kartotajs-table-input plaukta-ievade" type="text" name="plaukts" value="<?php echo e($rinda['plaukts_nosaukums']); ?>" data-kartesanas-id="<?php echo e($rinda['id']); ?>" pattern="[A-Fa-f]-?([1-9]|[12][0-9]|30)" title="Atļauts tikai A-F un 1-30, piemēram, A-1 vai F-30" form="kartotajs-update-<?php echo e($rinda['id']); ?>" required>
                                            </td>
                                            <td>
                                                <input class="kartotajs-table-input kartotajs-daudzums-input" type="number" name="daudzums" min="0" value="<?php echo e($rinda['daudzums']); ?>" form="kartotajs-update-<?php echo e($rinda['id']); ?>">
                                            </td>
                                            <td>
                                                <input class="kartotajs-table-input" type="text" name="piezime" value="<?php echo e($rinda['piezime'] ?? ''); ?>" placeholder="Piezīme" form="kartotajs-update-<?php echo e($rinda['id']); ?>">
                                            </td>
                                            <td>
                                                <form id="kartotajs-update-<?php echo e($rinda['id']); ?>" method="post">
                                                    <input type="hidden" name="action" value="update_mapping">
                                                    <input type="hidden" name="id" value="<?php echo e($rinda['id']); ?>">
                                                </form>
                                                <form id="kartotajs-delete-<?php echo e($rinda['id']); ?>" class="kartotajs-delete-form" method="post" onsubmit="return confirm('Dzēst šo piesaisti?');">
                                                    <input type="hidden" name="action" value="delete_mapping">
                                                    <input type="hidden" name="id" value="<?php echo e($rinda['id']); ?>">
                                                </form>
                                                <div class="kartotajs-darbibas">
                                                    <button class="admin-poga admin-poga-mainit" type="submit" form="kartotajs-update-<?php echo e($rinda['id']); ?>">Saglabāt</button>
                                                    <button class="admin-poga admin-poga-dzest" type="submit" form="kartotajs-delete-<?php echo e($rinda['id']); ?>">Dzēst</button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </article>

            <article id="atskaites" class="admin-panel" data-panel>
                <h2>Atskaites</h2>
                <p>Īss kopsavilkums par plauktiem un precēm, kas jau piesaistītas plauktiem.</p>

                <div class="kartotajs-statistika">
                    <div>
                        <span>Plaukti</span>
                        <strong><?php echo e($statistika['plaukti']); ?></strong>
                    </div>
                    <div>
                        <span>Kartētas preces</span>
                        <strong><?php echo e($statistika['kartetas_preces']); ?></strong>
                    </div>
                    <div>
                        <span>Kopējais daudzums plauktos</span>
                        <strong><?php echo e($statistika['kop_daudzums']); ?></strong>
                    </div>
                </div>

                <div class="admin-tabula-wrap">
                    <h3>Plauktu noslodze</h3>
                    <?php if (!$plaukti): ?>
                        <p>Nav datu atskaitei.</p>
                    <?php else: ?>
                        <table class="admin-tabula">
                            <thead>
                                <tr>
                                    <th>Plaukts</th>
                                    <th>Preču ieraksti</th>
                                    <th>Daudzums</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($plaukti as $plaukts): ?>
                                    <?php
                                    $ieraksti = array_filter($kartesanas, fn($r) => (int) $r['plaukts_id'] === (int) $plaukts['id']);
                                    $daudzums = array_sum(array_map(fn($r) => (int) $r['daudzums'], $ieraksti));
                                    ?>
                                    <tr>
                                        <td><?php echo e($plaukts['nosaukums']); ?></td>
                                        <td><?php echo count($ieraksti); ?></td>
                                        <td><?php echo e($daudzums); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </article>
        </section>
    </main>

    <script>
        (function () {
            const links = Array.from(document.querySelectorAll('.admin-nav-bar a'));
            const panels = Array.from(document.querySelectorAll('[data-panel]'));

            function showPanelFromHash() {
                const hash = window.location.hash || '#kartesana';
                const targetId = hash.replace('#', '');
                const targetPanel = panels.find((panel) => panel.id === targetId) || panels[0];

                panels.forEach((panel) => {
                    panel.classList.toggle('active', panel === targetPanel);
                });

                links.forEach((link) => {
                    link.classList.toggle('active', link.getAttribute('href') === '#' + targetPanel.id);
                });
            }

            function sakartotPlauktu(value) {
                const notirits = value.toUpperCase().replace(/\s+/g, '').replace(/[^A-F0-9-]/g, '');
                const sakritiba = notirits.match(/^([A-F])-?(\d{0,2})/);

                if (!sakritiba) {
                    return notirits.slice(0, 1);
                }

                const burts = sakritiba[1];
                let cipari = sakritiba[2] || '';

                if (cipari !== '') {
                    let numurs = parseInt(cipari, 10);

                    if (Number.isNaN(numurs)) {
                        return burts;
                    }

                    if (numurs > 30) {
                        numurs = 30;
                    }

                    cipari = String(numurs);
                    return burts + '-' + cipari;
                }

                return burts;
            }

            const aiznemtiePlaukti = new Map([
                <?php foreach ($kartesanas as $rinda): ?>
                    ['<?php echo e(strtoupper((string) $rinda['plaukts_nosaukums'])); ?>', '<?php echo e($rinda['id']); ?>'],
                <?php endforeach; ?>
            ]);

            function parbauditVaiPlauktsBrivs(input) {
                const vertiba = sakartotPlauktu(input.value);
                const pasreizejaisId = input.dataset.kartesanasId || '0';
                const aiznemtsArId = aiznemtiePlaukti.get(vertiba);

                if (vertiba !== '' && aiznemtsArId && aiznemtsArId !== pasreizejaisId) {
                    input.setCustomValidity('Šis plaukts jau ir izmantots. Izvēlies citu plauktu.');
                } else {
                    input.setCustomValidity('');
                }
            }

            document.querySelectorAll('.plaukta-ievade').forEach((input) => {
                input.addEventListener('input', () => {
                    input.value = sakartotPlauktu(input.value);
                    parbauditVaiPlauktsBrivs(input);
                });

                input.addEventListener('blur', () => {
                    input.value = sakartotPlauktu(input.value);
                    parbauditVaiPlauktsBrivs(input);
                });

                parbauditVaiPlauktsBrivs(input);
            });

            window.addEventListener('hashchange', showPanelFromHash);
            showPanelFromHash();
        })();
    </script>
</body>
</html>
