<?php
include_once("./bdd/env.php");
include_once("./bdd/BddUtils.php");
include_once("./bdd/LigneUtils.php");
$conn = OuvrirConnexionPDO($dbOracle, $db_usernameOracle, $db_passwordOracle);
?>

<!DOCTYPE html>
<html lang="fr">

<?php include_once("./includes/head.php"); ?>

<body>
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container mt-4">

        <?php if (isset($_GET['lig_num'])): ?>

            <a href="horaires.php" class="btn btn-outline-secondary mb-3">← Retour aux lignes</a>
            <h1 class="mb-4">Ligne <?php echo $_GET['lig_num']; ?></h1>

            <?php $horaires = ListeHorairesLigne($conn, $_GET['lig_num']); ?>

            <?php if (empty($horaires)): ?>
                <div class="alert alert-warning">Aucun horaire trouvé pour cette ligne.</div>
            <?php else: ?>
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Départ</th>
                            <th>Arrivée</th>
                            <th>Heure</th>
                            <th>Distance (km)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($horaires as $h): ?>
                        <tr>
                            <td><?php echo $h['COM_CODE_INSEE_DEPART']; ?></td>
                            <td><?php echo $h['COM_CODE_INSEE_ARRIVEE']; ?></td>
                            <td><?php echo $h['ETA_HEURE']; ?></td>
                            <td><?php echo $h['ETA_DISTANCE']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php else: ?>

            <h1 class="mb-4">Nos lignes</h1>
            <div class="row">
                <?php $lignes = ListeLignes($conn);
                foreach ($lignes as $l): ?>
                <div class="col-md-3 mb-3">
                    <a href="horaires.php?lig_num=<?php echo $l['LIG_NUM']; ?>" class="card text-decoration-none text-dark">
                        <div class="card-body text-center">
                            <h5 class="card-title text-primary">Ligne <?php echo $l['LIG_NUM']; ?></h5>
                            <p class="card-text small text-muted">
                                <?php echo $l['COM_CODE_INSEE_DEBU']; ?> → <?php echo $l['COM_CODE_INSEE_TERM']; ?>
                            </p>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </main>

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>
</body>

</html>