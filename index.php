<!DOCTYPE html>
<html lang="fr" class="h-100"> <?php include_once("./includes/head.php"); ?>

<body class="d-flex flex-column h-100 bg-light text-dark">
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container my-5 flex-shrink-0">
        
        <div class="row mb-5 text-center">
            <div class="col-lg-8 mx-auto">
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill mb-3 fw-semibold text-uppercase tracking-wider">
                    DeviK
                </span>
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
						<span class="text-primary">|</span> Lignes
					</h2>
				</div>
			</div>

			<div class="row align-items-center gy-4">
				
				<div class="col-md-7 col-12 order-2 order-md-1">
					<p class="lead fs-6 text-secondary mb-4">
						Découvrez toutes les lignes du réseau Viking Transport, leurs itinéraires, leurs arrêts ainsi que les horaires de passage en temps réel pour vos déplacements urbains.
					</p>
					<a href="lignes.php" class="btn btn-primary px-4 py-2 fw-semibold rounded-3 shadow-sm transition">
						<i class="bi bi-bus-front me-2"></i>Afficher les lignes
					</a>
				</div>

				<div class="col-md-5 col-12 order-1 order-md-2 text-center">
					<img src="https://nomad.normandie.fr/sites/default/files/2024-01/carte%20nomad%20train.jpg" alt="Illustration des lignes" class="img-fluid rounded-3" style="max-height: 180px; object-fit: contain;">
				</div>

			</div>
		</div>

    </main>

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>
</body>

</html>
