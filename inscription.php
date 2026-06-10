<?php 
if (session_status() === PHP_SESSION_NONE) session_start();

include_once("./bdd/env.php"); 
include_once("./bdd/Inscription_utils.php"); 

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = OuvrirConnexionPDO($dbOracle, $db_usernameOracle, $db_passwordOracle);
    if ($conn) {
        if (isset($_POST['Cli_nom'])) {
            $nom = trim($_POST['Cli_nom']);
        } else $nom = "";

        if (isset($_POST['Cli_prenom'])) {
            $prenom = trim($_POST['Cli_prenom']);
        } else $prenom = "";
        
        if (isset($_POST['Cli_ville'])) {
            $ville = trim($_POST['Cli_ville']);
        } else $ville = "";
        
        if (isset($_POST['Cli_tel'])) {
            $tel = trim($_POST['Cli_tel']);
        } else $tel = "";
        
        if (isset($_POST['Cli_mail'])) {
            $mail = trim($_POST['Cli_mail']);
        } else $mail = "";
        
        if (isset($_POST['Cli_mdp'])) {
            $mdp = trim($_POST['Cli_mdp']);
        } else $mdp = "";
        
        if (isset($_POST['Cli_mdp2'])) {
            $mdp2 = trim($_POST['Cli_mdp2']);
        } else $mdp2 = "";
        
        if (empty($nom) || empty($prenom) || empty($ville) || empty($tel) || empty($mail) || empty($mdp) || empty($mdp2)) $error = "Tous les champs sont obligatoires.";
        else if ($mdp != $mdp2) $error = "Les mots de passe ne correspondent pas.";
        else {
            $res = AjouteClient($conn, $nom, $prenom, $ville, $tel, $mail, $mdp);

            if ($res) null;
            else $error = "Erreur de serveur : insertion échouée.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<?php include_once("./includes/head.php"); ?>
<body>
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">

                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header bg-secondary text-white text-center py-4 rounded-top-4">
                        <h1 class="h3 mb-0">Inscription</h1>
                        <p class="mb-0 mt-2">Veuillez rentrer vos informations</p>
                    </div>

                    <?php ?>

                    <div class="card-body p-4">

                        <form action="inscription.php" method="POST">

                            <div class="mb-3">
                                <label for="Cli_nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="Cli_nom" name="Cli_nom">
                            </div>

                            <div class="mb-3">
                                <label for="Cli_prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="Cli_prenom" name="Cli_prenom">
                            </div>

                            <div class="mb-3">
                                <label for="Cli_ville" class="form-label">Ville</label>
                                <input type="text" class="form-control" id="Cli_ville" name="Cli_ville">
                            </div>

                            <div class="mb-3">
                                <label for="Cli_tel" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="Cli_tel" name="Cli_tel">
                            </div>

                            <div class="mb-3">
                                <label for="Cli_mail" class="form-label">Adresse mail</label>
                                <input type="email" class="form-control" id="Cli_mail" name="Cli_mail">
                            </div>

                            <div class="mb-3">
                                <label for="Cli_mdp" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="Cli_mdp" name="Cli_mdp">
                            </div>

                            <div class="mb-4">
                                <label for="Cli_mdp2" class="form-label">Confirmation du mot de passe</label>
                                <input type="password" class="form-control" id="Cli_mdp2" name="Cli_mdp2">
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="submitBtn" class="btn btn-lg text-white" style="background-color: rgb(210, 10, 40);">
                                    Confirmer l'inscription
                                </button>
                            </div>

                        </form>

                    </div>
                </div>

            </div>
        </div>
    </main>

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>
</body>
</html>