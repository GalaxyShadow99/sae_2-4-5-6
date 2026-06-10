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
                    <?php if (empty($horaires)): ?>
                        <div class="alert alert-warning m-3">Aucun horaire trouvé pour cet arrêt.</div>
                    <?php else: ?>
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