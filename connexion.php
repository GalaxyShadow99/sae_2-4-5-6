<?php
if (session_status() === PHP_SESSION_NONE){
  session_start();  
}
//chargements de outils de connexion et des requêtes necessaires
require_once './bdd/env.php';
require_once './bdd/BddUtils.php';
//variable en cas d'erreurs
$message_erreur = "";
// vérification si la page est chargé suite au clic sur le  bouton se connecter
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // connexion a la base de donnée oracle configuré dans env.php
    $conn = OuvrirConnexionPDO ($dbOracle, $db_usernameOracle, $db_passwordOracle);
    if ($conn) {
        // apppel de la fonction  userAllow avec l'email et le mot de passe saisi
        $client = userAllowed($conn, $_POST['email'], $_POST['mot_de_passe']);
        // si les identifiants sont bon on stock l'id, le prenom dans le session du serv
        if ($client) {
            $_SESSION['user_id'] = $client['cli_num'];
            $_SESSION['user_prenom'] = $client['cli_prenom'];
            // redirection auto de l'ut vers la page d'accueil
            header("Location: index.php");
            exit();
        }
        else {
            $message_erreur = "Identifiants incorrects";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<?php include_once("./includes/head.php"); ?>
<body>
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container mt-5" style="max-width: 400px;">
        <h2 class="mb-3">Connexion</h2>

        <?php if(!empty($message_erreur)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($message_erreur) ?></div>
        <?php endif; ?>

        <form action="connexion.php" method="POST">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Mot de passe</label>
                <input type="password" class="form-control" name="mot_de_passe" required>
            </div>
            <input type="submit" class="btn btn-primary w-100" value="Se connecter">
        </form>
    </main>

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>
</body>
</html>