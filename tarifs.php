<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
?>

<!DOCTYPE html>
<html lang="fr" class="h-100"> 

<?php include_once("./includes/head.php"); ?>

<body class="d-flex flex-column h-100 bg-light">
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container my-5 flex-shrink-0">
        
        <div class="row mb-5 text-center">
            <div class="col-lg-8 mx-auto">
                <div class="mb-3">
                    <span class="badge px-3 py-2 rounded-pill fw-semibold text-uppercase tracking-wider" 
                          style="background-color: rgba(210, 10, 40, 0.1); color: rgb(210, 10, 40);">
                        Grille Tarifaire
                    </span>
                </div>
                <h1 class="display-5 fw-bold text-dark mb-3">
                    Nos Tarifs & Fidélité
                </h1>
                <p class="lead text-secondary">
                    Découvrez la transparence de nos prix indexés à la distance, ainsi que les avantages de notre système de fidélité pour vos déplacements en Normandie.
                </p>
            </div>
        </div>

        <div class="row g-4">
            
            <div class="col-lg-6 col-12">
                <div class="p-4 p-md-5 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10 h-100">
                    <h2 class="fw-bold text-dark h4 mb-3 tracking-wider text-uppercase">
                        <span style="color: rgb(210, 10, 40);">|</span> Tarifs selon la distance
                    </h2>
                    <p class="text-secondary mb-4">
                        Le prix de votre trajet dépend de la distance parcourue. Plus vous voyagez, plus le tarif est avantageux au kilomètre.
                    </p>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-secondary text-uppercase fs-7 tracking-wider">
                                <tr>
                                    <th scope="col" class="border-0 ps-3">Tranche</th>
                                    <th scope="col" class="border-0">Distance</th>
                                    <th scope="col" class="border-0 text-end pe-3">Prix de base</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td class="fw-semibold ps-3">1</td><td>0 à 10 km</td><td class="text-end fw-bold text-dark pe-3">5,00 €</td></tr>
                                <tr><td class="fw-semibold ps-3">2</td><td>11 à 20 km</td><td class="text-end fw-bold text-dark pe-3">7,00 €</td></tr>
                                <tr><td class="fw-semibold ps-3">3</td><td>21 à 30 km</td><td class="text-end fw-bold text-dark pe-3">10,00 €</td></tr>
                                <tr><td class="fw-semibold ps-3">4</td><td>31 à 40 km</td><td class="text-end fw-bold text-dark pe-3">12,00 €</td></tr>
                                <tr><td class="fw-semibold ps-3">5</td><td>41 à 50 km</td><td class="text-end fw-bold text-dark pe-3">16,00 €</td></tr>
                                <tr><td class="fw-semibold ps-3">6</td><td>51 à 60 km</td><td class="text-end fw-bold text-dark pe-3">20,00 €</td></tr>
                                <tr><td class="fw-semibold ps-3">7</td><td>61 à 80 km</td><td class="text-end fw-bold text-dark pe-3">30,00 €</td></tr>
                                <tr><td class="fw-semibold ps-3">8</td><td>81 à 100 km</td><td class="text-end fw-bold text-dark pe-3">40,00 €</td></tr>
                                <tr><td class="fw-semibold ps-3">9</td><td>101 à 140 km</td><td class="text-end fw-bold text-dark pe-3">50,00 €</td></tr>
                                <tr><td class="fw-semibold ps-3">10</td><td>141 à 160 km</td><td class="text-end fw-bold text-dark pe-3">60,00 €</td></tr>
                                <tr><td class="fw-semibold ps-3">11</td><td>161 à 200 km</td><td class="text-end fw-bold text-dark pe-3">70,00 €</td></tr>
                                <tr><td class="fw-semibold ps-3">12</td><td>201 à 300 km</td><td class="text-end fw-bold text-dark pe-3">80,00 €</td></tr>
                                <tr><td class="fw-semibold ps-3">13</td><td>301 à 500 km</td><td class="text-end fw-bold text-dark pe-3">90,00 €</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-12 d-flex flex-column justify-content-between">
                
                <div class="p-4 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10 mb-4 flex-grow-1">
                    <h2 class="fw-bold text-dark h5 mb-3 tracking-wider text-uppercase">
                        <span style="color: rgb(210, 10, 40);">|</span> Statuts Fidélité
                    </h2>
                    <p class="small text-secondary mb-3">
                        Votre statut évolue selon votre historique de kilomètres cumulés. Plus votre grade est élevé, plus la réduction permanente est forte.
                    </p>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light text-secondary text-uppercase fs-7">
                                <tr>
                                    <th scope="col" class="border-0 ps-2">Niveau</th>
                                    <th scope="col" class="border-0 text-end">Seuil</th>
                                    <th scope="col" class="border-0 text-end pe-2">Remise</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td class="ps-2 fw-medium text-dark">Nouveau</td><td class="text-end text-secondary small">Dès 10 pts</td><td class="text-end fw-bold text-success pe-2">-5 %</td></tr>
                                <tr><td class="ps-2 fw-medium text-dark">Poussin</td><td class="text-end text-secondary small">Dès 400 pts</td><td class="text-end fw-bold text-success pe-2">-10 %</td></tr>
                                <tr><td class="ps-2 fw-medium text-dark">Junior</td><td class="text-end text-secondary small">Dès 3 000 pts</td><td class="text-end fw-bold text-success pe-2">-20 %</td></tr>
                                <tr><td class="ps-2 fw-medium text-dark">Argent</td><td class="text-end text-secondary small">Dès 10 000 pts</td><td class="text-end fw-bold text-success pe-2">-35 %</td></tr>
                                <tr><td class="ps-2 fw-medium text-dark">Or</td><td class="text-end text-secondary small">Dès 50 000 pts</td><td class="text-end fw-bold text-success pe-2">-50 %</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="p-4 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10 mb-4 flex-grow-1">
                    <h2 class="fw-bold text-dark h5 mb-3 tracking-wider text-uppercase">
                        <span style="color: rgb(210, 10, 40);">|</span> Utiliser ses points
                    </h2>
                    <p class="small text-secondary mb-3">
                        À chaque réservation, vous pouvez choisir de convertir une partie de votre solde de points pour déduire un montant forfaitaire de votre facture.
                    </p>
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="p-2 bg-light rounded-3 border border-secondary border-opacity-10">
                                <div class="fw-bold text-dark small">100 pts</div>
                                <div class="fw-bold text-danger h6 mb-0 mt-1">-1 €</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-light rounded-3 border border-secondary border-opacity-10">
                                <div class="fw-bold text-dark small">500 pts</div>
                                <div class="fw-bold text-danger h6 mb-0 mt-1">-7 €</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-light rounded-3 border border-secondary border-opacity-10">
                                <div class="fw-bold text-dark small">1000 pts</div>
                                <div class="fw-bold text-danger h6 mb-0 mt-1">-15 €</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10 flex-grow-1">
                    <h2 class="fw-bold text-dark h5 mb-3 tracking-wider text-uppercase">
                        <span style="color: rgb(210, 10, 40);">|</span> Règles d'acquisition
                    </h2>
                    
                    <div class="d-flex align-items-start mb-3">
                        <div class="badge p-2 rounded-3 me-3" style="background-color: rgba(210, 10, 40, 0.1); color: rgb(210, 10, 40);">
                            <i class="bi bi-plus-circle-fill fs-6"></i>
                        </div>
                        <div>
                            <h4 class="h6 fw-bold text-dark mb-0">Calcul des gains</h4>
                            <p class="small text-secondary mb-0">Vous accumulez automatiquement 1 point de fidélité par tranche de 10 kilomètres parcourus.</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start mb-3">
                        <div class="badge p-2 rounded-3 me-3" style="background-color: rgba(210, 10, 40, 0.1); color: rgb(210, 10, 40);">
                            <i class="bi bi-shield-check fs-6"></i>
                        </div>
                        <div>
                            <h4 class="h6 fw-bold text-dark mb-0">Minimum Garanti</h4>
                            <p class="small text-secondary mb-0">Chaque voyage validé sur le réseau vous rapporte au minimum 1 point, quelle que soit la distance.</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start">
                        <div class="badge p-2 rounded-3 me-3" style="background-color: rgba(210, 10, 40, 0.1); color: rgb(210, 10, 40);">
                            <i class="bi bi-exclamation-triangle-fill fs-6"></i>
                        </div>
                        <div>
                            <h4 class="h6 fw-bold text-dark mb-0">Expiration du compte</h4>
                            <p class="small text-secondary mb-0">Les points expirent après 1 an sans activité de connexion. Le compte est supprimé après 2 ans d'inactivité complète.</p>
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