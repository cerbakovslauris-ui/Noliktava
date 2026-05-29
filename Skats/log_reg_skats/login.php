<?php
$loginMessage = '';
$loginMessageType = 'kluda';

if (isset($_GET['session_expired']) && $_GET['session_expired'] == '1') {
    $loginMessage = 'Sesija ir beigusies. Ienāc vēlreiz.';
}

if (isset($_GET['msg'])) {
    $loginMessage = trim($_GET['msg']);
    $loginMessageType = ($_GET['type'] ?? 'kluda') === 'ok' ? 'ok' : 'kluda';
}

if (isset($_GET['error'])) {
    $loginMessageType = 'kluda';

    if ($_GET['error'] === 'empty') {
        $loginMessage = 'Aizpildi lietotājvārdu un paroli.';
    } elseif ($_GET['error'] === 'wrong') {
        $loginMessage = 'Nepareizs lietotājvārds vai parole.';
    } else {
        $loginMessage = 'Pieteikšanās neizdevās.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>login</title>
    <link rel="stylesheet" href="../../Css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
    <a class="back" href="../user.php"><i class="fa fa-arrow-left" aria-hidden="true"></i> Atpakaļ</a>
    <main>
        <div class="login">
            <div class="log_teksts">
                <h1>Ienākt</h1>
                <?php if ($loginMessage !== ''): ?>
                    <div class="login-zina <?= $loginMessageType === 'ok' ? 'login-ok-zina' : 'login-kluda'; ?>" id="login-zina">
                        <?= htmlspecialchars($loginMessage, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
                <form action="../../Includes/log_reg_inc/log_inc.php" method="POST">
                    <h3>Lietotajvārds</h3>
                    <input type="text" name="lietotajvards" placeholder='Lietotajvards' pattern="\S+" title="Lietotājvārdā nedrīkst būt atstarpes">
                    <h3>Parole</h3>
                    <input type="password" name="parole" placeholder='Parole' pattern="\S+" title="Parolē nedrīkst būt atstarpes">   
                    <button type="submit"><i class="fa fa-sign-in"></i>Pieteikties</button>
                </form>
                <p>Nav konta? <a href="register.php">Reģistrēties</a></p>
            </div>
        </div>
    </main>
    <script>
        (() => {
            const zina = document.getElementById('login-zina');

            if (zina) {
                setTimeout(() => {
                    zina.classList.add('paslept');
                    setTimeout(() => zina.remove(), 350);
                }, 3000);

                if (window.history.replaceState) {
                    window.history.replaceState(null, '', window.location.pathname);
                }
            }
        })();

        (() => {
            const ievades = Array.from(document.querySelectorAll('input[name="lietotajvards"], input[name="parole"]'));

            function notiritAtstarpes(input) {
                input.value = input.value.replace(/\s+/g, '');
            }

            ievades.forEach((input) => {
                input.addEventListener('input', () => notiritAtstarpes(input));
                input.addEventListener('blur', () => notiritAtstarpes(input));
            });
        })();
    </script>
</body>
</html>