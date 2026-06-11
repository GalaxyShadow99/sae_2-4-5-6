<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
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
						Bienvenue
					</span>
				</div>
                <h1 class="display-5 fw-bold text-dark mb-3">
                    Réseau Viking Transport
                </h1>
                <p class="lead text-secondary">
                    Bienvenue sur l'interface de gestion du réseau de transport urbain. 
                    Utilisez la barre de navigation pour explorer les lignes.
                </p>
            </div>
        </div>

        <div class="p-4 p-md-5 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10">
    
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="fw-bold text-dark h4 mb-0 tracking-wider text-uppercase">
                        <span style="color: rgb(210, 10, 40);">|</span> Lignes
                    </h2>
                </div>
            </div>

            <div class="row align-items-center gy-4">
                
                <div class="col-md-6 col-12 order-2 order-md-1">
                    <p class="lead fs-6 text-secondary mb-4">
                        Découvrez toutes les lignes du réseau Viking Transport, leurs itinéraires, leurs arrêts ainsi que les horaires de passage en temps réel pour vos déplacements urbains.
                    </p>
                    <a href="lignes.php" class="btn px-4 py-2 fw-semibold rounded-3 shadow-sm text-white hover-scale" 
                       style="background-color: rgb(210, 10, 40); border: 1px solid rgb(210, 10, 40);">
                        <i class="bi bi-bus-front me-2"></i>Afficher les lignes
                    </a>
                </div>

                <div class="col-md-6 col-12 order-1 order-md-2 text-center text-md-end">
                    <img src="/assets/map.png" 
                         alt="Illustration des lignes" 
                         class="img-fluid rounded-3 border border-secondary border-opacity-10 shadow-sm" 
                         style="max-height: 200px; width: 100%; object-fit: cover;">
                </div>

            </div>
        </div>

    </main>

    <!-- Pour avoir accès à la base de données-->

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>
</body>

</html>