<?php
if (session_status() === PHP_SESSION_NONE){
  session_start();  
}
//chargements de outils de connexion et des requêtes necessaires
require_once './bdd/env.php';
require_once './bdd/BddClientUtils.php';
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
            $_SESSION['user_id']     = $client['CLI_NUM'];
            $_SESSION['user_prenom'] = $client['CLI_PRENOM'];
            // redirection auto de l'ut vers la page d'accueil
        echo '
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            </head>
            <body>

                <h1 class="text-center"> Bienvenu cher utilisateur </h1>    
            
                <div style="
                    position: fixed;
                    bottom: 30px;
                    right: 30px;
                    background: white;
                    border-left: 4px solid #198754;
                    border-radius: 8px;
                    padding: 14px 20px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    opacity: 0;
                    transform: translateX(20px);
                    transition: all 0.3s ease;
                    z-index: 9999;
                " id="toast">
                    <span style="font-size:1.3rem">✅</span>
                    <div>
                        <div style="font-weight:600; font-size:0.9rem;">Connexion réussie</div>
                        <div style="color:#6c757d; font-size:0.78rem;">Bienvenue ' . htmlspecialchars($client['CLI_PRENOM']) . ' !</div>
                    </div>
                </div>
                <script>
                    const t = document.getElementById("toast");
                    setTimeout(() => { t.style.opacity="1"; t.style.transform="translateX(0)"; }, 100);
                    setTimeout(() => { t.style.opacity="0"; t.style.transform="translateX(20px)"; }, 1800);
                    setTimeout(() => window.location.href="index.php", 3000);
                </script>
            </body>
            </html>';
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