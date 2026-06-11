<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once("./bdd/env.php");
include_once("./bdd/BddConnexionUtils.php");
include_once("./bdd/BddAdminStatsUtils.php");

$conn = OuvrirConnexionPDO($dbOracle, $db_usernameOracle, $db_passwordOracle);

// Vérification admin
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

// Récupération des stats
$meilleurs_clients = MeilleursClients($conn);
$lignes_utilisees  = LignesPlusUtilisees($conn);
$reservations      = ReservationsParPeriode($conn);
?>

<!DOCTYPE html>
<html lang="fr">
<?php include_once("./includes/head.php"); ?>

<body>
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container mt-5">
        <h1 class="mb-4 fw-bold">Statistiques</h1>

        <!-- Meilleurs clients -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold" style="background-color:#2c3e50; color:white;">
                Meilleurs clients
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Points</th>
                            <th>Réservations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($meilleurs_clients as $c): ?>
                        <tr>
                            <td><?= $c['CLI_NOM'] ?></td>
                            <td><?= $c['CLI_PRENOM'] ?></td>
                            <td><?= $c['CLI_NB_POINTS_TOT'] ?></td>
                            <td><?= $c['NB_RESERVATIONS'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Lignes les plus utilisées -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold" style="background-color:#2c3e50; color:white;">
                Lignes les plus utilisées
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Ligne</th>
                            <th>Nombre d'utilisations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lignes_utilisees as $l): ?>
                        <tr>
                            <td>Ligne <?= trim($l['LIG_NUM']) ?></td>
                            <td><?= $l['NB_UTILISATIONS'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Réservations par période -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold" style="background-color:#2c3e50; color:white;">
                Réservations par période
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Période</th>
                            <th>Nombre de réservations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $r): ?>
                        <tr>
                            <td><?= $r['PERIODE'] ?></td>
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