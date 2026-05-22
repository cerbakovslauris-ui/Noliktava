<?php
require_once __DIR__ . '/../Includes/auth.inc.php';
$auth = parbauditAutorizaciju('admin');

$lietotajaVards = (string) $auth['vards'];
$lietotajaVardsRedzams = $lietotajaVards;
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
                    <li><a class="active" href="admin.php"><i class="fa fa-dashboard" aria-hidden="true"></i>Panelis</a></li>
                    <li><a href="#lietotaji"><i class="fa fa-users" aria-hidden="true"></i>Lietotāju pārvaldība</a></li>
                    <li><a href="#lomas"><i class="fa fa-id-badge" aria-hidden="true"></i>Lomu un piekļuves tiesības</a></li>
                    <li><a href="#preces"><i class="fa fa-cubes" aria-hidden="true"></i>Preču pārvaldība</a></li>
                    <li><a href="#plaukti"><i class="fa fa-th-large" aria-hidden="true"></i>Plauktu kartēšana</a></li>
                    <li><a href="#pasutijumi"><i class="fa fa-clipboard" aria-hidden="true"></i>Pasūtījumu kontrole</a></li>
                    <li><a href="#atskaites"><i class="fa fa-line-chart" aria-hidden="true"></i>Atskaites un statistika</a></li>
                    <li><a href="#zurnals"><i class="fa fa-history" aria-hidden="true"></i>Darbību žurnāls</a></li>
                </ul>
            </nav>
        </aside>

        
    </main>
</body>
</html>