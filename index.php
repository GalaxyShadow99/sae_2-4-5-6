<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
?>

<!DOCTYPE html>
<html lang="fr" class="h-100"> 

<?php include_once("./includes/head.php"); ?>

<body class="bg-light">
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
                    Votre solution de mobility urbaine à travers toute la Normandie. Planifiez vos trajets, consultez les horaires et réservez vos voyages en toute simplicité.
                </p>
            </div>
        </div>

        <div class="p-4 p-md-5 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10 mb-5 text-center">
            <div class="row align-items-center">
                <div class="col-12 col-md-8 text-md-start mb-3 mb-md-0">
                    <h3 class="fw-bold text-dark h4 mb-2">Un déplacement de prévu ?</h3>
                    <p class="text-secondary mb-0">Calculez instantanément le trajet le plus rapide ou le plus court pour vous rendre à destination.</p>
                </div>
                <div class="col-12 col-md-4 text-md-end">
                    <a href="trajet.php" class="btn px-4 py-2 fw-semibold rounded-3 shadow-sm btn-dark hover-scale">
                        <i class="bi bi-search me-2"></i>Rechercher un itinéraire
                    </a>
                </div>
            </div>
        </div>

        <div class="p-4 p-md-5 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10 mb-5">
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="fw-bold text-dark h4 mb-0 tracking-wider text-uppercase">
                        <span style="color: rgb(210, 10, 40);">|</span> Lignes du réseau
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
                         style="max-height: 220px; width: 100%; object-fit: cover;">
                </div>
            </div>
        </div>

        <div class="p-4 p-md-5 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10 mb-5">
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="fw-bold text-dark h4 mb-0 tracking-wider text-uppercase">
                        <span style="color: rgb(210, 10, 40);">|</span> Présentation du Projet
                    </h2>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6 col-12">
                    <h3 class="h5 fw-bold text-dark mb-3">Pour vos déplacements</h3>
                    
                    <div class="mb-4">
                        <h4 class="h6 fw-bold text-dark mb-1">
                            <i class="bi bi-search me-2" style="color: rgb(210, 10, 40);"></i>Calcul de trajets
                        </h4>
                        <p class="small text-secondary mb-0">Trouvez instantanément le meilleur itinéraire entre deux communes normandes, que vous privilégiez le chemin le plus court ou le plus rapide.</p>
                    </div>

                    <div class="mb-4">
                        <h4 class="h6 fw-bold text-dark mb-1">
                            <i class="bi bi-clock me-2" style="color: rgb(210, 10, 40);"></i>Horaires en temps réel
                        </h4>
                        <p class="small text-secondary mb-0">Accédez aux fiches d'arrêts complètes et visualisez les heures de passage précises des bus pour planifier votre journée sans surprise.</p>
                    </div>

                    <div class="mb-4">
                        <h4 class="h6 fw-bold text-dark mb-1">
                            <i class="bi bi-map me-2" style="color: rgb(210, 10, 40);"></i>Carte interactive
                        </h4>
                        <p class="small text-secondary mb-0">Explorez visuellement les lignes du réseau sur la carte et cliquez directement sur un tracé pour réserver votre voyage en un instant.</p>
                    </div>
                </div> <div class="col-lg-6 col-12">
                    <h3 class="h5 fw-bold text-dark mb-3">Votre espace personnel</h3>

                    <div class="mb-4">
                        <h4 class="h6 fw-bold text-dark mb-1">
                            <i class="bi bi-ticket-perforated me-2" style="color: rgb(210, 10, 40);"></i>Réservation simplifiée
                        </h4>
                        <p class="small text-secondary mb-0">Réservez vos correspondances facilement via un parcours d'achat rapide, que vous ayez déjà un compte ou non.</p>
                    </div>

                    <div class="mb-4">
                        <h4 class="h6 fw-bold text-dark mb-1">
                            <i class="bi bi-gift me-2" style="color: rgb(210, 10, 40);"></i>Cagnotte Fidélité
                        </h4>
                        <p class="small text-secondary mb-0">Cumulez automatiquement des points à chaque kilomètre parcouru et utilisez-les pour débloquer des réductions sur vos prochains billets.</p>
                    </div>

                    <div class="mb-4">
                        <h4 class="h6 fw-bold text-dark mb-1">
                            <i class="bi bi-person-check me-2" style="color: rgb(210, 10, 40);"></i>Suivi de compte
                        </h4>
                        <p class="small text-secondary mb-0">Retrouvez l'historique complet de vos trajets, gerez vos informations personnelles et suivez l'évolution de vos points depuis votre profil.</p>
                    </div>
                </div> </div>
        </div>

        <div class="row g-4 text-center">
            <div class="col-6 col-md-3">
                <div class="p-3 bg-white rounded-4 border border-secondary border-opacity-10 shadow-sm">
                    <div class="h3 fw-bold mb-1" style="color: rgb(210, 10, 40);">19</div>
                    <div class="small text-secondary text-uppercase tracking-wider">Lignes Actives</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-white rounded-4 border border-secondary border-opacity-10 shadow-sm">
                    <div class="h3 fw-bold mb-1" style="color: rgb(210, 10, 40);">100%</div>
                    <div class="small text-secondary text-uppercase tracking-wider">Normand</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-white rounded-4 border border-secondary border-opacity-10 shadow-sm">
                    <div class="h3 fw-bold mb-1" style="color: rgb(210, 10, 40);">Fidélité</div>
                    <div class="small text-secondary text-uppercase tracking-wider">1 pt / 10 km</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-white rounded-4 border border-secondary border-opacity-10 shadow-sm">
                    <div class="h3 fw-bold mb-1" style="color: rgb(210, 10, 40);">Temps Réel</div>
                    <div class="small text-secondary text-uppercase tracking-wider">Grilles Horaires</div>
                </div>
            </div>
        </div>

    </main>

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>
</body>
</html>