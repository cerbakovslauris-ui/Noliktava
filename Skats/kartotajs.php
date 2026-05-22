<?php
require_once __DIR__ . '/../Includes/auth.inc.php';
$auth = parbauditAutorizaciju('kartotajs');

$lietotajaVards = (string) $auth['vards'];
$lietotajaVardsRedzams = $lietotajaVards;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plauktu kartotājs</title>
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
            <h1>Plauktu kartotājs</h1>
        </div>
    </header>

    <main class="admin-lapa">
        <aside class="admin-nav-bar" aria-label="Kartotāja navigācija">
            <h2>Kartotāja panelis</h2>
            <nav>
                <ul>
                    <li><a class="active" href="#plaukti"><i class="fa fa-th-large" aria-hidden="true"></i>Plaukti</a></li>
                    <li><a href="#kartesana"><i class="fa fa-exchange" aria-hidden="true"></i>Kartēšana</a></li>
                    <li><a href="#atskaites"><i class="fa fa-line-chart" aria-hidden="true"></i>Atskaites</a></li>
                </ul>
            </nav>
        </aside>

        <section class="admin-saturs" aria-live="polite">
            <article id="plaukti" class="admin-panel active" data-panel>
                <h2>Plaukti</h2>
                <p>Noliktavas plauktu apskats un vadība.</p>
            </article>

            <article id="kartesana" class="admin-panel" data-panel>
                <h2>Kartēšana</h2>
                <p>Preču kartēšana uz noliktavas plauktiņiem.</p>
            </article>

            <article id="atskaites" class="admin-panel" data-panel>
                <h2>Atskaites</h2>
                <p>Kartēšanas atskaites un statistika.</p>
            </article>
        </section>
    </main>

    <script>
        (function () {
            const links = Array.from(document.querySelectorAll('.admin-nav-bar a'));
            const panels = Array.from(document.querySelectorAll('[data-panel]'));

            function showPanelFromHash() {
                const hash = window.location.hash || '#plaukti';
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