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
    .table-viking thead { background-color: #2c3e50; color: white; }
</style>

<body>
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container mt-5">

        <?php if ($ville_selectionnee): ?>

            <a href="horaires.php?lig_num=<?= $lig_selectionnee ?>" class="btn btn-outline-secondary mb-3">← Retour aux arrêts</a>
            <h1 class="mb-1 fw-bold">Ligne <?= $lig_selectionnee ?></h1>
            <p class="text-muted mb-4"><?= RecupereVille($conn, $ville_selectionnee) ?></p>

            <div class="card shadow-sm">
                <div class="card-header fw-semibold" style="background-color:#2c3e50; color:white;">
                    Grille horaire
                </div>
                <div class="card-body p-0">
                        <?php if (empty($horaires) && $is_terminus): ?>
                            <div class="alert alert-info m-3">Cet arrêt est un terminus — changez de direction pour voir les prochains départs.</div>
                        <?php else: ?>
                            <?php if (empty($horaires)): ?>
                                <div class="alert alert-warning m-3">Aucun horaire trouvé pour cet arrêt.</div>
                            <?php endif; ?>
                        <table class="table table-striped table-hover mb-0 table-viking">
                            <thead>
                                <tr>
                                    <th>Arrêt</th>
                                    <th>Heure</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($horaires as $h): ?>
                                <tr>
                                    <td><?= RecupereVille($conn, $h['COM_CODE_INSEE_ARRET']) ?></td>
                                    <td><?= $h['NOE_HEURE_PASSAGE'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($lig_selectionnee): ?>

            <a href="lignes.php" class="btn btn-outline-secondary mb-3">← Retour aux lignes</a>
            <h1 class="mb-1 fw-bold">Ligne <?= $lig_selectionnee ?></h1>
            <p class="text-muted mb-4">Sélectionnez un arrêt pour voir ses horaires</p>

            <div class="card shadow-sm">
                <div class="card-header fw-semibold" style="background-color:#2c3e50; color:white;">
                    Arrêts de la ligne
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($villes as $v): ?>
                        <?php $nomVille = RecupereVille($conn, $v['COM_CODE_INSEE']); ?>
                        <a href="horaires.php?lig_num=<?= $lig_selectionnee ?>&ville=<?= $v['COM_CODE_INSEE'] ?>"
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($nomVille ?: $v['COM_NOM']) ?>
                            <span class="text-muted">→</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php else: ?>
            <div class="alert alert-info">Aucune ligne sélectionnée. <a href="lignes.php">Voir les lignes</a></div>
        <?php endif; ?>

    </main>

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>
</body>
</html>