<?php 
include_once("./bdd/env.php"); 
include_once("./bdd/BddUtils.php"); 
?>
<!DOCTYPE html>
<html lang="fr">
<?php include_once("./includes/head.php"); ?>
<body>
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container pt-3">
        <div class="row mb-4 text-center">
            <div class="col">
                <h1 class="display-5 fw-bold text-dark">Réseau Viking Transport</h1>
                <p class="lead text-muted">Voici la liste des lignes qui composent notre réseau</p>
            </div>
        </div>

        <?php
        $filtre = isset($_GET['sens']) ? $_GET['sens'] : 'all';
        if (!in_array($filtre, ['all', 'A', 'B'])) {
            $filtre = 'all';
        }
        ?>

        <div class="row mb-4 justify-content-center">
            <div class="col-md-6 text-center">
                <div class="btn-group p-1 bg-light rounded-pill shadow-sm" role="group" aria-label="Filtrage des lignes">
                    <a href="?sens=all" class="btn rounded-pill px-4 <?= $filtre === 'all' ? 'btn-secondary active' : 'btn-light' ?>">
                        Toutes
                    </a>
                    <a href="?sens=A" class="btn rounded-pill px-4 <?= $filtre === 'A' ? 'btn-success active' : 'btn-light' ?>">
                        Aller (A)
                    </a>
                    <a href="?sens=B" class="btn rounded-pill px-4 <?= $filtre === 'B' ? 'btn-danger active' : 'btn-light' ?>">
                        Retour (B)
                    </a>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-header bg-secondary py-3 px-4">
                <h5 class="text-white mb-0 fw-semibold">Lignes disponibles</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase fs-7 fw-bold text-secondary border-bottom">
                            <tr>
                                <th scope="col" class="ps-4 py-3" style="width: 25%;">N° Ligne</th>
                                <th scope="col" class="py-3">DéPART</th>
                                <th scope="col" class="py-3">TERMINUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $conn = OuvrirConnexionPDO($dbOracle, $db_usernameOracle, $db_passwordOracle);
                            $data = ListeLignes($conn);

                            $nbLignesAffichees = 0;

                            if (!empty($data) && is_array($data)): 
                                foreach ($data as $ligne): 
                                    $numLigne = trim($ligne['LIG_NUM'] ?? '');

                                    $isAller = str_ends_with($numLigne, 'A');
                                    $isRetour = str_ends_with($numLigne, 'B');

                                    if ($filtre === 'A' && !$isAller) continue;
                                    if ($filtre === 'B' && !$isRetour) continue;

                                    $nbLignesAffichees++;

                                    $badgeColor = 'bg-secondary';
                                    if ($isAller) {
                                        $badgeColor = 'bg-success';
                                    } elseif ($isRetour) {
                                        $badgeColor = 'bg-danger';
                                    }
                                    ?>
                                    <tr style="cursor:pointer" onclick="location.href='horaires.php?lig_num=<?= urlencode($numLigne) ?>'" >
                                        <td class="ps-4 py-3">
                                            <span class="badge <?= $badgeColor ?> fs-6 px-3 py-2 font-monospace">
                                                Ligne <?= htmlspecialchars($numLigne) ?>
                                            </span>
                                        </td>
                                        <td class="py-3">
                                            <div>
                                                <span class="fw-semibold text-dark font-monospace"><?= htmlspecialchars(RecupereVille($conn,$ligne['COM_CODE_INSEE_DEBU']) ?? '—') ?></span>
                                            </div>
                                        </td>
                                        <td class="py-3">
                                            <div>
                                                <span class="fw-semibold text-dark font-monospace"><?= htmlspecialchars(RecupereVille($conn,$ligne['COM_CODE_INSEE_TERM']) ?? '—') ?></span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php 
                                endforeach; 
                            endif; 

                            if ($nbLignesAffichees === 0): 
                            ?>
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">
                                        Aucune ligne ne correspond à ce filtre actuellement.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>
</body>
</html>