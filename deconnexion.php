<?php
// connexion au systeme de session en cours
session_start();
//effacer toutes les variables stockées en sessions 
session_unset();
// Ddestruction physique du fichier de session sur le serveur
session_destroy();
//redirection vers page accueil
header("Location: index.php");
exit();
?>