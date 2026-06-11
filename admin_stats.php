<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once("./bdd/env.php");
include_once("./bdd/BddConnexionUtils.php");
include_once("./bdd/BddAdminStatsUtils.php");

$conn = OuvrirConnexionPDO($dbOracle, $db_usernameOracle, $db_passwordOracle);

if (!isset($_SESSION['user_id'])) { header('Location: connexion.php'); exit(); }

$sql = "SELECT COUNT(*) as nb FROM vik_administrateur 
        WHERE cli_courriel = (SELECT cli_courriel FROM vik_client WHERE cli_num = :id)";
$stmt = $conn->prepare($sql);
$stmt->execute(['id' => $_SESSION['user_id']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result['NB'] == 0) { header('Location: index.php'); exit(); }

$meilleurs_clients = MeilleursClients($conn);
$lignes_utilisees  = LignesPlusUtilisees($conn);
$reservations      = ReservationsParPeriode($conn);
?>

<!DOCTYPE html>
<html lang="fr">
<?php include_once("./includes/head.php"); ?>

<style>
    .nav-tabs .nav-link { color: #555; }
    .nav-tabs .nav-link.active { 
        color: rgb(210, 10, 40); 
        border-bottom: 2px solid rgb(210, 10, 40);
        font-weight: 500;
    }
    .section-title {
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #888;
        margin-bottom: 12px;
    }
    .table thead th {
        background-color: #222;
        color: #fff;
        font-weight: 500;
        font-size: 13px;
    }
    .rank { 
        color: rgb(210, 10, 40); 
        font-weight: 600; 
        width: 30px;
    }
</style>

<body>
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container mt-4 mb-5">

        <h2 class="fw-bold mb-1">Statistiques</h2>
        <p class="text-muted mb-4">Espace administrateur — Viking Transport</p>

        <ul class="nav nav-tabs mb-4" id="statsTabs">
            <li class="nav-item">
                <a class="nav-link active" href="#" onclick="showTab('clients', this)">Meilleurs clients</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="showTab('lignes', this)">Lignes utilisées</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="showTab('periodes', this)">Réservations par période</a>
            </li>
        </ul>

        <!-- Meilleurs clients -->
        <div id="tab-clients">
            <p class="section-title">Top 10 clients par nombre de réservations</p>
            <div class="card border-0 shadow-sm">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Réservations</th>
                            <th>Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($meilleurs_clients as $i => $c): ?>
                        <tr>
                            <td class="rank"><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($c['CLI_NOM']) ?></td>
                            <td><?= htmlspecialchars($c['CLI_PRENOM']) ?></td>
                            <td><?= $c['NB_RESERVATIONS'] ?></td>
                            <td><?= $c['CLI_NB_POINTS_TOT'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Lignes utilisées -->
        <div id="tab-lignes" style="display:none">
            <p class="section-title">Lignes classées par nombre d'utilisations</p>
            <div class="card border-0 shadow-sm">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Ligne</th>
                            <th>Utilisations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lignes_utilisees as $i => $l): ?>
                        <tr>
                            <td class="rank"><?= $i + 1 ?></td>
                            <td>Ligne <?= trim($l['LIG_NUM']) ?></td>
                            <td><?= $l['NB_UTILISATIONS'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Réservations par période -->
        <div id="tab-periodes" style="display:none">
            <p class="section-title">Nombre de réservations par mois</p>
            <div class="card border-0 shadow-sm">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Période</th>
                            <th>Réservations</th>
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

    <script>
    function showTab(tab, el) {
        document.querySelectorAll('[id^="tab-"]').forEach(d => d.style.display = 'none');
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        document.getElementById('tab-' + tab).style.display = 'block';
        el.classList.add('active');
        return false;
    }
    </script>
</body>
</html>