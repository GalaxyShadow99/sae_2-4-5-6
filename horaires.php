<?php
include_once("./bdd/env.php");
include_once("./bdd/BddUtils.php");
$conn = OuvrirConnexionPDO($dbOracle, $db_usernameOracle, $db_passwordOracle);
$lignes = ListeLignes($conn);
$horaires = [];
$lig_selectionnee = null;

if (isset($_GET['lig_num'])) {
    $lig_selectionnee = $_GET['lig_num'];
    $horaires = ListeHorairesLigne($conn, $lig_selectionnee);
}
?>

<!DOCTYPE html>
<html lang="fr">
<?php include_once("./includes/head.php"); ?>

<style>
    .btn-viking { background-color: #c0392b; color: white; border: none; }
    .btn-viking:hover { background-color: #a93226; color: white; }
    .table-viking thead { background-color: #2c3e50; color: white; }
    .ligne-card { border-left: 4px solid #c0392b; }
</style>

<body>
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container mt-5">
        <h1 class="mb-4 fw-bold">Nos Lignes et Horaires</h1>

        <div class="row g-4">

            <!-- Colonne gauche : sélection ligne -->
            <div class="col-md-4">
                <div class="card shadow-sm ligne-card p-3">
                    <form method="GET" action="horaires.php">
                        <label class="form-label fw-semibold">Sélectionnez une ligne</label>
                        <select name="lig_num" class="form-select mb-3">
                            <option value="">Choisir...</option>
                            <?php foreach ($lignes as $l): ?>
                                <option value="<?php echo $l['LIG_NUM']; ?>"
                                    <?php echo ($lig_selectionnee == $l['LIG_NUM']) ? 'selected' : ''; ?>>
                                    Ligne <?php echo $l['LIG_NUM']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-viking w-100">Afficher les horaires</button>
                    </form>
                </div>

                <p class="text-muted mt-4 small">Réservez vos trajets en car facilement et au meilleur prix.</p>
            </div>

            <!-- Colonne droite : tableau horaires -->
            <div class="col-md-8">
                <?php if ($lig_selectionnee): ?>
                    <div class="card shadow-sm">
                        <div class="card-header fw-semibold" style="background-color:#2c3e50; color:white;">
                            Grille horaire — Ligne <?php echo $lig_selectionnee; ?>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($horaires)): ?>
                                <div class="alert alert-warning m-3">Aucun horaire trouvé pour cette ligne.</div>
                            <?php else: ?>
                                <table class="table table-striped table-hover mb-0 table-viking">
                                    <thead>
                                        <tr>
                                            <th>Noeud (Arrêt)</th>
                                            <th>Heure</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($horaires as $h): ?>
                                        <tr>
                                            <td><?php echo RecupereVille($conn, $h['COM_CODE_INSEE_ARRET']); ?></td>
                                            <td><?php echo $h['NOE_HEURE_PASSAGE']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                        <p>← Sélectionnez une ligne pour voir ses horaires</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>
</body>
</html>