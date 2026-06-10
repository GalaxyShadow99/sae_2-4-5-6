<nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3 border-bottom border-secondary border-opacity-25 sticky-top">
    <div class="container">

        <a class="navbar-brand fw-bold text-uppercase tracking-wider fs-5 text-white" href="index.php">
            <span class="text-primary">Viking</span> Transport
        </a>

        <button class="navbar-toggler border-0 navbar-dark" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarViking"
                aria-controls="navbarViking"
                aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarViking">

            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-1 gap-lg-3"> 
                <li class="nav-item">
                    <a class="nav-link active px-3 rounded-2 transition" aria-current="page" href="index.php">Accueil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 rounded-2 transition" href="#">Réseau</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 rounded-2 transition" href="#">Lignes</a>
                </li>
            </ul>

            <form class="d-flex me-lg-3 mb-3 mb-lg-0" role="search">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-secondary bg-opacity-10 border-secondary border-opacity-25 text-white-50">
                        <i class="bi bi-search"></i>
                    </span>
                    <input class="form-control bg-secondary bg-opacity-10 border-secondary border-opacity-25 text-white rounded-end"
                           type="search"
                           placeholder="Rechercher..."
                           aria-label="Search"
                           style="max-width: 200px;">
                </div>
            </form>

            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary text-white border-secondary border-opacity-50 dropdown-toggle px-3 py-2 rounded-2 transition"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                    <i class="bi bi-gear me-1"></i> Administration
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-secondary border-opacity-25 mt-2 bg-dark text-white">
                    <li>
                        <a class="dropdown-item text-white-50 opacity-100-hover py-2" href="#">
                            <i class="bi bi-database me-2"></i>Admin BDD
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item text-white-50 opacity-100-hover py-2" href="#">
                            <i class="bi bi-terminal me-2"></i>Logs
                        </a>
                    </li>
                    <li><hr class="dropdown-divider border-secondary opacity-25"></li>
                    <li>
                        <a class="dropdown-item text-white-50 opacity-100-hover py-2" href="#">
                            <i class="bi bi-shield-check me-2"></i>Diagnostic Connexion
                        </a>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</nav>