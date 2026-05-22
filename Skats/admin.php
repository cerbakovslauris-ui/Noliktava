<?php
require_once __DIR__ . '/../Includes/auth.inc.php';
require_once __DIR__ . '/../Includes/dbh.inc.php';
$auth = parbauditAutorizaciju('admin');

$lietotajaVards = (string) $auth['vards'];
$lietotajaVardsRedzams = $lietotajaVards;

$visiLietotaji = [];
$visasLomas = [];
$lietotajuIeladesKluda = null;

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
            <a href="../Includes/log_reg_inc/logout_inc.php"><i class="fa fa-sign-out"></i>Izlogoties</a>
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
                </ul>
            </nav>
        </aside>

        <section class="admin-saturs" aria-live="polite">
            <article id="lietotaji" class="admin-panel active" data-panel>
                <h2>Lietotāji</h2>
                <?php if ($lietotajuIeladesKluda !== null): ?>
                    <p class="admin-kluda"><?php echo htmlspecialchars($lietotajuIeladesKluda, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php elseif (empty($visiLietotaji)): ?>
                    <p>Šobrīd sistēmā nav neviena lietotāja.</p>
                <?php else: ?>
                    <?php if (is_array($flashZina) && isset($flashZina['teksts'])): ?>
                        <p class="<?php echo ($flashZina['tips'] ?? '') === 'ok' ? 'admin-ok-zina' : 'admin-kluda'; ?>">
                            <?php echo htmlspecialchars((string) $flashZina['teksts'], ENT_QUOTES, 'UTF-8'); ?>
                        </p>
                    <?php endif; ?>
                    <div class="admin-tabula-wrap">
                        <table class="admin-tabula">
                            <thead>
                                <tr>
                                    <th>Lietotājvārds</th>
                                    <th>Loma</th>
                                    <th>Darbības</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($visiLietotaji as $lietotajs): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars((string) $lietotajs['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string) ($lietotajs['role_name'] ?? 'Nav norādīta'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                            <div class="admin-darbibas">
                                                <form method="post" action="../Includes/mainit_lomu.php" class="admin-form-inline">
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

                                                <form method="post" action="../Includes/dzest_user.php" class="admin-form-inline" onsubmit="return confirm('Vai tiešām dzēst šo lietotāju?');">
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
            </article>

            <article id="rediget-preces" class="admin-panel" data-panel>
                <h2>Rediģēt preces</h2>
                <p>Šeit vari meklēt un labot jau esošu preču informāciju.</p>
            </article>
        </section>
    </main>

    <script>
        (function () {
            const links = Array.from(document.querySelectorAll('.admin-nav-bar a'));
            const panels = Array.from(document.querySelectorAll('[data-panel]'));

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

            window.addEventListener('hashchange', showPanelFromHash);
            showPanelFromHash();
        })();
    </script>
</body>
</html>