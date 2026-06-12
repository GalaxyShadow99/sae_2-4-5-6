<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once("./bdd/env.php");
include_once("./bdd/BddLigneUtils.php");
$conn = OuvrirConnexionPDO($dbOracle, $db_usernameOracle, $db_passwordOracle);

$lig_selectionnee = null;
$ville_selectionnee = null;
$villes = [];
$horaires = [];

if (isset($_GET['lig_num']) && isset($_GET['ville'])) {
    $lig_selectionnee = $_GET['lig_num'];
    $ville_selectionnee = $_GET['ville'];
    $tous = ListeHorairesLigne($conn, $lig_selectionnee);
    foreach ($tous as $h) {
        if ($h['COM_CODE_INSEE_ARRET'] == $ville_selectionnee) {
            $horaires[] = $h;
        }
    }
    $villes = ObtenirVillesOrdonnees($conn, $lig_selectionnee);
    $is_terminus = false;
    $firstCode = !empty($villes) ? $villes[0]['COM_CODE_INSEE'] : null;
    $lastCode = !empty($villes) ? $villes[count($villes)-1]['COM_CODE_INSEE'] : null;
    if ($firstCode && $ville_selectionnee == $firstCode) {
        $is_terminus = true;
    }
    if ($lastCode && $ville_selectionnee == $lastCode) {
        $is_terminus = true;
    }
        // Récupérer directement le(s) terminus depuis la table vik_ligne (plus simple et fiable)
        $lines = ListeLignes($conn);
        foreach ($lines as $ln) {
            if (trim($ln['LIG_NUM']) == trim($lig_selectionnee)) {
                $debu = $ln['COM_CODE_INSEE_DEBU'];
                $term = $ln['COM_CODE_INSEE_TERM'];
                if ($ville_selectionnee == $debu || $ville_selectionnee == $term) {
                    $is_terminus = true;
                }
                break;
            }
        }
        // Garder la liste ordonnée des arrêts pour l'affichage
        $villes = ObtenirVillesOrdonnees($conn, $lig_selectionnee);
} elseif (isset($_GET['lig_num'])) {
    $lig_selectionnee = $_GET['lig_num'];
    $villes = ObtenirVillesOrdonnees($conn, $lig_selectionnee);
}
?>

<!DOCTYPE html>
<html lang="fr">
<?php include_once("./includes/head.php"); ?>

<style>
        /* Design de la ligne de bus (Timeline) */
        .bus-timeline {
            position: relative;
            padding-left: 2rem;
            margin-top: 1rem;
            margin-bottom: 1rem;
        }
        /* La ligne verticale */
        .bus-timeline::before {
            content: '';
            position: absolute;
            top: 1.5rem;
            bottom: 2rem;
            left: 0.5rem;
            width: 4px;
            background-color: rgb(210, 10, 40);
            border-radius: 4px;
        }
        /* Le conteneur de chaque arrêt */
        .timeline-item {
            position: relative;
            margin-bottom: 0.5rem;
        }
        /* Le point (l'arrêt) sur la ligne */
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -1.9rem; /* Centré sur la ligne */
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            background-color: white;
            border: 4px solid rgb(210, 10, 40);
            border-radius: 50%;
            z-index: 1;
            transition: all 0.2s ease-in-out;
        }
        /* Effet au survol de l'arrêt */
        .timeline-item:hover::before {
            background-color: rgb(210, 10, 40);
            box-shadow: 0 0 0 4px rgba(210, 10, 40, 0.2);
        }
        .hover-bg-light:hover {
            background-color: #f8f9fa;
        }
    </style>

<body class="bg-light">
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container my-5 flex-shrink-0">

        <?php if ($ville_selectionnee && $lig_selectionnee): ?>
            <a href="horaires.php?lig_num=<?= urlencode($lig_selectionnee) ?>" class="btn btn-outline-secondary mb-4">
                <i class="bi bi-arrow-left"></i> Retour aux arrêts
            </a>
            
            <?php 
                // Vérifier si l'utilisateur a cliqué sur un horaire précis pour voir le détail du parcours
                $heure_focus = isset($_GET['focus_heure']) ? $_GET['focus_heure'] : null;
                $details_course = [];
                if ($heure_focus) {
                    $details_course = ObtenirTrajetComplet($conn, $lig_selectionnee, $ville_selectionnee, $heure_focus);
                }
            ?>

            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="card shadow-sm border border-secondary border-opacity-10 rounded-4 h-100">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4">
                            <h3 class="h5 fw-bold text-uppercase" style="color: rgb(210, 10, 40);">
                                <i class="bi bi-clock-history me-2"></i>Horaires de passage
                            </h3>
                            <p class="text-muted small">Cliquez sur un horaire pour afficher le détail des arrêts suivants.</p>
                        </div>
                        <div class="card-body p-4">
                            <?php if (!empty($horaires)): ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($horaires as $h): ?>
                                        <?php 
                                            $h_formate = date('H:i', strtotime($h['NOE_HEURE_PASSAGE'] ?? $h['ETA_HEURE'])); 
                                            $est_actif = ($heure_focus === $h_formate);
                                        ?>
                                        <a href="horaires.php?lig_num=<?= urlencode($lig_selectionnee) ?>&ville=<?= urlencode($ville_selectionnee) ?>&focus_heure=<?= urlencode($h_formate) ?>" 
                                           class="text-decoration-none px-3 py-2 rounded-3 border fw-bold fs-5 shadow-sm transition-all d-inline-block <?= $est_actif ? 'text-white' : 'text-dark bg-light hover-bg-light' ?>"
                                           style="<?= $est_actif ? 'background-color: rgb(210, 10, 40); border-color: rgb(210, 10, 40);' : '' ?>">
                                            <?= $h_formate ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0 border-0 rounded-3">
                                    Aucun horaire disponible.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card shadow-sm border border-secondary border-opacity-10 rounded-4 h-100 bg-white">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4">
                            <h3 class="h5 fw-bold text-uppercase text-dark">
                                <i class="bi bi-info-circle me-2" style="color: #2c3e50;"></i>Détail de la course
                            </h3>
                        </div>
                        <div class="card-body p-4">
                            <?php if ($heure_focus && !empty($details_course)): ?>
                                <div class="bus-timeline">
                                    <?php foreach ($details_course as $index => $etape): ?>
                                        <div class="timeline-item d-flex justify-content-between align-items-center p-2 rounded-3 <?= $index === 0 ? 'bg-light fw-bold' : '' ?>">
                                            <span class="fs-5 text-dark"><?= htmlspecialchars($etape['COM_NOM']) ?></span>
                                            <span class="badge fs-6 px-3 py-2 text-dark bg-secondary bg-opacity-10 rounded-pill"><?= $etape['HEURE'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="d-flex flex-column align-items-center justify-content-center text-center text-muted h-100 py-5">
                                    <i class="bi bi-cursor display-4 mb-3 opacity-50"></i>
                                    <p class="mb-0">Sélectionnez une heure à gauche pour suivre le bus arrêt par arrêt.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($lig_selectionnee): ?>
            <a href="lignes.php" class="btn btn-outline-secondary mb-4">
                <i class="bi bi-arrow-left"></i> Retour aux lignes
            </a>
            
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="d-flex align-items-center mb-4 p-4 rounded-4 shadow-sm border border-secondary border-opacity-10 bg-white">
                        <div class="p-3 rounded-circle me-3" style="background-color: rgba(210, 10, 40, 0.1);">
                            <i class="bi bi-signpost-split fs-2" style="color: rgb(210, 10, 40);"></i>
                        </div>
                        <div>
                            <h1 class="mb-1 fw-bold text-dark">Ligne <?= htmlspecialchars($lig_selectionnee) ?></h1>
                            <p class="text-muted mb-0">Sélectionnez un arrêt pour voir ses horaires</p>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
                        <div class="bus-timeline">
                            <?php foreach ($villes as $v): ?>
                                <?php 
                                    // Idéalement, $v['COM_NOM'] devrait être retourné directement par ta fonction ObtenirVillesOrdonnees()
                                    // Si ce n'est pas le cas, tu peux garder ton appel à RecupereVille() ici.
                                    $nomVille = isset($v['COM_NOM']) ? $v['COM_NOM'] : RecupereVille($conn, $v['COM_CODE_INSEE']); 
                                ?>
                                <div class="timeline-item">
                                    <a href="horaires.php?lig_num=<?= urlencode($lig_selectionnee) ?>&ville=<?= urlencode($v['COM_CODE_INSEE']) ?>" 
                                       class="text-decoration-none d-block p-3 rounded-3 transition-all hover-bg-light border border-transparent d-flex justify-content-between align-items-center text-dark">
                                        <span class="fw-semibold fs-5"><?= htmlspecialchars($nomVille) ?></span>
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-bus-front display-1 text-muted mb-3 opacity-50"></i>
                <h2 class="fw-bold text-dark mb-3">Aucune ligne sélectionnée</h2>
                <p class="text-muted mb-4">Veuillez choisir une ligne pour consulter ses arrêts et horaires.</p>
                <a href="lignes.php" class="btn px-4 py-2 fw-semibold rounded-3 text-white shadow-sm" style="background-color: rgb(210, 10, 40);">
                    Consulter les lignes
                </a>
            </div>
        <?php endif; ?>

    </main>

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>

    <?php
    // Fermeture de la connexion BDD
    if (isset($conn)) {
        $conn = null;
    }
    ?>
</body>
</html>