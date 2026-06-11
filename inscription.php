<?php 
if (session_status() === PHP_SESSION_NONE) session_start();

include_once("./bdd/env.php"); 
include_once("./bdd/Inscription_utils.php"); 
include_once("./bdd/BddConnexionUtils.php"); 


$error = "";
$success = $_SESSION['inscription_success'] ?? '';
unset($_SESSION['inscription_success']);

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
        else if (!empty(VerifExiste($conn, $mail, $mdp))) $error = "Un compte existe déjà avec cette adresse mail.";
        else {
            $res = AjouteClient($conn, $nom, $prenom, $ville, $tel, $mail, $mdp);

            if ($res) {
                $_SESSION['inscription_success'] = "Votre inscription a bien été prise en compte.";
                header('Location: inscription.php');
                exit;
            } else {
                // cath error ORA-12899 d'oracle : value too large for column
                if ($conn->errorInfo()[0] == '22001') {
                    echo "Erreur : une des valeurs dépasse la taille maximale autorisée.";
                    $error = "Erreur de saisie : une des valeurs est trop longue.";
                } else {
                echo "Erreur lors de l'insertion : " . $conn->errorInfo()[2];
                $error = "Erreur de serveur : insertion échouée.";
            }
        }
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

                    <?php if(!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error)?></div>
                    <?php endif;?>

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

    <?php if (!empty($success)): ?>
        <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header text-white" style="background: linear-gradient(135deg, #198754, #20c997);">
                        <h5 class="modal-title fw-bold" id="successModalLabel">Inscription réussie</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <div class="display-4 mb-3">✓</div>
                        <p class="mb-0 fs-5"><?= htmlspecialchars($success) ?></p>
                    </div>
                    <div class="modal-footer justify-content-center border-0 pb-4">
                        <a href="index.php" class="btn btn-success px-4">Continuer</a>
                    </div>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = new bootstrap.Modal(document.getElementById('successModal'));
                modal.show();
            });
        </script>
    <?php endif; ?>

    <?php
    // Fermeture de la connexion BDD
    if (isset($conn)) {
        $conn = null;
    }
    ?>
</body>
</html>