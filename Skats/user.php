<?php
require_once __DIR__ . '/../Includes/log_reg_inc/auth.inc.php';
require_once __DIR__ . '/../Includes/dbh.inc.php';
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
                <a href="../Includes/log_reg_inc/logout_inc.php"><i class="fa fa-sign-out"></i>Iziet</a>
            <?php else: ?>
                <a href="log_reg_skats/login.php">Pieteikties</a>
                <a href="log_reg_skats/register.php">Reģistrēties</a>
            <?php endif; ?>
        </div>
        <div class="name">
            <h1>Lietotājs</h1>
        </div>
    </header>

    <main>
        <div class="saturs-centrs">
            <section class="saturs">
                <?php if ($irIelogojies): ?>
                    <h2>Paldies par reģistrāciju</h2>
                    <p>Admins drīzumā piešķirs jums lomu.</p>
                <?php else: ?>
                    <h2>Lūdzu reģistrējies</h2>
                <?php endif; ?>
            </section>
        </div>
    </main>

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