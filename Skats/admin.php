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
</body>
</html>