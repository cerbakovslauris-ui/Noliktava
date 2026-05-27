<?php
require_once __DIR__ . '/../Includes/log_reg_inc/auth.inc.php';
require_once __DIR__ . '/../Includes/dbh.inc.php';
$auth = parbauditAutorizaciju('admin');

$lietotajaVards = (string) $auth['vards'];
$lietotajaVardsRedzams = $lietotajaVards;

$visiLietotaji = [];
$visasLomas = [];
$lietotajuIeladesKluda = null;
$products = [];
$produktuIeladesKluda = null;
$atskaitesKluda = null;
$atskaite = [
    'kop_lietotaji' => 0,
    'kop_preces' => 0,
    'kop_atlikums' => 0,
    'bez_atlikuma' => 0,
    'kop_pasutijumi' => 0,
    'jauni_pasutijumi' => 0,
    'atcelti_pasutijumi' => 0,
];

$flashZina = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

try {
    $lomuVaicajums = $pdo->query('SELECT id, name FROM roles ORDER BY id ASC');
    $visasLomas = $lomuVaicajums->fetchAll();

    $vaicajums = $pdo->query(
        'SELECT users.id, users.username, users.role_id, users.created_at, roles.name AS role_name
         FROM users
         LEFT JOIN roles ON roles.id = users.role_id
         ORDER BY users.id ASC'
    );
    $visiLietotaji = $vaicajums->fetchAll();
} catch (PDOException $e) {
    $lietotajuIeladesKluda = 'Neizdevās ielādēt lietotāju sarakstu.';
}

try {
    $produktuVaicajums = $pdo->query('SELECT * FROM products ORDER BY id DESC');
    $products = $produktuVaicajums->fetchAll();

    $atskaite['kop_preces'] = count($products);
    foreach ($products as $product) {
        $daudzums = (int) ($product['quantity'] ?? 0);
        $atskaite['kop_atlikums'] += $daudzums;
        if ($daudzums <= 0) {
            $atskaite['bez_atlikuma']++;
        }
    }
} catch (PDOException $e) {
    $produktuIeladesKluda = 'Neizdevās ielādēt preču sarakstu.';
}

try {
    $atskaite['kop_lietotaji'] = count($visiLietotaji);

    $pasutijumuAtskaiteStmt = $pdo->query(
        "SELECT
            COUNT(*) AS kop_pasutijumi,
            SUM(CASE WHEN status = 'jauns' THEN 1 ELSE 0 END) AS jauni_pasutijumi,
            SUM(CASE WHEN status = 'atcelts' THEN 1 ELSE 0 END) AS atcelti_pasutijumi
         FROM orders"
    );
    $pasutijumuAtskaite = $pasutijumuAtskaiteStmt->fetch();

    $atskaite['kop_pasutijumi'] = (int) ($pasutijumuAtskaite['kop_pasutijumi'] ?? 0);
    $atskaite['jauni_pasutijumi'] = (int) ($pasutijumuAtskaite['jauni_pasutijumi'] ?? 0);
    $atskaite['atcelti_pasutijumi'] = (int) ($pasutijumuAtskaite['atcelti_pasutijumi'] ?? 0);
} catch (PDOException $e) {
    $atskaitesKluda = 'Neizdevās ielādēt atskaišu datus.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
    <link rel="stylesheet" href="../Css/skats.css">
    <link rel="stylesheet" href="../Css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
    <header class="headers">
        <div class="log_reg">
            <span class="user" title="<?php echo htmlspecialchars($lietotajaVards, ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-user" aria-hidden="true"></i><?php echo htmlspecialchars($lietotajaVardsRedzams, ENT_QUOTES, 'UTF-8'); ?></span>
            <a href="../Includes/log_reg_inc/logout_inc.php"><i class="fa fa-sign-out"></i>Iziet</a>
        </div>
        <div class="name">
            <h1>Admin</h1>
        </div>
    </header>

    <main class="admin-lapa">
        <aside class="admin-nav-bar" aria-label="Admina navigācija">
            <h2>Admina panelis</h2>
            <nav>
                <ul>
                    <li><a class="active" href="#lietotaji"><i class="fa fa-user" aria-hidden="true"></i>Lietotāji</a></li>
                    <li><a href="#pievienot-preces"><i class="fa fa-plus" aria-hidden="true"></i>Pievienot preces</a></li>
                    <li><a href="#rediget-preces"><i class="fa fa-edit" aria-hidden="true"></i>Rediģēt preces</a></li>
                    <li><a href="#atskaites"><i class="fa fa-line-chart" aria-hidden="true"></i>Atskaites</a></li>
                </ul>
            </nav>
        </aside>

        <section class="admin-saturs" aria-live="polite">
            <?php if (is_array($flashZina) && isset($flashZina['teksts'])): ?>
                <p class="<?php echo ($flashZina['tips'] ?? '') === 'ok' ? 'admin-ok-zina' : 'admin-kluda'; ?>">
                    <?php echo htmlspecialchars((string) $flashZina['teksts'], ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>

            <article id="lietotaji" class="admin-panel active" data-panel>
                <h2>Lietotāji</h2>
                <div class="admin-tabula-wrap">
                    <h3>Izveidot lietotāju</h3>
                    <form method="post" action="../Includes/admin_inc/izveidot_user.php" class="admin-form-inline admin-create-user-form">
                        <input type="text" name="username" placeholder="Lietotājvārds" required>
                        <input type="password" name="password" placeholder="Parole" required>
                        <select name="role_id" aria-label="Izvēlēties lomu" required>
                            <option value="">Izvēlies lomu</option>
                            <?php foreach ($visasLomas as $loma): ?>
                                <option value="<?php echo (int) $loma['id']; ?>"><?php echo htmlspecialchars((string) $loma['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="admin-poga admin-poga-mainit">Izveidot</button>
                    </form>
                </div>

                <?php if ($lietotajuIeladesKluda !== null): ?>
                    <p class="admin-kluda"><?php echo htmlspecialchars($lietotajuIeladesKluda, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php elseif (empty($visiLietotaji)): ?>
                    <p>Šobrīd sistēmā nav neviena lietotāja.</p>
                <?php else: ?>
                    <div class="admin-tabula-wrap">
                        <table class="admin-tabula admin-lietotaji-tabula">
                            <thead>
                                <tr>
                                    <th>Lietotājvārds</th>
                                    <th>Loma</th>
                                    <th>Konta rediģēšana</th>
                                    <th>Darbības</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($visiLietotaji as $lietotajs): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars((string) $lietotajs['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string) ($lietotajs['role_name'] ?? 'Nav norādīta'), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <form method="post" action="../Includes/admin_inc/rediget_user.php" class="admin-form-inline admin-user-edit-form">
                                                <input type="hidden" name="lietotaja_id" value="<?php echo (int) $lietotajs['id']; ?>">
                                                <input type="text" name="username" value="<?php echo htmlspecialchars((string) $lietotajs['username'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                                <input type="password" name="jauna_parole" placeholder="Jauna parole (nav obligāta)">
                                                <button type="submit" class="admin-poga admin-poga-mainit">Saglabāt kontu</button>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="admin-darbibas admin-user-actions">
                                                <form method="post" action="../Includes/admin_inc/mainit_lomu.php" class="admin-form-inline admin-role-form">
                                                    <input type="hidden" name="lietotaja_id" value="<?php echo (int) $lietotajs['id']; ?>">
                                                    <select name="jauna_loma_id" aria-label="Izvēlēties jaunu lomu">
                                                        <?php foreach ($visasLomas as $loma): ?>
                                                            <option value="<?php echo (int) $loma['id']; ?>" <?php echo (int) $lietotajs['role_id'] === (int) $loma['id'] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars((string) $loma['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="admin-poga admin-poga-mainit">Mainīt lomu</button>
                                                </form>

                                                <form method="post" action="../Includes/admin_inc/dzest_user.php" class="admin-form-inline" onsubmit="return confirm('Vai tiešām dzēst šo lietotāju?');">
                                                    <input type="hidden" name="lietotaja_id" value="<?php echo (int) $lietotajs['id']; ?>">
                                                    <button type="submit" class="admin-poga admin-poga-dzest">Dzēst</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </article>

            <article id="pievienot-preces" class="admin-panel" data-panel>
                <h2>Pievienot preces</h2>
                <p>Šeit vari pievienot jaunu preci noliktavai.</p>
                <div class="admin-tabula-wrap">
                    <form method="post" action="../Includes/admin_inc/add_produktu.php">
                        <table class="admin-tabula admin-add-product-table">
                            <tbody>
                                <tr>
                                    <td><label for="add-name">Nosaukums</label></td>
                                    <td><input id="add-name" type="text" name="name" required></td>
                                </tr>
                                <tr>
                                    <td><label for="add-quantity">Daudzums</label></td>
                                    <td><input id="add-quantity" type="number" name="quantity" min="0" value="0" required></td>
                                </tr>
                                <tr>
                                    <td><label for="add-shelf">Plaukta vieta</label></td>
                                    <td><input id="add-shelf" type="text" name="shelf_location" placeholder="Piem., A-12" pattern="[A-Fa-f]-?([1-9]|[12][0-9]|30)" title="Atļauts tikai A-F un 1-30, piemēram, A-1 vai F-30"></td>
                                </tr>
                                <tr>
                                    <td><label for="add-description">Apraksts</label></td>
                                    <td><textarea id="add-description" name="description" placeholder="Preces apraksts"></textarea></td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <button type="submit" class="admin-poga admin-poga-mainit">Pievienot preci</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </form>
                </div>
            </article>

            <article id="rediget-preces" class="admin-panel" data-panel>
                <h2>Rediģēt preces</h2>
                <p>Šeit vari labot un dzēst jau esošas preces.</p>
                <?php if ($produktuIeladesKluda !== null): ?>
                    <p class="admin-kluda"><?php echo htmlspecialchars($produktuIeladesKluda, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php else: ?>
                    <div class="admin-tabula-wrap">
                        <table class="admin-tabula admin-products-table">
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
                                        <td><?php echo (int) $product['id']; ?></td>
                                        <td>
                                            <div class="admin-darbibas admin-product-actions">
                                                <form method="post" action="../Includes/admin_inc/edit_produktu.php" class="admin-form-inline admin-product-edit-form">
                                                    <input type="hidden" name="id" value="<?php echo (int) $product['id']; ?>">
                                                    <input type="text" name="name" value="<?php echo htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                                    <input type="text" name="description" value="<?php echo htmlspecialchars((string) ($product['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Apraksts">
                                                    <input type="number" name="quantity" min="0" value="<?php echo (int) $product['quantity']; ?>" required>
                                                    <input type="text" name="shelf_location" value="<?php echo htmlspecialchars((string) ($product['shelf_location'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Plaukts" pattern="[A-Fa-f]-?([1-9]|[12][0-9]|30)" title="Atļauts tikai A-F un 1-30, piemēram, A-1 vai F-30">
                                                    <button type="submit" class="admin-poga admin-poga-mainit">Saglabāt</button>
                                                </form>
                                                <form method="post" action="../Includes/admin_inc/dzest_produktu.php" class="admin-form-inline" onsubmit="return confirm('Vai tiešām dzēst šo preci?');">
                                                    <input type="hidden" name="id" value="<?php echo (int) $product['id']; ?>">
                                                    <button type="submit" class="admin-poga admin-poga-dzest">Dzēst</button>
                                                </form>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars((string) ($product['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </article>

            <article id="atskaites" class="admin-panel" data-panel>
                <h2>Atskaites</h2>
                <p>Pārskats par lietotājiem, precēm un pasūtījumiem.</p>

                <?php if ($atskaitesKluda !== null): ?>
                    <p class="admin-kluda"><?php echo htmlspecialchars($atskaitesKluda, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php else: ?>
                    <div class="admin-tabula-wrap">
                        <table class="admin-tabula">
                            <tbody>
                                <tr>
                                    <th>Kopā lietotāji</th>
                                    <td><?php echo (int) $atskaite['kop_lietotaji']; ?></td>
                                </tr>
                                <tr>
                                    <th>Kopā preces</th>
                                    <td><?php echo (int) $atskaite['kop_preces']; ?></td>
                                </tr>
                                <tr>
                                    <th>Kopējais preču atlikums</th>
                                    <td><?php echo (int) $atskaite['kop_atlikums']; ?></td>
                                </tr>
                                <tr>
                                    <th>Preces bez atlikuma</th>
                                    <td><?php echo (int) $atskaite['bez_atlikuma']; ?></td>
                                </tr>
                                <tr>
                                    <th>Kopā pasūtījumi</th>
                                    <td><?php echo (int) $atskaite['kop_pasutijumi']; ?></td>
                                </tr>
                                <tr>
                                    <th>Jauni pasūtījumi</th>
                                    <td><?php echo (int) $atskaite['jauni_pasutijumi']; ?></td>
                                </tr>
                                <tr>
                                    <th>Atcelti pasūtījumi</th>
                                    <td><?php echo (int) $atskaite['atcelti_pasutijumi']; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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
                const hash = window.location.hash || '#lietotaji';
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
</body>
</html>