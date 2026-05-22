<?php
require_once __DIR__ . '/../Includes/auth.inc.php';
$auth = iegutAktivoLietotaju();

$lietotajaVards = (string) ($auth['vards'] ?? '');
$lietotajaVardsRedzams = $lietotajaVards;
$irIelogojies = $auth !== null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lietotājs</title>
    <link rel="stylesheet" href="../Css/skats.css">
    <link rel="stylesheet" href="../Css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
    <header class="headers">
        <div class="log_reg">
            <?php if ($lietotajaVards !== ''): ?>
                <span class="user" title="<?php echo htmlspecialchars($lietotajaVards, ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-user" aria-hidden="true"></i><?php echo htmlspecialchars($lietotajaVardsRedzams, ENT_QUOTES, 'UTF-8'); ?></span>
                <a href="../Includes/log_reg_inc/logout_inc.php"><i class="fa fa-sign-out"></i>Izlogoties</a>
            <?php else: ?>
                <a href="log_reg_skats/login.php">Pieteikties</a>
                <a href="log_reg_skats/register.php">Reģistrēties</a>
            <?php endif; ?>
        </div>
        <div class="name">
            <h1>Lietotājs</h1>
        </div>
    </header>

    <?php if ($irIelogojies): ?>
    <main class="admin-lapa">
        <aside class="admin-nav-bar" aria-label="Lietotāja navigācija">
            <h2>Manu preču panelis</h2>
            <nav>
                <ul>
                    <li><a class="active" href="#preces"><i class="fa fa-cube" aria-hidden="true"></i>Preces</a></li>
                    <li><a href="#pasutijumi"><i class="fa fa-shopping-cart" aria-hidden="true"></i>Mani pasūtījumi</a></li>
                    <li><a href="#profils"><i class="fa fa-cog" aria-hidden="true"></i>Profils</a></li>
                </ul>
            </nav>
        </aside>

        <section class="admin-saturs" aria-live="polite">
            <article id="preces" class="admin-panel active" data-panel>
                <h2>Preces</h2>
                <p>Šeit jūs varat redzēt visus pieejamos produktus.</p>
            </article>

            <article id="pasutijumi" class="admin-panel" data-panel>
                <h2>Mani pasūtījumi</h2>
                <p>Jūsu pasūtījumu vēsture un statuss.</p>
            </article>

            <article id="profils" class="admin-panel" data-panel>
                <h2>Profils</h2>
                <p>Jūsu profila iestatījumi un informācija.</p>
            </article>
        </section>
    </main>
    <?php else: ?>
    <main>
        <div class="saturs-centrs">
            <section class="saturs">
                <h2>Reģistrējies lai redzētu preces</h2>
            </section>
        </div>
    </main>
    <?php endif; ?>

    <script>
        (function () {
            const links = Array.from(document.querySelectorAll('.admin-nav-bar a'));
            const panels = Array.from(document.querySelectorAll('[data-panel]'));

            if (links.length === 0 || panels.length === 0) return;

            function showPanelFromHash() {
                const hash = window.location.hash || '#preces';
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