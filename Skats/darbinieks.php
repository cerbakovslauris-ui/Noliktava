<?php
require_once __DIR__ . '/../Includes/dbh.inc.php';
require_once __DIR__ . '/../Includes/log_reg_inc/auth.inc.php';
$auth = parbauditAutorizaciju('darbinieks');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($pdo)) {
    $iespejamieDbFaili = [
        __DIR__ . '/../Includes/dbh.inc.php',
        __DIR__ . '/../Includes/db.inc.php',
        __DIR__ . '/../Includes/database.inc.php',
        __DIR__ . '/../Includes/config.php'
    ];

    foreach ($iespejamieDbFaili as $dbFails) {
        if (file_exists($dbFails)) {
            require_once $dbFails;
            break;
        }
    }
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die('Nav atrasts datubāzes pieslēgums. Pārbaudi, vai Includes mapē ir dbh.inc.php un tajā ir $pdo.');
}

$lietotajaVards = (string)($auth['vards'] ?? $auth['username'] ?? 'Darbinieks');
$lietotajaId = (int)($auth['id'] ?? $auth['user_id'] ?? 0);

$zina = '';
$zinaTips = 'ok';

function cleanText($value) {
    return trim((string)$value);
}

function redirectWithHash($hash, $teksts = '', $tips = 'ok') {
    if ($teksts !== '') {
        $_SESSION['darbinieks_zina'] = [
            'teksts' => $teksts,
            'tips' => $tips
        ];
    }

    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '#') . '#' . $hash);
    exit;
}

function normalizeShelfLocation($value) {
    $value = strtoupper(trim((string)$value));
    $value = str_replace(' ', '', $value);

    if ($value === '') {
        return '';
    }

    if (!preg_match('/^([A-F])-?([1-9]|[12][0-9]|30)$/', $value, $matches)) {
        throw new Exception('Plaukta vietai jābūt no A līdz F un no 1 līdz 30, piemēram, A-1 vai F-30.');
    }

    return $matches[1] . '-' . $matches[2];
}

if (!empty($_SESSION['darbinieks_zina'])) {
    $zina = (string)($_SESSION['darbinieks_zina']['teksts'] ?? '');
    $zinaTips = (string)($_SESSION['darbinieks_zina']['tips'] ?? 'ok');
    unset($_SESSION['darbinieks_zina']);
}

function restoreProductQuantity(PDO $pdo, int $productId, int $quantity): void {
    $stmt = $pdo->prepare('UPDATE products SET quantity = quantity + ? WHERE id = ?');
    $stmt->execute([$quantity, $productId]);
}

function reduceProductQuantity(PDO $pdo, int $productId, int $quantity): void {
    $productStmt = $pdo->prepare('SELECT quantity FROM products WHERE id = ? FOR UPDATE');
    $productStmt->execute([$productId]);
    $product = $productStmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Izvēlētā prece nav atrasta.');
    }

    $availableQuantity = (int)$product['quantity'];

    if ($quantity > $availableQuantity) {
        throw new Exception('Noliktavā nav pietiekams preces daudzums. Pieejams: ' . $availableQuantity . '.');
    }

    $updateStmt = $pdo->prepare('UPDATE products SET quantity = quantity - ? WHERE id = ?');
    $updateStmt->execute([$quantity, $productId]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_product') {
            $name = cleanText($_POST['name'] ?? '');
            $description = cleanText($_POST['description'] ?? '');
            $quantity = (int)($_POST['quantity'] ?? 0);
            $shelfLocation = normalizeShelfLocation($_POST['shelf_location'] ?? '');

            if ($name === '') {
                throw new Exception('Preces nosaukums nedrīkst būt tukšs.');
            }
            if ($quantity < 0) {
                throw new Exception('Daudzums nevar būt negatīvs.');
            }

            $stmt = $pdo->prepare('INSERT INTO products (name, description, quantity, shelf_location) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $description, $quantity, $shelfLocation]);
            redirectWithHash('noliktava', 'Prece pievienota.', 'ok');
        }

        if ($action === 'edit_product') {
            $id = (int)($_POST['id'] ?? 0);
            $name = cleanText($_POST['name'] ?? '');
            $description = cleanText($_POST['description'] ?? '');
            $quantity = (int)($_POST['quantity'] ?? 0);
            $shelfLocation = normalizeShelfLocation($_POST['shelf_location'] ?? '');

            if ($id <= 0) {
                throw new Exception('Nav atrasta prece, kuru rediģēt.');
            }
            if ($name === '') {
                throw new Exception('Preces nosaukums nedrīkst būt tukšs.');
            }
            if ($quantity < 0) {
                throw new Exception('Daudzums nevar būt negatīvs.');
            }

            $oldStmt = $pdo->prepare('SELECT name, description, quantity, shelf_location FROM products WHERE id = ?');
            $oldStmt->execute([$id]);
            $oldProduct = $oldStmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldProduct) {
                throw new Exception('Prece nav atrasta.');
            }

            $stmt = $pdo->prepare('UPDATE products SET name = ?, description = ?, quantity = ?, shelf_location = ? WHERE id = ?');
            $stmt->execute([$name, $description, $quantity, $shelfLocation, $id]);

            $izmainas = [];

            if ((string)$oldProduct['name'] !== $name) {
                $izmainas[] = 'nosaukums';
            }
            if ((string)($oldProduct['description'] ?? '') !== $description) {
                $izmainas[] = 'apraksts';
            }
            if ((int)$oldProduct['quantity'] !== $quantity) {
                $izmainas[] = 'daudzums';
            }
            if ((string)($oldProduct['shelf_location'] ?? '') !== $shelfLocation) {
                $izmainas[] = 'plaukts';
            }

            if (empty($izmainas)) {
                $zinojums = 'Prece saglabāta.';
            } elseif (count($izmainas) === 1) {
                if ($izmainas[0] === 'nosaukums') {
                    $zinojums = 'Preces nosaukums nomainīts.';
                } elseif ($izmainas[0] === 'apraksts') {
                    $zinojums = 'Preces apraksts nomainīts.';
                } elseif ($izmainas[0] === 'daudzums') {
                    $zinojums = 'Preces daudzums nomainīts.';
                } else {
                    $zinojums = 'Preces plaukts nomainīts.';
                }
            } else {
                $teksti = [
                    'nosaukums' => 'nosaukums',
                    'apraksts' => 'apraksts',
                    'daudzums' => 'daudzums',
                    'plaukts' => 'plaukts'
                ];

                $mainitieLauki = array_map(function ($lauks) use ($teksti) {
                    return $teksti[$lauks];
                }, $izmainas);

                $pedejais = array_pop($mainitieLauki);
                $zinojums = 'Preces ' . implode(', ', $mainitieLauki) . ' un ' . $pedejais . ' nomainīts.';
            }

            redirectWithHash('noliktava', $zinojums, 'ok');
        }

        if ($action === 'delete_product') {
            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception('Nav atrasta prece, kuru dzēst.');
            }

            $parbaudeStmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE product_id = ?');
            $parbaudeStmt->execute([$id]);
            $pasutijumuSkaits = (int)$parbaudeStmt->fetchColumn();

            if ($pasutijumuSkaits > 0) {
                throw new Exception('Nevar dzēst preci, jo tai ir saistīti pasūtījumi. Vispirms izdzēs vai atcel šīs preces pasūtījumus.');
            }

            $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
            $stmt->execute([$id]);
            redirectWithHash('noliktava', 'Prece izdzēsta.', 'ok');
        }

        if ($action === 'create_order') {
            $productId = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 0);

            if ($productId <= 0) {
                throw new Exception('Izvēlies preci pasūtījumam.');
            }
            if ($quantity <= 0) {
                throw new Exception('Pasūtījuma daudzumam jābūt lielākam par 0.');
            }
            if ($lietotajaId <= 0) {
                throw new Exception('Nav atrasts darbinieka lietotāja ID.');
            }

            $pdo->beginTransaction();

            reduceProductQuantity($pdo, $productId, $quantity);

            $stmt = $pdo->prepare('INSERT INTO orders (product_id, user_id, quantity, status) VALUES (?, ?, ?, ?)');
            $stmt->execute([$productId, $lietotajaId, $quantity, 'jauns']);

            $pdo->commit();
            redirectWithHash('pasutijumi', 'Pasūtījums izveidots.', 'ok');
        }

        if ($action === 'update_order_status') {
            $orderId = (int)($_POST['order_id'] ?? 0);
            $newStatus = cleanText($_POST['status'] ?? '');
            $allowedStatuses = ['jauns', 'pieņemts', 'izpildīts', 'atcelts'];

            if ($orderId <= 0) {
                throw new Exception('Nav atrasts pasūtījums.');
            }
            if (!in_array($newStatus, $allowedStatuses, true)) {
                throw new Exception('Nederīgs pasūtījuma statuss.');
            }

            $pdo->beginTransaction();

            $orderStmt = $pdo->prepare('SELECT id, product_id, quantity, status FROM orders WHERE id = ? FOR UPDATE');
            $orderStmt->execute([$orderId]);
            $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                throw new Exception('Pasūtījums nav atrasts.');
            }

            $oldStatus = (string)$order['status'];
            $productId = (int)$order['product_id'];
            $quantity = (int)$order['quantity'];

            if ($oldStatus !== 'atcelts' && $newStatus === 'atcelts') {
                restoreProductQuantity($pdo, $productId, $quantity);
            } elseif ($oldStatus === 'atcelts' && $newStatus !== 'atcelts') {
                reduceProductQuantity($pdo, $productId, $quantity);
            }

            $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
            $stmt->execute([$newStatus, $orderId]);

            $pdo->commit();
            redirectWithHash('pasutijumi', 'Pasūtījuma statuss mainīts.', 'ok');
        }

        if ($action === 'delete_order') {
            $orderId = (int)($_POST['order_id'] ?? 0);

            if ($orderId <= 0) {
                throw new Exception('Nav atrasts pasūtījums, kuru dzēst.');
            }

            $pdo->beginTransaction();

            $orderStmt = $pdo->prepare('SELECT id, product_id, quantity, status FROM orders WHERE id = ? FOR UPDATE');
            $orderStmt->execute([$orderId]);
            $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                throw new Exception('Pasūtījums nav atrasts.');
            }

            if ((string)$order['status'] !== 'atcelts') {
                restoreProductQuantity($pdo, (int)$order['product_id'], (int)$order['quantity']);
            }

            $deleteStmt = $pdo->prepare('DELETE FROM orders WHERE id = ?');
            $deleteStmt->execute([$orderId]);

            $pdo->commit();
            redirectWithHash('pasutijumi', 'Pasūtījums izdzēsts.', 'ok');
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $zina = $e->getMessage();
        $zinaTips = 'kluda';
    }
}

$products = $pdo->query('SELECT * FROM products ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);

$orderStmt = $pdo->query('
    SELECT o.id, o.product_id, o.quantity, o.status, o.created_at, p.name AS product_name, u.username
    FROM orders o
    JOIN products p ON p.id = o.product_id
    JOIN users u ON u.id = o.user_id
    ORDER BY o.id DESC
');
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

$report = [
    'product_count' => count($products),
    'total_stock_quantity' => 0,
    'order_count' => count($orders),
    'ordered_quantity_total' => 0,
    'active_order_count' => 0,
    'completed_order_count' => 0,
    'cancelled_order_count' => 0,
    'low_stock_count' => 0,
    'used_shelves' => [],
];

foreach ($products as $product) {
    $productQuantity = (int)$product['quantity'];
    $report['total_stock_quantity'] += $productQuantity;

    if ($productQuantity <= 5) {
        $report['low_stock_count']++;
    }

    if (!empty($product['shelf_location'])) {
        $report['used_shelves'][$product['shelf_location']] = true;
    }
}

foreach ($orders as $order) {
    $orderQuantity = (int)$order['quantity'];
    $report['ordered_quantity_total'] += $orderQuantity;

    if ($order['status'] === 'izpildīts') {
        $report['completed_order_count']++;
    } elseif ($order['status'] === 'atcelts') {
        $report['cancelled_order_count']++;
    } else {
        $report['active_order_count']++;
    }
}

$report['used_shelves_count'] = count($report['used_shelves']);

$productReportStmt = $pdo->query('
    SELECT
        p.id,
        p.name,
        p.quantity AS stock_quantity,
        p.shelf_location,
        COALESCE(SUM(CASE WHEN o.status <> "atcelts" THEN o.quantity ELSE 0 END), 0) AS ordered_quantity,
        COALESCE(SUM(CASE WHEN o.status = "izpildīts" THEN o.quantity ELSE 0 END), 0) AS completed_quantity
    FROM products p
    LEFT JOIN orders o ON o.product_id = p.id
    GROUP BY p.id, p.name, p.quantity, p.shelf_location
    ORDER BY p.name ASC
');
$productReport = $productReportStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Darbinieks</title>
    <link rel="stylesheet" href="../Css/skats.css">
    <link rel="stylesheet" href="../Css/darbinieks.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
    <header class="headers">
        <div class="log_reg">
            <span class="user" title="<?php echo htmlspecialchars($lietotajaVards, ENT_QUOTES, 'UTF-8'); ?>">
                <i class="fa fa-user" aria-hidden="true"></i><?php echo htmlspecialchars($lietotajaVards, ENT_QUOTES, 'UTF-8'); ?>
            </span>
            <a href="../Includes/log_reg_inc/logout_inc.php"><i class="fa fa-sign-out"></i>Iziet</a>
        </div>
        <div class="name">
            <h1>Darbinieks</h1>
        </div>
    </header>

    <main class="admin-lapa">
        <aside class="admin-nav-bar" aria-label="Darbinieka navigācija">
            <h2>Darbinieka panelis</h2>
            <nav>
                <ul>
                    <li><a class="active" href="#pasutijumi"><i class="fa fa-clipboard" aria-hidden="true"></i>Pasūtījumi</a></li>
                    <li><a href="#noliktava"><i class="fa fa-cubes" aria-hidden="true"></i>Noliktava</a></li>
                    <li><a href="#atskaites"><i class="fa fa-line-chart" aria-hidden="true"></i>Atskaites</a></li>
                </ul>
            </nav>
        </aside>

        <section class="admin-saturs" aria-live="polite">
            <?php if ($zina !== ''): ?>
                <div class="<?php echo ($zinaTips === 'ok' || $zinaTips === 'success' || $zinaTips === 'delete') ? 'admin-ok-zina' : 'admin-kluda'; ?>">
                    <?php echo htmlspecialchars($zina, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <article id="pasutijumi" class="admin-panel active" data-panel>
                <h2>Pasūtījumi</h2>
                <p>Darbinieks var izveidot pasūtījumu, mainīt statusu un dzēst cilvēku pasūtījumus.</p>

                <div class="forma-kaste">
                    <h3>Izveidot jaunu pasūtījumu</h3>
                    <form method="post" class="pasutijums-form">
                        <input type="hidden" name="action" value="create_order">
                        <div class="forma-rinda">
                            <div>
                                <label>Prece</label>
                                <select name="product_id" required>
                                    <option value="">Izvēlies preci</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo (int)$product['id']; ?>" <?php echo (int)$product['quantity'] <= 0 ? 'disabled' : ''; ?>>
                                            <?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>
                                            — atlikums: <?php echo (int)$product['quantity']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label>Daudzums</label>
                                <input type="number" name="quantity" min="1" required>
                            </div>
                        </div>
                        <button class="poga" type="submit">Izveidot pasūtījumu</button>
                    </form>
                </div>

                <div class="tabula-kaste">
                    <h3>Pasūtījumu saraksts</h3>
                    <table class="tabula">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Prece</th>
                                <th>Daudzums</th>
                                <th>Darbinieks</th>
                                <th>Statuss</th>
                                <th>Izveidots</th>
                                <th>Darbība</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr><td colspan="7">Pasūtījumu vēl nav.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo (int)$order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['product_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo (int)$order['quantity']; ?></td>
                                    <td><?php echo htmlspecialchars($order['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($order['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <div class="edit-rinda">
                                            <form method="post">
                                                <input type="hidden" name="action" value="update_order_status">
                                                <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                                <select class="status-select" name="status">
                                                    <?php foreach (['jauns', 'pieņemts', 'izpildīts', 'atcelts'] as $status): ?>
                                                        <option value="<?php echo $status; ?>" <?php echo $order['status'] === $status ? 'selected' : ''; ?>>
                                                            <?php echo $status; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="poga" type="submit">Mainīt</button>
                                            </form>

                                            <form method="post" onsubmit="return confirm('Vai tiešām dzēst šo pasūtījumu? Noliktavas atlikums tiks atjaunots, ja pasūtījums nav atcelts.');">
                                                <input type="hidden" name="action" value="delete_order">
                                                <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                                <button class="poga poga-dzest" type="submit">Dzēst</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </article>

            <article id="noliktava" class="admin-panel" data-panel>
                <h2>Noliktava</h2>
                <p>Darbinieks var pievienot jaunas preces, rediģēt esošās un skatīt visu preču sarakstu.</p>

                <div class="forma-kaste">
                    <h3>Pievienot preci</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="add_product">
                        <div class="forma-rinda">
                            <div>
                                <label>Nosaukums</label>
                                <input type="text" name="name" required>
                            </div>
                            <div>
                                <label>Daudzums</label>
                                <input type="number" name="quantity" min="0" max="10000000" value="0" placeholder="Max 10000000" required>
                            </div>
                            <div>
                                <label>Plaukta vieta</label>
                                <input type="text" name="shelf_location" placeholder="Piem., A-12" pattern="[A-Fa-f]-?([1-9]|[12][0-9]|30)" title="Atļauts tikai A-F un 1-30, piemēram, A-1 vai F-30">
                            </div>
                        </div>
                        <label>Apraksts</label>
                        <textarea name="description" placeholder="Preces apraksts"></textarea>
                        <br><br>
                        <button class="poga" type="submit">Pievienot preci</button>
                    </form>
                </div>

                <div class="tabula-kaste">
                    <h3>Preču saraksts</h3>
                    <table class="tabula">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Rediģēšana</th>
                                <th>Izveidots</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr><td colspan="3">Preču vēl nav.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo (int)$product['id']; ?></td>
                                    <td>
                                        <div class="edit-rinda">
                                            <form class="edit-form" method="post">
                                                <input type="hidden" name="action" value="edit_product">
                                                <input type="hidden" name="id" value="<?php echo (int)$product['id']; ?>">
                                                <input type="text" name="name" value="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                                <input type="text" name="description" value="<?php echo htmlspecialchars((string)$product['description'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Apraksts">
                                                <input class="small-input" type="number" name="quantity" min="0" value="<?php echo (int)$product['quantity']; ?>" required>
                                                <input type="text" name="shelf_location" value="<?php echo htmlspecialchars((string)$product['shelf_location'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Plaukts" pattern="[A-Fa-f]-?([1-9]|[12][0-9]|30)" title="Atļauts tikai A-F un 1-30, piemēram, A-1 vai F-30">
                                                <button class="poga" type="submit">Saglabāt</button>
                                            </form>

                                            <form method="post" onsubmit="return confirm('Vai tiešām dzēst šo preci?');">
                                                <input type="hidden" name="action" value="delete_product">
                                                <input type="hidden" name="id" value="<?php echo (int)$product['id']; ?>">
                                                <button class="poga poga-dzest" type="submit">Dzēst</button>
                                            </form>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </article>

            <article id="atskaites" class="admin-panel" data-panel>
                <h2>Atskaites</h2>

                <div class="tabula-kaste atskaite-kopsavilkums">
                    <h3>Kopsavilkums</h3>
                    <p>Kopā preču veidi: <strong><?php echo (int)$report['product_count']; ?></strong></p>
                    <p>Kopējais preču atlikums noliktavā: <strong><?php echo (int)$report['total_stock_quantity']; ?></strong></p>
                    <p>Kopā pasūtījumi: <strong><?php echo (int)$report['order_count']; ?></strong></p>
                    <p>Kopējais pasūtītais daudzums: <strong><?php echo (int)$report['ordered_quantity_total']; ?></strong></p>
                    <p>Aktīvie pasūtījumi: <strong><?php echo (int)$report['active_order_count']; ?></strong></p>
                    <p>Izpildītie pasūtījumi: <strong><?php echo (int)$report['completed_order_count']; ?></strong></p>
                    <p>Atceltie pasūtījumi: <strong><?php echo (int)$report['cancelled_order_count']; ?></strong></p>
                    <p>Preces ar mazu atlikumu (0 līdz 5): <strong><?php echo (int)$report['low_stock_count']; ?></strong></p>
                    <p>Aizņemtie plaukti: <strong><?php echo (int)$report['used_shelves_count']; ?></strong></p>
                </div>

                <div class="tabula-kaste">
                    <h3>Atskaite pa precēm</h3>
                    <table class="tabula">
                        <thead>
                            <tr>
                                <th>Prece</th>
                                <th>Plaukts</th>
                                <th>Atlikums noliktavā</th>
                                <th>Pasūtīts</th>
                                <th>Izpildīts</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($productReport)): ?>
                                <tr><td colspan="5">Atskaites datu vēl nav.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($productReport as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)$row['shelf_location'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo (int)$row['stock_quantity']; ?></td>
                                    <td><?php echo (int)$row['ordered_quantity']; ?></td>
                                    <td><?php echo (int)$row['completed_quantity']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </main>

    <script>
        (function () {
            const links = Array.from(document.querySelectorAll('.admin-nav-bar a'));
            const panels = Array.from(document.querySelectorAll('[data-panel]'));

            function normalizeShelfLocation(value) {
                const cleaned = value.toUpperCase().replace(/\s+/g, '').replace(/[^A-F0-9-]/g, '');
                const match = cleaned.match(/^([A-F])-?(\d{0,2})/);

                if (!match) {
                    return cleaned.slice(0, 1);
                }

                const letter = match[1];
                let digits = match[2] || '';

                if (digits !== '') {
                    let number = parseInt(digits, 10);

                    if (Number.isNaN(number)) {
                        return letter;
                    }

                    if (number > 30) {
                        number = 30;
                    }

                    digits = String(number);
                    return letter + '-' + digits;
                }

                return letter;
            }

            function validateShelfInput(input) {
                const value = input.value.trim();

                if (value === '') {
                    input.setCustomValidity('');
                    return;
                }

                if (!/^([A-F])-?([1-9]|[12][0-9]|30)$/.test(value)) {
                    input.setCustomValidity('Atļauts tikai A-F un 1-30, piemēram, A-1 vai F-30');
                    return;
                }

                input.setCustomValidity('');
            }

            function showPanelFromHash() {
                const hash = window.location.hash || '#pasutijumi';
                const targetId = hash.replace('#', '');
                const targetPanel = panels.find((panel) => panel.id === targetId) || panels[0];

                panels.forEach((panel) => {
                    panel.classList.toggle('active', panel === targetPanel);
                });

                links.forEach((link) => {
                    link.classList.toggle('active', link.getAttribute('href') === '#' + targetPanel.id);
                });
            }

            document.querySelectorAll('input[name="shelf_location"]').forEach((input) => {
                input.addEventListener('input', () => {
                    input.value = normalizeShelfLocation(input.value);
                    validateShelfInput(input);
                });

                input.addEventListener('blur', () => {
                    input.value = normalizeShelfLocation(input.value);
                    validateShelfInput(input);
                });

                validateShelfInput(input);
            });

            window.addEventListener('hashchange', showPanelFromHash);
            showPanelFromHash();
        })();
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const zinas = document.querySelectorAll('.admin-ok-zina, .admin-kluda, .message-error');

            zinas.forEach(function (zina) {
                setTimeout(function () {
                    zina.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
                    zina.style.opacity = '0';
                    zina.style.transform = 'translateY(-6px)';

                    setTimeout(function () {
                        zina.remove();
                    }, 350);
                }, 3000);
            });
        });
    </script>

</body>
</html>
