<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once("./bdd/env.php");
include_once("./bdd/BddConnexionUtils.php");
include_once("./bdd/BddAdminStatsUtils.php");

$conn = OuvrirConnexionPDO($dbOracle, $db_usernameOracle, $db_passwordOracle);

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}

$sql = "SELECT COUNT(*) as nb FROM vik_administrateur 
        WHERE cli_courriel = (SELECT cli_courriel FROM vik_client WHERE cli_num = :id)";
$stmt = $conn->prepare($sql);
$stmt->execute(['id' => $_SESSION['user_id']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['NB'] == 0) {
    header('Location: index.php');
    exit();
}

$meilleurs_clients = MeilleursClients($conn);
$lignes_utilisees  = LignesPlusUtilisees($conn);
$reservations      = ReservationsParPeriode($conn);
?>

<!DOCTYPE html>
<html lang="fr">
<?php include_once("./includes/head.php"); ?>

<style>
    .stat-card-header {
        background-color: #1a1a2e;
        color: white;
        border-left: 4px solid rgb(210, 10, 40);
        padding: 12px 16px;
        font-weight: 500;
        font-size: 15px;
    }
    .badge-rank {
        background-color: rgb(210, 10, 40);
        color: white;
        font-size: 12px;
        padding: 3px 8px;
        border-radius: 99px;
    }
    .badge-ligne {
        background-color: #1a1a2e;
        color: rgb(255, 220, 0);
        font-size: 13px;
        padding: 4px 12px;
        border-radius: 99px;
        font-family: monospace;
    }
    .table thead th {
        background-color: #1a1a2e;
        color: rgb(255, 220, 0);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: none;
    }
    .table tbody tr:hover {
        background-color: rgba(210, 10, 40, 0.05);
    }
    .page-header {
        border-left: 5px solid rgb(210, 10, 40);
        padding-left: 16px;
    }
    .metric-card {
        border-top: 3px solid rgb(210, 10, 40);
        border-radius: 8px;
    }
</style>

<body>
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container mt-5 mb-5">

        <div class="page-header mb-5">
            <h1 class="fw-bold mb-1">Tableau de bord</h1>
            <p class="text-muted mb-0">Statistiques du réseau Viking Transport</p>
        </div>

        <!-- Cartes résumé -->
        <div class="row g-3 mb-5">
            <div class="col-md-4">
                <div class="card metric-card shadow-sm p-3 text-center">
                    <p class="text-muted small mb-1">Clients suivis</p>
                    <h3 class="fw-bold mb-0"><?= count($meilleurs_clients) ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card metric-card shadow-sm p-3 text-center">
                    <p class="text-muted small mb-1">Lignes actives</p>
                    <h3 class="fw-bold mb-0"><?= count($lignes_utilisees) ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card metric-card shadow-sm p-3 text-center">
                    <p class="text-muted small mb-1">Périodes enregistrées</p>
                    <h3 class="fw-bold mb-0"><?= count($reservations) ?></h3>
                </div>
            </div>
        </div>

        <!-- Meilleurs clients -->
        <div class="card shadow-sm mb-4 border-0">
            <div class="stat-card-header">Meilleurs clients</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Points</th>
                            <th>Réservations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($meilleurs_clients as $i => $c): ?>
                        <tr>
                            <td class="ps-3"><span class="badge-rank"><?= $i + 1 ?></span></td>
                            <td class="fw-semibold"><?= htmlspecialchars($c['CLI_NOM']) ?></td>
                            <td><?= htmlspecialchars($c['CLI_PRENOM']) ?></td>
                            <td><?= $c['CLI_NB_POINTS_TOT'] ?> pts</td>
                            <td><?= $c['NB_RESERVATIONS'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Lignes les plus utilisées -->
        <div class="card shadow-sm mb-4 border-0">
            <div class="stat-card-header">Lignes les plus utilisées</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Ligne</th>
                            <th>Utilisations</th>
                            <th>Popularité</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $max = !empty($lignes_utilisees) ? $lignes_utilisees[0]['NB_UTILISATIONS'] : 1;
                        foreach ($lignes_utilisees as $l):
                            $pct = round(($l['NB_UTILISATIONS'] / $max) * 100);
                        ?>
                        <tr>
                            <td class="ps-3"><span class="badge-ligne">Ligne <?= trim($l['LIG_NUM']) ?></span></td>
                            <td><?= $l['NB_UTILISATIONS'] ?></td>
                            <td style="width:40%">
                                <div class="progress" style="height:8px; border-radius:99px;">
                                    <div class="progress-bar" style="width:<?= $pct ?>%; background-color:rgb(210,10,40);"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Réservations par période -->
        <div class="card shadow-sm mb-4 border-0">
            <div class="stat-card-header">Réservations par période</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Période</th>
                            <th>Réservations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $r): ?>
                        <tr>
                            <td class="ps-3 fw-semibold"><?= $r['PERIODE'] ?></td>
                            <td><?= $r['NB_RESERVATIONS'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>
</body>
</html>