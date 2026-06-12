<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAdmin = false;
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../bdd/env.php';
    require_once __DIR__ . '/../bdd/BddConnexionUtils.php';
    require_once __DIR__ . '/../bdd/BddAdminClientUtils.php';
    $topbarConn = OuvrirConnexionPDO($dbOracle, $db_usernameOracle, $db_passwordOracle);
    if ($topbarConn) {
        $isAdmin = isUserAdmin($topbarConn, $_SESSION['user_id']);
    }
}

$pageCourante = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3 border-bottom border-secondary border-opacity-25 sticky-top">
    
    <style>
        .btn {
            transition: transform 0.3s ease;
        }
        .btn:hover {
            transform: scale(1.07);
        }
        .topBarButton:onclick {
            background-color: rgb(210, 10, 40);
        }
        
    </style>

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
                    <a class="nav-link px-3 rounded-2 transition <?= ($pageCourante === 'index.php') ? 'active' : '' ?>" 
                       <?= ($pageCourante === 'index.php') ? 'aria-current="page"' : '' ?> href="index.php">Accueil</a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <a class="nav-link px-3 rounded-2 transition <?= ($pageCourante === 'trajet.php') ? 'active' : '' ?>" 
                       <?= ($pageCourante === 'trajet.php') ? 'aria-current="page"' : '' ?> href="trajet.php">Trajet</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link px-3 rounded-2 transition <?= ($pageCourante === 'lignes.php') ? 'active' : '' ?>" 
                       <?= ($pageCourante === 'lignes.php') ? 'aria-current="page"' : '' ?> href="lignes.php">Lignes</a>
                </li>
                
                <?php 
                $pagesDropdown = ['reserver.php', 'carte.php', 'tarifs.php'];
                $isDropdownActive = in_array($pageCourante, $pagesDropdown);
                ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle px-3 rounded-2 transition <?= $isDropdownActive ? 'active' : '' ?>" 
                       href="#" 
                       role="button" 
                       data-bs-toggle="dropdown" 
                       aria-expanded="false">
                        Réserver
                    </a>
                    <ul class="dropdown-menu border-0 shadow-sm">
                        <li>
                            <a class="dropdown-item topBarButton py-2 px-3 transition <?= ($pageCourante === 'reserver.php') ? 'active' : '' ?>" href="reserver.php">
                                <i class="bi bi-cursor-fill me-2 opacity-75"></i>Manuellement
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item topBarButton py-2 px-3 transition <?= ($pageCourante === 'carte.php') ? 'active' : '' ?>" href="carte.php">
                                <i class="bi bi-map-fill me-2 opacity-75"></i>Carte interactive
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item topBarButton py-2 px-3 transition <?= ($pageCourante === 'tarifs.php') ? 'active' : '' ?>" href="tarifs.php">
                                <i class="bi bi-tag-fill me-2 opacity-75"></i>Tarifs
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2 mb-3 mb-lg-0 col-lg-6 justify-content-lg-end">
                <?php if (isset($_SESSION['user_id'])): ?>
                    
                    <?php if ($isAdmin): ?>
                        <a href="admin_dashboard.php" class="btn text-dark px-3 py-2 fw-bold rounded-3 shadow-sm transition hover-scale" 
                            style="background-color: #ffc107; border: 1px solid #e0a800;">
                            <i class="bi bi-shield-lock me-1"></i> Admin
                        </a>
                    <?php endif; ?>

                    <a href="profil.php" class="btn text-white px-3 py-2 fw-semibold rounded-3 shadow-sm transition hover-scale <?= ($pageCourante === 'profil.php') ? 'active' : '' ?>" 
                        style="background-color: rgb(33, 37, 41); border: 1px solid rgb(210, 10, 40);">
                        Mon profil
                    </a>
                    <a href="deconnexion.php" class="btn text-white px-3 py-2 fw-semibold rounded-3 shadow-sm transition hover-scale" 
                        style="background-color: rgb(210, 10, 40); border: 1px solid rgb(210, 10, 40);">
                        Déconnexion
                    </a>

                <?php else: ?>
                    <a href="connexion.php" class="btn btn-link text-white text-decoration-none px-3 py-2 transition hover-scale">
                        Connexion
                    </a>
                    <a href="inscription.php" class="btn text-white px-3 py-2 fw-semibold rounded-3 shadow-sm transition hover-scale" 
                        style="background-color: rgb(210, 10, 40); border: 1px solid rgb(210, 10, 40);">
                        Inscription
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </div>
</nav>