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
                <form action="../../Includes/log_reg_inc/log_inc.php" method="POST">
                    <h3>Lietotajvārds</h3>
                    <input type="text" name="lietotajvards" placeholder='Lietotajvards' required>
                    <h3>Parole</h3>
                    <input type="password" name="parole" placeholder='Parole' required>   
                    <button type="submit"><i class="fa fa-sign-in"></i>Pieteikties</button>
                </form>
                <p>Nav konta? <a href="register.php">Reģistrēties</a></p>
            </div>
        </div>
    </main>
</body>
</html>