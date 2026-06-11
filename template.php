<!DOCTYPE html>
<html lang="fr">

<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
include_once("./includes/head.php"); 
?>

<body class="bg-light">
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container mt-4">
    <!-- contenu de la page ici ! 
    le site utilise Bootstrap pour son interface afin de ne pas avoir à utiliser du CSS manuel de trop....
    pensez à aller vous servir la doc donne plein d'exemples et de composants pré faits
     https://getbootstrap.com/
     https://getbootstrap.com/docs/5.3/examples/
    --> 
     <h1 class="text-center"> SAE </h1>
    </main>

    <!-- Pour avoir accès à la base de données-->



    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>
</body>

</html>