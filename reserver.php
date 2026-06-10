<!DOCTYPE html>
<html lang="fr">

<?php include_once("./includes/head.php"); ?>

<body>
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container mt-4">
    
     <h1 class="text-center"> Site de la SAE 2.456</h1>

     <?php
        require_once './bdd/env.php';
        require_once './bdd/BddUtils.php';
 
        define('MOD_BDD', 'ORACLE');
        $conn = OuvrirConnexionPDO($dbOracle, $db_usernameOracle, $db_passwordOracle);
 
        // TODO : récupérer les lignes disponibles pour le <select>
        // $lignes = ListeLignes($conn);
 
        // TODO : traitement du formulaire quand il est soumis
        // if ($_SERVER['REQUEST_METHOD'] === 'POST') { ... }
        ?>


    </main>

    <!-- Pour avoir accès à la base de données-->

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>
</body>

</html>