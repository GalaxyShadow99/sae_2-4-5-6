<?php
session_start();
//effacer toutes les variables stockées en sessions 
session_unset();
// Ddestruction physique du fichier de session sur le serveur
session_destroy();
header("Location: index.php");
exit();
?>