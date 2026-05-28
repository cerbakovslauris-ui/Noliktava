<?php
$echoText = '';
$echoType = 'error';

if (isset($_GET['msg'])) {
    $echoText = trim((string)$_GET['msg']);
    $echoType = (isset($_GET['type']) && $_GET['type'] === 'ok') ? 'success' : 'error';
}

if (isset($_GET['error'])) {
    $echoType = 'error';
    switch ($_GET['error']) {
        case 'empty':
            $echoText = 'Lūdzu aizpildi visus laukus!';
            break;
        case 'username':
            $echoText = 'Lietotājvārds nav derīgs!';
            break;
        case 'password':
            $echoText = 'Parole neatbilst noteikumiem!';
            break;
        case 'passwordmatch':
        case 'match':
            $echoText = 'Paroles nesakrīt!';
            break;
        case 'taken':
        case 'exists':
            $echoText = 'Šāds lietotājvārds jau eksistē!';
            break;
        default:
            $echoText = 'Reģistrācija neizdevās!';
            break;
    }
}

if (isset($_GET['success']) || (isset($_GET['signup']) && $_GET['signup'] === 'success')) {
    $echoType = 'success';
    $echoText = 'Reģistrācija veiksmīga!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register</title>
    <link rel="stylesheet" href="../../Css/reg.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
    <a class="back" href="../user.php"><i class="fa fa-arrow-left" aria-hidden="true"></i> Atpakaļ</a>
    <main>
        <div class="reg">
            <div class="reg_teksts">
                <h1>Reģistrēties</h1>
                <form id="registerForm" action="../../Includes/log_reg_inc/reg_inc.php" method="POST" novalidate>
                    <div id="echo-zinojums" class="echo-zinojums <?php echo $echoText !== '' ? htmlspecialchars($echoType, ENT_QUOTES, 'UTF-8') . ' show' : ''; ?>" aria-live="polite">
                        <?php echo htmlspecialchars($echoText, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <h3>Lietotājvārds vai vārds</h3>
                    <input type="text" name="lietotajvards" placeholder='Lietotājvārds vai vārds'>

                    <h3>Parole</h3>
                    <input type="password" id="parole" name="parole" placeholder='Parole'>
                        <div class="paroles_drošība">
                            <p id="rule-garums" class="parole-noteikums"><i class="fa fa-check" aria-hidden="true"></i>Astoņas rakstzīmes</p>
                            <p id="rule-lielais" class="parole-noteikums"><i class="fa fa-check" aria-hidden="true"></i>Lielais burts</p>
                            <p id="rule-cipars" class="parole-noteikums"><i class="fa fa-check" aria-hidden="true"></i>Cipars</p>
                            <p id="rule-specialais" class="parole-noteikums"><i class="fa fa-check" aria-hidden="true"></i>Speciālā rakstzīme</p>
                        </div>     
                    <h3>Apstiprināt paroli</h3>
                    <input type="password" name="parole_apstiprinat" placeholder='Apstiprināt paroli'> 
                    <button type="submit"><i class="fa fa-user-plus"></i>Reģistrēties</button>
                </form>
                <p>Jau ir konts? <a href="login.php">Pieteikties</a></p>
            </div>
        </div>
    </main>
    <script>
        (() => {
            const forma = document.getElementById('registerForm');
            const zinojums = document.getElementById('echo-zinojums');
            const lietotajvardsIevade = document.querySelector('input[name="lietotajvards"]');
            const paroleIevade = document.getElementById('parole');
            const paroleApstiprinatIevade = document.querySelector('input[name="parole_apstiprinat"]');
            const bezAtstarpemIevades = Array.from(document.querySelectorAll('input[name="lietotajvards"], input[name="parole"], input[name="parole_apstiprinat"]'));
            let zinojumaTaimeris;

            if (zinojums && zinojums.classList.contains('show')) {
                zinojumaTaimeris = setTimeout(() => {
                    zinojums.classList.remove('show');
                }, 3000);
            }

            function paraditZinojumu(teksts, tips = 'error') {
                if (!zinojums) {
                    return;
                }

                clearTimeout(zinojumaTaimeris);
                zinojums.textContent = teksts;
                zinojums.className = `echo-zinojums ${tips} show`;

                zinojumaTaimeris = setTimeout(() => {
                    zinojums.classList.remove('show');
                }, 3000);
            }

            function notiritAtstarpes(input) {
                input.value = input.value.replace(/\s+/g, '');
            }

            bezAtstarpemIevades.forEach((input) => {
                input.addEventListener('input', () => notiritAtstarpes(input));
                input.addEventListener('blur', () => notiritAtstarpes(input));
            });

            if (!paroleIevade) {
                return;
            }

            const noteikumi = {
                garums: document.getElementById('rule-garums'),
                lielais: document.getElementById('rule-lielais'),
                cipars: document.getElementById('rule-cipars'),
                specialais: document.getElementById('rule-specialais')
            };

            function paroleIrDrosa(parole) {
                return Array.from(parole).length >= 8
                    && /[A-ZĀČĒĢĪĶĻŅŠŪŽ]/.test(parole)
                    && /[0-9]/.test(parole)
                    && /[^a-zA-ZĀČĒĢĪĶĻŅŠŪŽāčēģīķļņšūž0-9\s]/.test(parole);
            }

            function setIzpildits(elem, irIzpildits) {
                if (!elem) {
                    return;
                }
                elem.classList.toggle('izpildits', irIzpildits);
            }

            function atjaunotParolesNoteikumus() {
                const parole = paroleIevade.value;
                const rakstzimjuSkaits = Array.from(parole).length;

                setIzpildits(noteikumi.garums, rakstzimjuSkaits >= 8);
                setIzpildits(noteikumi.lielais, /[A-ZĀČĒĢĪĶĻŅŠŪŽ]/.test(parole));
                setIzpildits(noteikumi.cipars, /[0-9]/.test(parole));
                setIzpildits(noteikumi.specialais, /[^a-zA-ZĀČĒĢĪĶĻŅŠŪŽāčēģīķļņšūž0-9\s]/.test(parole));
            }

            if (forma) {
                forma.addEventListener('submit', (event) => {
                    const lietotajvards = lietotajvardsIevade.value.trim();
                    const parole = paroleIevade.value;
                    const paroleApstiprinat = paroleApstiprinatIevade.value;

                    if (lietotajvards === '') {
                        event.preventDefault();
                        paraditZinojumu('Lūdzu ievadi lietotājvārdu vai vārdu!');
                        return;
                    }

                    if (/[0-9\s]/.test(lietotajvards)) {
                        event.preventDefault();
                        paraditZinojumu('Lietotājvārdā nedrīkst būt cipari un atstarpes!');
                        return;
                    }

                    if (parole === '') {
                        event.preventDefault();
                        paraditZinojumu('Lūdzu ievadi paroli!');
                        return;
                    }

                    if (!paroleIrDrosa(parole)) {
                        event.preventDefault();
                        paraditZinojumu('Parolei jābūt vismaz 8 rakstzīmēm, lielajam burtam, ciparam un speciālai rakstzīmei!');
                        return;
                    }

                    if (paroleApstiprinat === '') {
                        event.preventDefault();
                        paraditZinojumu('Lūdzu apstiprini paroli!');
                        return;
                    }

                    if (parole !== paroleApstiprinat) {
                        event.preventDefault();
                        paraditZinojumu('Paroles nesakrīt!');
                    }
                });
            }

            paroleIevade.addEventListener('input', atjaunotParolesNoteikumus);
            atjaunotParolesNoteikumus();
        })();
    </script>
</body>
</html>