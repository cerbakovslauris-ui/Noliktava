<?php
require_once __DIR__ . '/../Includes/auth.inc.php';
$auth = parbauditAutorizaciju('darbinieks');

$lietotajaVards = (string) $auth['vards'];
$lietotajaVardsRedzams = $lietotajaVards;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Darbinieks</title>
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
            <article id="pasutijumi" class="admin-panel active" data-panel>
                <h2>Pasūtījumi</h2>
                <p>Šeit vari skatīt un pārvaldīt aktuālos pasūtījumus.</p>
            </article>

            <article id="noliktava" class="admin-panel" data-panel>
                <h2>Noliktava</h2>
                <p>Noliktavas preču saraksts un vadība.</p>
            </article>

            <article id="atskaites" class="admin-panel" data-panel>
                <h2>Atskaites</h2>
                <p>Skatīt darbības atskaites un statistiku.</p>
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