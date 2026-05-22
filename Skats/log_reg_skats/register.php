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
                <h1>Laipni lūgti To Do List</h1>
                <p>Reģistrējieties savā kontā, lai piekļūtu visādiem piedāvājumiem un būtu viens no mūsu locekļiem.</p>
                <form action="../../Includes/log_reg_inc/reg_inc.php" method="POST">
                    <h3>Lietotājvārds vai vārds</h3>
                    <input type="text" name="lietotajvards" placeholder='Lietotājvārds vai vārds' required>


                    <h3>Parole</h3>
                    <input type="password" id="parole" name="parole" placeholder='Parole' required>
                        <div class="proles_drošiba">
                            <p>Parolei jābūt vismaz 8 rakstzīmēm, un tai jāietver lielie burti, cipari un speciālās rakstzīmes.</p>
                            <p id="rule-garums" class="parole-noteikums"><i class="fa fa-check" aria-hidden="true"></i>Astoņas rakstzīmes</p>
                            <p id="rule-lielais" class="parole-noteikums"><i class="fa fa-check" aria-hidden="true"></i>Lielais burts</p>
                            <p id="rule-cipars" class="parole-noteikums"><i class="fa fa-check" aria-hidden="true"></i>Cipars</p>
                            <p id="rule-specialais" class="parole-noteikums"><i class="fa fa-check" aria-hidden="true"></i>Speciālā rakstzīme</p>
                        </div>     
                    <h3>Apstiprināt paroli</h3>
                    <input type="password" name="parole_apstiprinat" placeholder='Apstiprināt paroli' required> 
                    <button type="submit"><i class="fa fa-user-plus"></i>Reģistrēties</button>
                </form>
                <p>Jau ir konts? <a href="login.php">Pieteikties</a></p>
            </div>
        </div>
    </main>
    <script>
        (() => {
            const paroleIevade = document.getElementById('parole');

            if (!paroleIevade) {
                return;
            }

            const noteikumi = {
                garums: document.getElementById('rule-garums'),
                lielais: document.getElementById('rule-lielais'),
                cipars: document.getElementById('rule-cipars'),
                specialais: document.getElementById('rule-specialais')
            };

            function setIzpildits(elem, irIzpildits) {
                if (!elem) {
                    return;
                }
                elem.classList.toggle('izpildits', irIzpildits);
            }

            function atjaunotParolesNoteikumus() {
                const parole = paroleIevade.value;

                setIzpildits(noteikumi.garums, parole.length >= 8);
                setIzpildits(noteikumi.lielais, /[A-Z]/.test(parole));
                setIzpildits(noteikumi.cipars, /[0-9]/.test(parole));
                setIzpildits(noteikumi.specialais, /[^a-zA-Z0-9]/.test(parole));
            }

            paroleIevade.addEventListener('input', atjaunotParolesNoteikumus);
        })();
    </script>
</body>
</html>