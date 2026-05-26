<?php
require_once __DIR__ . '/../Includes/log_reg_inc/auth.inc.php';
$auth = parbauditAutorizaciju('darbinieks');

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
$zinaTips = 'success';

function cleanText($value) {
    return trim((string)$value);
}

function redirectWithHash($hash) {
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '#') . '#' . $hash);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_product') {
            $name = cleanText($_POST['name'] ?? '');
            $description = cleanText($_POST['description'] ?? '');
            $quantity = (int)($_POST['quantity'] ?? 0);
            $shelfLocation = cleanText($_POST['shelf_location'] ?? '');

            if ($name === '') {
                throw new Exception('Preces nosaukums nedrīkst būt tukšs.');
            }
            if ($quantity < 0) {
                throw new Exception('Daudzums nevar būt negatīvs.');
            }

            $stmt = $pdo->prepare('INSERT INTO products (name, description, quantity, shelf_location) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $description, $quantity, $shelfLocation]);
            redirectWithHash('noliktava');
        }

        if ($action === 'edit_product') {
            $id = (int)($_POST['id'] ?? 0);
            $name = cleanText($_POST['name'] ?? '');
            $description = cleanText($_POST['description'] ?? '');
            $quantity = (int)($_POST['quantity'] ?? 0);
            $shelfLocation = cleanText($_POST['shelf_location'] ?? '');

            if ($id <= 0) {
                throw new Exception('Nav atrasta prece, kuru rediģēt.');
            }
            if ($name === '') {
                throw new Exception('Preces nosaukums nedrīkst būt tukšs.');
            }
            if ($quantity < 0) {
                throw new Exception('Daudzums nevar būt negatīvs.');
            }

            $stmt = $pdo->prepare('UPDATE products SET name = ?, description = ?, quantity = ?, shelf_location = ? WHERE id = ?');
            $stmt->execute([$name, $description, $quantity, $shelfLocation, $id]);
            redirectWithHash('noliktava');
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
                throw new Exception('Nevar dzēst preci, jo tai ir saistīti pasūtījumi. Vispirms sakārto pasūtījumus.');
            }

            $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
            $stmt->execute([$id]);
            redirectWithHash('noliktava');
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

            $stmt = $pdo->prepare('INSERT INTO orders (product_id, user_id, quantity, status) VALUES (?, ?, ?, ?)');
            $stmt->execute([$productId, $lietotajaId, $quantity, 'jauns']);
            redirectWithHash('pasutijumi');
        }

        if ($action === 'update_order_status') {
            $orderId = (int)($_POST['order_id'] ?? 0);
            $status = cleanText($_POST['status'] ?? '');
            $allowedStatuses = ['jauns', 'pieņemts', 'izpildīts', 'atcelts'];

            if ($orderId <= 0) {
                throw new Exception('Nav atrasts pasūtījums.');
            }
            if (!in_array($status, $allowedStatuses, true)) {
                throw new Exception('Nederīgs pasūtījuma statuss.');
            }

            $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
            $stmt->execute([$status, $orderId]);
            redirectWithHash('pasutijumi');
        }
    } catch (Exception $e) {
        $zina = $e->getMessage();
        $zinaTips = 'error';
    }
}

$products = $pdo->query('SELECT * FROM products ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);

$orderStmt = $pdo->query('
    SELECT o.id, o.quantity, o.status, o.created_at, p.name AS product_name, u.username
    FROM orders o
    JOIN products p ON p.id = o.product_id
    JOIN users u ON u.id = o.user_id
    ORDER BY o.id DESC
');
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Darbinieks</title>
    <link rel="stylesheet" href="../Css/skats.css">
    <link rel="stylesheet" href="../Css/darbinieks.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
    <header class="headers">
        <div class="log_reg">
            <span class="user" title="<?php echo htmlspecialchars($lietotajaVards, ENT_QUOTES, 'UTF-8'); ?>">
                <i class="fa fa-user" aria-hidden="true"></i><?php echo htmlspecialchars($lietotajaVards, ENT_QUOTES, 'UTF-8'); ?>
            </span>
            <a href="../Includes/log_reg_inc/logout_inc.php"><i class="fa fa-sign-out"></i>Izlogoties</a>
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
                <div class="message-error"><?php echo htmlspecialchars($zina, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <article id="pasutijumi" class="admin-panel active" data-panel>
                <h2>Pasūtījumi</h2>
                <p>Darbinieks var izveidot pasūtījumu un mainīt tā statusu.</p>

                <div class="forma-kaste">
                    <h3>Izveidot jaunu pasūtījumu</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="create_order">
                        <div class="forma-rinda">
                            <div>
                                <label>Prece</label>
                                <select name="product_id" required>
                                    <option value="">Izvēlies preci</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo (int)$product['id']; ?>">
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
                                <input type="number" name="quantity" min="0" value="0" required>
                            </div>
                            <div>
                                <label>Plaukta vieta</label>
                                <input type="text" name="shelf_location" placeholder="Piem., A-12">
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
                                                <input type="text" name="shelf_location" value="<?php echo htmlspecialchars((string)$product['shelf_location'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Plaukts">
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
                <div class="tabula-kaste">
                    <p>Kopā preces: <strong><?php echo count($products); ?></strong></p>
                    <p>Kopā pasūtījumi: <strong><?php echo count($orders); ?></strong></p>
                </div>
            </article>
        </section>
    </main>

    <script>
        (function () {
            const links = Array.from(document.querySelectorAll('.admin-nav-bar a'));
            const panels = Array.from(document.querySelectorAll('[data-panel]'));

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

            window.addEventListener('hashchange', showPanelFromHash);
            showPanelFromHash();
        })();
    </script>
</body>
</html>
