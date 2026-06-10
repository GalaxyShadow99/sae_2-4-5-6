<?php 
include_once("./bdd/env.php"); 
include_once("./bdd/BddUtils.php"); 
?>
<!DOCTYPE html>
<html lang="fr">
<?php include_once("./includes/head.php"); ?>
<body>
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container pt-3">
        <div class="row mb-4 text-center">
            <div class="col">
                <h1 class="display-5 fw-bold text-dark">Inscription</h1>
                <p class="lead text-muted">Veuillez rentrer vos informations</p>
            </div>
        </div>

        <form action="" method="post">
            <input type="text" id="Cli_nom" name="Cli_nom" placeholder="Nom">
            <input type="text" id="Cli_prenom" name="Cli_prenom" placeholder="Prénom">
            <input type="text" id="Cli_ville" name="Cli_ville" placeholder="Ville">
            <input type="tel" id="Cli_tel" name="Cli_tel" placeholder="Téléphone">
            <input type="email" id="Cli_mail" name="Cli_mail" placeholder="Adresse mail">
            <input type="password" id="Cli_mdp" name="Cli_mdp" placeholder="Mot de passe">
            <input type="password" id="Cli_mdp2" name="Cli_mdp2" placeholder="Confirmer mot de passe">
            <input type="submit" id="submitBtn" name="submitBtn" value="Confirmer">
        </form>
    </main>

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>
</body>
</html>