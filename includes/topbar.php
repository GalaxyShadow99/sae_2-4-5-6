<nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3 border-bottom border-secondary border-opacity-25 sticky-top">
    <div class="container"> 

        <div class="col-lg-4 d-flex justify-content-start align-items-center">
            <a class="navbar-brand fw-bold text-uppercase tracking-wider fs-5 text-white d-flex align-items-center mb-0" href="index.php">
                <img src="assets/logo_blanc.png" alt="Logo" height="45" class="me-2 rounded-1 shadow-sm">
                <span>
                    <span style="color: rgb(255, 220, 0);">Viking</span> Transport
                </span>
            </a>
        </div>

        <button class="navbar-toggler border-0 navbar-dark" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarViking"
                aria-controls="navbarViking"
                aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse col-lg-8" id="navbarViking">

            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-1 gap-lg-3 col-lg-6 justify-content-lg-center"> 
                <li class="nav-item">
                    <a class="nav-link active px-3 rounded-2 transition" aria-current="page" href="index.php">Accueil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 rounded-2 transition" href="lignes.php">Lignes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 rounded-2 transition" href="reserver.php">Réserver</a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2 mb-3 mb-lg-0 col-lg-6 justify-content-lg-end">
                <a href="connexion.php" class="btn btn-link text-white text-decoration-none px-3 py-2 transition hover-scale">
                    Connexion
                </a>
                <a href="inscription.php" class="btn text-white px-3 py-2 fw-semibold rounded-3 shadow-sm transition hover-scale" 
                   style="background-color: rgb(210, 10, 40); border: 1px solid rgb(210, 10, 40);">
                    Inscription
                </a>
            </div>

        </div>
    </div>
</nav>
