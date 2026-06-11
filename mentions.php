<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>

<!DOCTYPE html>
<html lang="fr" class="h-100">

<?php include_once("./includes/head.php"); ?>

<body class="d-flex flex-column h-100 bg-light text-dark">
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container my-5 flex-shrink-0">

        <div class="row mb-5 text-center">
            <div class="col-lg-8 mx-auto">
                <div class="mb-3">
                    <span class="badge px-3 py-2 rounded-pill fw-semibold text-uppercase"
                          style="background-color: rgba(210, 10, 40, 0.1); color: rgb(210, 10, 40);">
                        Informations légales
                    </span>
                </div>
                <h1 class="display-5 fw-bold text-dark mb-3">Mentions légales</h1>
                <p class="lead text-secondary">
                    Informations relatives à l'éditeur, à l'hébergement et à vos droits en tant qu'utilisateur.
                </p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">

                <div class="p-4 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10 mb-4">
                    <h2 class="fw-bold text-dark h5 mb-4 text-uppercase">
                        <span style="color: rgb(210, 10, 40);">|</span> Éditeur du site
                    </h2>
                    <p class="text-secondary mb-2">Le présent site et l'application associée sont édités par :</p>
                    <ul class="list-unstyled text-secondary mb-0">
                        <li class="mb-1"><i class="bi bi-building me-2" style="color: rgb(210, 10, 40);"></i><strong class="text-dark">DeviK</strong> - Micro-entreprise</li>
                        <li class="mb-1"><i class="bi bi-geo-alt me-2" style="color: rgb(210, 10, 40);"></i>8 Rue Anton Tchekhov, 14123 Ifs, France</li>
                        <li><i class="bi bi-envelope me-2" style="color: rgb(210, 10, 40);"></i><a href="mailto:contact@devik.fr" class="text-decoration-none" style="color: rgb(210, 10, 40);">contact@devik.fr</a></li>
                    </ul>
                    <p class="text-secondary small mt-3 mb-0">Directeur de la publication : Le représentant légal de DeviK.</p>
                </div>

                <div class="p-4 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10 mb-4">
                    <h2 class="fw-bold text-dark h5 mb-4 text-uppercase">
                        <span style="color: rgb(210, 10, 40);">|</span> Propriété intellectuelle
                    </h2>
                    <p class="text-secondary mb-0">
                        L'ensemble des éléments présents sur le site et l'application - textes, graphismes, logos, interfaces, bases de données, fonctionnalités, codes sources, images et documents - sont protégés par les lois relatives à la propriété intellectuelle.
                        Toute reproduction, représentation, modification, diffusion ou exploitation, totale ou partielle, sans autorisation préalable écrite de DeviK est strictement interdite.
                    </p>
                </div>

                <div class="p-4 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10 mb-4">
                    <h2 class="fw-bold text-dark h5 mb-4 text-uppercase">
                        <span style="color: rgb(210, 10, 40);">|</span> Responsabilité
                    </h2>
                    <p class="text-secondary mb-2">
                        DeviK met tout en œuvre pour assurer l'exactitude des informations diffusées et la disponibilité du service.
                        Toutefois, DeviK ne saurait être tenu responsable des interruptions temporaires du service, des erreurs éventuelles ou de tout dommage résultant de l'utilisation du site ou de l'application.
                    </p>
                    <p class="text-secondary mb-0">
                        L'utilisateur demeure seul responsable de l'utilisation qu'il fait des informations et fonctionnalités mises à sa disposition.
                    </p>
                </div>

                <div class="p-4 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10 mb-4">
                    <h2 class="fw-bold text-dark h5 mb-4 text-uppercase">
                        <span style="color: rgb(210, 10, 40);">|</span> Données personnelles
                    </h2>
                    <p class="text-secondary mb-2">
                        Les données personnelles collectées sont traitées conformément à la réglementation française et européenne applicable, notamment le Règlement Général sur la Protection des Données (RGPD).
                    </p>
                    <p class="text-secondary mb-2">
                        Les utilisateurs disposent d'un droit d'accès, de rectification, d'effacement, de limitation, d'opposition et de portabilité de leurs données.
                    </p>
                    <p class="text-secondary mb-0">
                        Toute demande peut être adressée à :
                        <a href="mailto:contact@devik.fr" class="text-decoration-none fw-semibold" style="color: rgb(210, 10, 40);">contact@devik.fr</a>
                    </p>
                </div>

                <div class="p-4 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10 mb-4">
                    <h2 class="fw-bold text-dark h5 mb-4 text-uppercase">
                        <span style="color: rgb(210, 10, 40);">|</span> Cookies
                    </h2>
                    <p class="text-secondary mb-0">
                        Le site peut utiliser des cookies techniques nécessaires à son bon fonctionnement ainsi que, le cas échéant, des cookies de mesure d'audience.
                        Les utilisateurs peuvent gérer leurs préférences via les paramètres de leur navigateur ou le bandeau de gestion des cookies lorsqu'il est présent.
                    </p>
                </div>

                <div class="p-4 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10">
                    <h2 class="fw-bold text-dark h5 mb-4 text-uppercase">
                        <span style="color: rgb(210, 10, 40);">|</span> Droit applicable
                    </h2>
                    <p class="text-secondary mb-0">
                        Les présentes mentions légales sont soumises au droit français.
                        Tout litige relatif à leur interprétation ou à leur exécution relève des juridictions françaises compétentes.
                    </p>
                </div>

            </div>
        </div>

    </main>

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>
</body>

</html>
