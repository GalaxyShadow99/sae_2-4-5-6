<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>

<!DOCTYPE html>
<html lang="fr" class="h-100"> 

<?php include_once("./includes/head.php"); ?>

<body class="d-flex flex-column h-100 bg-light text-dark">
    <?php include_once("./includes/topbar.php"); ?>
    
    <style>
        .card-member {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card-member:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
        }
    </style>

    <main class="container my-5 flex-shrink-0">
        
        <!-- En-tête principal -->
        <div class="row mb-5 text-center">
            <div class="col-lg-8 mx-auto">
                <div class="mb-3">
                    <span class="badge px-3 py-2 rounded-pill fw-semibold text-uppercase tracking-wider" 
                          style="background-color: rgba(210, 10, 40, 0.1); color: rgb(210, 10, 40);">
                        L'agence DeviK
                    </span>
                </div>
                <h1 class="display-5 fw-bold text-dark mb-2">Équipe de développement</h1>
                <p class="lead text-secondary tracking-wide text-uppercase fs-6 fw-bold">
                    « Une requête, des solutions. »
                </p>
            </div>
        </div>

        <!-- Section Histoire & Philosophie -->
        <div class="p-4 p-md-5 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10 mb-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-12">
                    <h2 class="fw-bold text-dark h4 mb-3 text-uppercase tracking-wider">
                        <span style="color: rgb(210, 10, 40);">|</span> À propos de DeviK
                    </h2>
                    <p class="text-secondary lh-lg mb-0">
                        Issue de la collaboration de huit étudiants lors de notre cursus universitaire, l'entreprise trouve son origine dans un projet académique destiné à nous confronter au développement web concret. Ce projet initial ayant révélé une excellente cohésion et une forte complémentarité au sein de notre groupe, les huit membres fondateurs ont officiellement créé <strong>DeviK</strong> le 12 juin 2002 à Caen. Notre philosophie repose avant tout sur cette complémentarité d'équipe et notre polyvalence technique. Après plusieurs années d'activité sur divers marchés, nous concevons aujourd'hui une application sur mesure pour <strong>Viking Transport</strong>, qui représente à ce jour notre plus grand client historique.
                    </p>
                </div>
            </div>
        </div>

        <!-- Section Grille des Membres -->
        <div class="p-4 p-md-5 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10">
    
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="fw-bold text-dark h4 mb-0 tracking-wider text-uppercase">
                        <span style="color: rgb(210, 10, 40);">|</span> Membres & Rôles
                    </h2>
                </div>
            </div>

            <div class="row g-4">
                
                <!-- Membre 1 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm bg-light card-member">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="bi bi-person-circle display-4" style="color: rgb(210, 10, 40);"></i>
                            </div>
                            <h5 class="fw-bold mb-1">AHMADI Mohammad Elyas</h5>
                            <p class="text-secondary small mb-3">Développement PHP et conception générale de l'application.</p>
                            <span class="badge bg-dark rounded-pill px-3 py-2">Dev PHP & Conception</span>
                        </div>
                    </div>
                </div>

                <!-- Membre 2 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm bg-light card-member">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="bi bi-person-circle display-4" style="color: rgb(210, 10, 40);"></i>
                            </div>
                            <h5 class="fw-bold mb-1">CHAIGNON Nathan</h5>
                            <p class="text-secondary small mb-3">Codéveloppement des modules et de l'architecture back-end.</p>
                            <span class="badge bg-dark rounded-pill px-3 py-2">Codéveloppeur Back-end</span>
                        </div>
                    </div>
                </div>
                
                <!-- Membre 3 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm bg-light card-member">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="bi bi-person-circle display-4" style="color: rgb(210, 10, 40);"></i>
                            </div>
                            <h5 class="fw-bold mb-1">CHUQUET Anaël</h5>
                            <p class="text-secondary small mb-3">Conception de l'architecture logicielle and intégration front-end.</p>
                            <span class="badge bg-dark rounded-pill px-3 py-2">Architecte & Front-end</span>
                        </div>
                    </div>
                </div>
                
                <!-- Membre 4 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm bg-light card-member">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="bi bi-person-circle display-4" style="color: rgb(210, 10, 40);"></i>
                            </div>
                            <h5 class="fw-bold mb-1">COLLET Léo</h5>
                            <p class="text-secondary small mb-3">Responsable de l'identité visuelle et du design graphique de l'interface.</p>
                            <span class="badge bg-dark rounded-pill px-3 py-2">Designer d'interface (UI)</span>
                        </div>
                    </div>
                </div>
                
                <!-- Membre 5 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm bg-light card-member">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="bi bi-person-circle display-4" style="color: rgb(210, 10, 40);"></i>
                            </div>
                            <h5 class="fw-bold mb-1">CONSTANTIN Thomas</h5>
                            <p class="text-secondary small mb-3">Définition de l'architecture globale et développement des structures back-end.</p>
                            <span class="badge bg-dark rounded-pill px-3 py-2">Architecte & Back-end</span>
                        </div>
                    </div>
                </div>
                
                <!-- Membre 6 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm bg-light card-member">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="bi bi-person-circle display-4" style="color: rgb(210, 10, 40);"></i>
                            </div>
                            <h5 class="fw-bold mb-1">GRENECHE Mathéo</h5>
                            <p class="text-secondary small mb-3">Développement des vues front-end et création des maquettes fonctionnelles.</p>
                            <span class="badge bg-dark rounded-pill px-3 py-2">Maquettage & Front-end</span>
                        </div>
                    </div>
                </div>
                
                <!-- Membre 7 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm bg-light card-member">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="bi bi-person-circle display-4" style="color: rgb(210, 10, 40);"></i>
                            </div>
                            <h5 class="fw-bold mb-1">GUILBERT Joan</h5>
                            <p class="text-secondary small mb-3">Développement PHP, conception de modules et élaboration des requêtes complexes SQL.</p>
                            <span class="badge bg-dark rounded-pill px-3 py-2">Dev PHP & Requêtes SQL</span>
                        </div>
                    </div>
                </div>
                
                <!-- Membre 8 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm bg-light card-member">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="bi bi-person-circle display-4" style="color: rgb(210, 10, 40);"></i>
                            </div>
                            <h5 class="fw-bold mb-1">PRENVEILLE Noé</h5>
                            <p class="text-secondary small mb-3">Modélisation, conception du système d'information et structuration de la base de données SQL.</p>
                            <span class="badge bg-dark rounded-pill px-3 py-2">Conception & Base de données</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </main>

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>
</body>
</html>