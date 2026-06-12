<?php
if (session_status() === PHP_SESSION_NONE){
  session_start();  
}
//chargements de outils de connexion et des requêtes nécessaires
require_once './bdd/env.php';
require_once './bdd/BddClientUtils.php';
//variable en cas d'erreurs
$message_erreur = "";
$success = $_SESSION['login_success'] ?? '';
unset($_SESSION['login_success']);
// vérification si la page est chargée suite au clic sur le  bouton se connecter
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // connexion a la base de donnée oracle configuré dans env.php
    $conn = OuvrirConnexionPDO ($dbOracle, $db_usernameOracle, $db_passwordOracle);
    if ($conn) {
        // appel de la fonction  userAllow avec l'email et le mot de passe saisi
        $client = userAllowed($conn, $_POST['email'], $_POST['mot_de_passe']);
        // si les identifiants sont bon on stock l'id, le prenom dans le session du serv
        if ($client) {
            $_SESSION['user_id']     = $client['CLI_NUM'];
            $_SESSION['user_prenom'] = $client['CLI_PRENOM'];
            $_SESSION['login_success'] = "Bienvenue " . $client['CLI_PRENOM'] . " !";
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
<html lang="fr" class="h-100">

<?php include_once("./includes/head.php"); ?>

<body class="d-flex flex-column h-100 bg-light text-dark">
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container my-5 flex-shrink-0" style="max-width: 450px;">
        
        <div class="text-center mb-4">
            <div class="mb-2">
                <span class="badge px-3 py-2 rounded-pill fw-semibold text-uppercase tracking-wider" 
                      style="background-color: rgba(210, 10, 40, 0.1); color: rgb(210, 10, 40);">
                    Authentification
                </span>
            </div>
            <h1 class="h3 fw-bold text-dark">Connexion</h1>
        </div>

        <?php if(!empty($message_erreur)): ?>
            <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4" role="alert">
                <?= htmlspecialchars($message_erreur) ?>
            </div>
        <?php endif; ?>

        <div class="p-4 p-md-5 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10">
            <form action="connexion.php" method="POST">
                
                <div class="mb-4">
                    <label class="form-label small fw-semibold text-secondary text-uppercase tracking-wider">Email</label>
                    <div class="input-group">
                        <input type="email" class="form-control bg-light border-start-0 rounded-end-3" name="email" required placeholder="exemple@domaine.fr">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold text-secondary text-uppercase tracking-wider">Mot de passe</label>
                    <div class="input-group">
                        <input type="password" class="form-control bg-light border-start-0 rounded-end-3" name="mot_de_passe" required placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" class="btn text-white btn-lg w-100 fw-bold shadow-sm rounded-3 mt-2 hover-scale" 
                        style="background-color: rgb(210, 10, 40); border: 1px solid rgb(210, 10, 40);">
                    Se connecter
                </button>
            </form>
            
        </div>
        <div class="text-center mt-4">
            <p class="small text-secondary">Vous n'avez pas de compte ? <a href="inscription.php" class="fw-semibold decoration-none" style="color: rgb(210, 10, 40);">Créer un profil</a></p>
        </div>
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

    <?php
    // Fermeture de la connexion BDD
    if (isset($conn)) {
        $conn = null;
    }
    ?>
</body>
</html>