<?php
if (session_status() === PHP_SESSION_NONE){
  session_start();  
}
//chargements de outils de connexion et des requêtes necessaires
require_once './bdd/env.php';
require_once './bdd/BddClientUtils.php';
//variable en cas d'erreurs
$message_erreur = "";
$success = $_SESSION['login_success'] ?? '';
unset($_SESSION['login_success']);
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
            $_SESSION['login_success'] = "Bienvenue " . $client['CLI_PRENOM'] . " ! .";
            header('Location: connexion.php');
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

    <?php if (!empty($success)): ?>
        <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header text-white" style="background: linear-gradient(135deg, #198754, #20c997);">
                        <h5 class="modal-title fw-bold" id="successModalLabel">Connexion réussie</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <div class="display-4 mb-3">✓</div>
                        <p class="mb-0 fs-5"><?= htmlspecialchars($success) ?></p>
                    </div>
                    <div class="modal-footer justify-content-center border-0 pb-4">
                        <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">Continuer</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modalElement = document.getElementById('successModal');
                const modal = new bootstrap.Modal(modalElement);
                modal.show();

                modalElement.addEventListener('hidden.bs.modal', function () {
                    window.location.href = 'index.php';
                });

                setTimeout(function () {
                    modal.hide();
                }, 2500);
            });
        </script>
    <?php endif; ?>
</body>
</html>