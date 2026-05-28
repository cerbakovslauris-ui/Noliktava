<!DOCTYPE html>
<html lang="lv">
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

                <div id="echoZina" class="admin-kluda<?php echo (isset($_GET['session_expired']) && $_GET['session_expired'] == '1') ? '' : ' paslepta'; ?>">
                    <?php if (isset($_GET['session_expired']) && $_GET['session_expired'] == '1'): ?>
                        Sesija ir beigusies. Ienāc vēlreiz.
                    <?php endif; ?>
                </div>

                <form id="loginForma" action="../../Includes/log_reg_inc/log_inc.php" method="POST" novalidate>
                    <h3>Lietotājvārds</h3>
                    <input type="text" name="lietotajvards" placeholder="Lietotājvārds">
                    <h3>Parole</h3>
                    <input type="password" name="parole" placeholder="Parole">   
                    <button type="submit"><i class="fa fa-sign-in"></i>Pieteikties</button>
                </form>
                <p>Nav konta? <a href="register.php">Reģistrēties</a></p>
            </div>
        </div>
    </main>
    <script>
        (() => {
            const forma = document.getElementById('loginForma');
            const echoZina = document.getElementById('echoZina');
            const lietotajvards = document.querySelector('input[name="lietotajvards"]');
            const parole = document.querySelector('input[name="parole"]');
            const ievades = [lietotajvards, parole].filter(Boolean);
            let pazusanasTaimeris = null;
            let nonemsanasTaimeris = null;

            function notiritAtstarpes(input) {
                input.value = input.value.replace(/\s+/g, '');
            }

            function pasleptZinu() {
                echoZina.classList.add('pazud');

                nonemsanasTaimeris = setTimeout(() => {
                    echoZina.classList.add('paslepta');
                    echoZina.classList.remove('pazud');
                    echoZina.textContent = '';
                }, 500);
            }

            function paraditZinu(teksts) {
                clearTimeout(pazusanasTaimeris);
                clearTimeout(nonemsanasTaimeris);

                echoZina.textContent = teksts;
                echoZina.classList.remove('paslepta', 'pazud');

                pazusanasTaimeris = setTimeout(pasleptZinu, 3000);
            }

            ievades.forEach((input) => {
                input.addEventListener('input', () => notiritAtstarpes(input));
                input.addEventListener('blur', () => notiritAtstarpes(input));
            });

            if (echoZina && echoZina.textContent.trim() !== '') {
                pazusanasTaimeris = setTimeout(pasleptZinu, 3000);
            }

            forma.addEventListener('submit', (e) => {
                const lietotajvardsVertiba = lietotajvards.value.trim();
                const parolesVertiba = parole.value.trim();

                if (lietotajvardsVertiba === '') {
                    e.preventDefault();
                    paraditZinu('Lūdzu ievadi lietotājvārdu!');
                    lietotajvards.focus();
                    return;
                }

                if (parolesVertiba === '') {
                    e.preventDefault();
                    paraditZinu('Lūdzu ievadi paroli!');
                    parole.focus();
                    return;
                }

                lietotajvards.value = lietotajvardsVertiba.replace(/\s+/g, '');
                parole.value = parolesVertiba.replace(/\s+/g, '');
            });
        })();
    </script>
</body>
</html>
