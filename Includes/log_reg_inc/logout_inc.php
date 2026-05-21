<?php
session_start();
session_destroy();

header('Location: ../../Skats/log_reg_skats/login.php');
exit; 