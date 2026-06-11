<!DOCTYPE html>
<html lang="fr" class="h-100"> 

<?php include_once("./includes/head.php"); ?>

<body class="d-flex flex-column h-100 bg-light text-dark">
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container my-5 flex-shrink-0">
        
        <div class="row mb-5 text-center">
            <div class="col-lg-8 mx-auto">
                <div class="mb-3">
                    <span class="badge px-3 py-2 rounded-pill fw-semibold text-uppercase tracking-wider" 
                          style="background-color: rgba(210, 10, 40, 0.1); color: rgb(210, 10, 40);">
                        Viking Transport
                    </span>
                </div>
                <h1 class="display-5 fw-bold text-dark mb-3">Équipe de projet</h1>
                <p class="lead text-secondary">
                    Visualisez la composition de l'équipe du projet Viking Transport.
                </p>
            </div>
        </div>

        <div class="p-4 p-md-5 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10">
    
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="fw-bold text-dark h4 mb-0 tracking-wider text-uppercase">
                        <span style="color: rgb(210, 10, 40);">|</span> Membres de l'équipe
                    </h2>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="card border-0 shadow-sm overflow-hidden rounded-4">
                        <img src="/assets/equipe.png" 
                             alt="Présentation de l'équipe" 
                             class="img-fluid" 
                             style="width: 100%; height: auto;">
                    </div>
                </div>
            </div>

        </div>

    </main>

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>
</body>
</html>
