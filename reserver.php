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
       
$ok = reserver_ligne($conn, 12, 105, 3, '2025-01-15', 50, 89.90);
if ($ok) {
    echo "Réservation enregistrée !";
} else {
    echo "Erreur lors de la réservation.";
} 

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
