<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
require_once './bdd/env.php';
require_once './bdd/BddConnexionUtils.php';
require_once './bdd/BddAdminClientUtils.php';

$conn = OuvrirConnexionPDO($dbOracle, $db_usernameOracle, $db_passwordOracle);

if (!isset($_SESSION['user_id']) || !isUserAdmin($conn, $_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr" class="h-100"> 

<?php 
include_once("./includes/head.php");
?>

<body>
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container my-5 flex-shrink-0">
        
        <div class="row mb-5 text-center">
            <div class="col-lg-8 mx-auto">
                <div class="mb-3">
                    <span class="badge px-3 py-2 rounded-pill fw-semibold text-uppercase tracking-wider" 
                          style="background-color: rgba(210, 10, 40, 0.1); color: rgb(210, 10, 40);">
                        Espace Administrateur
                    </span>
                </div>
                <h1 class="display-5 fw-bold text-dark mb-3">
                    Tableau de bord de gestion
                </h1>
                <p class="lead text-secondary">
                    Bienvenue sur l'interface d'administration de Viking Transport.
                    Utilisez les options ci-dessous pour gérer les lignes, consulter les statistiques et les informations clients.
                </p>
            </div>
        </div>

        <div class="row g-4">
            
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10 text-center p-4">
                    <div class="card-body d-flex flex-column align-items-center">
                        <div class="mb-4 p-3 rounded-circle" style="background-color: rgba(210, 10, 40, 0.1);">
                            <i class="bi bi-signpost-split fs-1" style="color: rgb(210, 10, 40);"></i>
                        </div>
                        <h2 class="fw-bold text-dark h5 mb-3 text-uppercase tracking-wider">Gestion des lignes</h2>
                        <p class="text-secondary mb-4 flex-grow-1">
                            Ajoutez, modifiez ou supprimez les lignes du réseau Viking Transport pour assurer une expérience optimale.
                        </p>
                        <a href="admin_modif_ligne.php" class="btn w-100 py-2 fw-semibold rounded-3 text-white transition-all hover-scale" 
                           style="background-color: rgb(210, 10, 40);">
                            Gérer les lignes
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="card h-100 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10 text-center p-4">
                    <div class="card-body d-flex flex-column align-items-center">
                        <div class="mb-4 p-3 rounded-circle" style="background-color: rgba(210, 10, 40, 0.1);">
                            <i class="bi bi-bar-chart-line fs-1" style="color: rgb(210, 10, 40);"></i>
                        </div>
                        <h2 class="fw-bold text-dark h5 mb-3 text-uppercase tracking-wider">Statistiques</h2>
                        <p class="text-secondary mb-4 flex-grow-1">
                            Consultez les indicateurs de performances du réseau pour améliorer continuellement l'expérience usager.
                        </p>
                        <a href="admin_stats.php" class="btn w-100 py-2 fw-semibold rounded-3 text-white transition-all hover-scale" 
                           style="background-color: rgb(210, 10, 40);">
                            Voir les statistiques
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="card h-100 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10 text-center p-4">
                    <div class="card-body d-flex flex-column align-items-center">
                        <div class="mb-4 p-3 rounded-circle" style="background-color: rgba(210, 10, 40, 0.1);">
                            <i class="bi bi-people fs-1" style="color: rgb(210, 10, 40);"></i>
                        </div>
                        <h2 class="fw-bold text-dark h5 mb-3 text-uppercase tracking-wider">Base Clients</h2>
                        <p class="text-secondary mb-4 flex-grow-1">
                            Accédez aux informations des usagers enregistrés sur le réseau Viking Transport.
                        </p>
                        <a href="admin_client.php" class="btn w-100 py-2 fw-semibold rounded-3 text-white transition-all hover-scale" 
                           style="background-color: rgb(210, 10, 40);">
                            Voir les clients
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>
</body>
</html>