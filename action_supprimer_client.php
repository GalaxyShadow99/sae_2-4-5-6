<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once './bdd/env.php';
require_once './bdd/BddConnexionUtils.php';
require_once './bdd/BddAdminClientUtils.php';
require_once './bdd/BddClientUtils.php';

$conn = OuvrirConnexionPDO($dbOracle, $db_usernameOracle, $db_passwordOracle);

if (!isset($_SESSION['user_id']) || !isUserAdmin($conn, $_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}

$cli_num = isset($_GET['cli_num']) ? (int)$_GET['cli_num'] : null;
$userDeletedSuccessfully = false;

// ON SUPPR PAS CLIENT 0
if ($cli_num !== null && $cli_num !== 0 && $conn) {
    $userDeletedSuccessfully = deleteClient($conn, $cli_num);
    // pour debug avant pop up 
    // die();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include_once("./includes/head.php"); ?>
</head>
<body class="bg-light">

    <?php if ($userDeletedSuccessfully): ?>
        <div class="modal fade" id="statusModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header text-white border-0 py-3" style="background: linear-gradient(135deg, #198754, #20c997);">
                        <h5 class="modal-title fw-bold mx-auto"><i class="bi bi-check-circle-fill me-2"></i>Action réussie</h5>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <div class="text-success display-1 mb-3">
                            <i class="bi bi-person-check"></i>
                        </div>
                        <p class="mb-0 fs-5 fw-medium text-secondary">Le compte client a bien été supprimé.</p>
                    </div>
                    <div class="modal-footer justify-content-center border-0 pb-4">
                        <a href="admin_client.php" class="btn btn-success px-4 rounded-3 fw-semibold shadow-sm">
                            Continuer
                        </a>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="modal fade" id="statusModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header text-white border-0 py-3" style="background: linear-gradient(135deg, #dc3545, #f25c6c);">
                        <h5 class="modal-title fw-bold mx-auto"><i class="bi bi-exclamation-triangle-fill me-2"></i>Erreur système</h5>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <div class="text-danger display-1 mb-3">
                            <i class="bi bi-person-x"></i>
                        </div>
                        <p class="mb-0 fs-5 fw-medium text-secondary">Impossible de supprimer ce client.</p>
                        <small class="text-muted d-block mt-2">Le compte possède peut-être des réservations actives ou le paramètre est invalide.</small>
                    </div>
                    <div class="modal-footer justify-content-center border-0 pb-4">
                        <a href="admin_client.php" class="btn btn-danger px-4 rounded-3 fw-semibold shadow-sm">
                            Retour à la liste
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php include_once("./includes/jsIncludes.php"); ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalElement = document.getElementById('statusModal');
            const modal = new bootstrap.Modal(modalElement);
            modal.show();

            // Redirection automatique après 3 secondes s'il n'y a pas de clic
            setTimeout(function () {
                window.location.href = 'admin_client.php';
            }, 3000);
        });
    </script>
</body>
</html>