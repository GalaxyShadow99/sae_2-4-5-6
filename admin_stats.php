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
$ca_total          = ChiffreAffairesTotal($conn);
$ca_lignes         = ChiffreAffairesParLigne($conn);
$clients_points    = ClientsPlusDePoints($conn);
$heures_pointe     = HeureDePointe($conn);
$trajets_pop       = TrajetsPopulaires($conn);
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
    .table tbody tr:hover { background-color: rgba(210, 10, 40, 0.05); }
    .rank { color: rgb(210, 10, 40); font-weight: 600; width: 30px; }
    .btn-voir { 
        color: rgb(210, 10, 40); 
        border-color: rgb(210, 10, 40); 
    }
    .btn-voir:hover { 
        background-color: rgb(210, 10, 40); 
        color: white; 
    }
</style>

<body>
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container mt-4 mb-5">

        <h2 class="fw-bold mb-1">Statistiques</h2>
        <p class="text-muted mb-4">Espace administrateur — Viking Transport</p>

        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link active" href="#" onclick="showTab('clients', this); return false;">Meilleurs clients</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="showTab('lignes', this); return false;">Lignes utilisées</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="showTab('periodes', this); return false;">Réservations par période</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="showTab('ca', this); return false;">Chiffre d'affaires</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="showTab('points', this); return false;">Clients fidèles</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="showTab('pointe', this); return false;">Heures de pointe</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="showTab('trajets', this); return false;">Trajets populaires</a>
            </li>
        </ul>

        <!-- Meilleurs clients -->
        <div id="tab-clients">
            <p class="section-title">Top clients par nombre de réservations</p>
            <div class="card border-0 shadow-sm">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>#</th><th>Nom</th><th>Prénom</th><th>Réservations</th><th>Points</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($meilleurs_clients as $i => $c): ?>
                        <tr <?= $i >= 10 ? 'class="extra" style="display:none"' : '' ?>>
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
            <?php if (count($meilleurs_clients) > 10): ?>
            <div class="text-center mt-2">
                <button class="btn btn-sm btn-outline-secondary btn-voir" onclick="toggleExtra(this, 'clients')">
                    Voir tout (<?= count($meilleurs_clients) ?> résultats)
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Lignes utilisées -->
        <div id="tab-lignes" style="display:none">
            <p class="section-title">Lignes classées par nombre d'utilisations</p>
            <div class="card border-0 shadow-sm">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>#</th><th>Ligne</th><th>Utilisations</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lignes_utilisees as $i => $l): ?>
                        <tr <?= $i >= 10 ? 'class="extra" style="display:none"' : '' ?>>
                            <td class="rank"><?= $i + 1 ?></td>
                            <td>Ligne <?= trim($l['LIG_NUM']) ?></td>
                            <td><?= $l['NB_UTILISATIONS'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($lignes_utilisees) > 10): ?>
            <div class="text-center mt-2">
                <button class="btn btn-sm btn-outline-secondary btn-voir" onclick="toggleExtra(this, 'lignes')">
                    Voir tout (<?= count($lignes_utilisees) ?> résultats)
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Réservations par période -->
        <div id="tab-periodes" style="display:none">
            <p class="section-title">Nombre de réservations par mois</p>
            <div class="card border-0 shadow-sm">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Période</th><th>Réservations</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $i => $r): ?>
                        <tr <?= $i >= 10 ? 'class="extra" style="display:none"' : '' ?>>
                            <td><?= $r['PERIODE'] ?></td>
                            <td><?= $r['NB_RESERVATIONS'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($reservations) > 10): ?>
            <div class="text-center mt-2">
                <button class="btn btn-sm btn-outline-secondary btn-voir" onclick="toggleExtra(this, 'periodes')">
                    Voir tout (<?= count($reservations) ?> résultats)
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Chiffre d'affaires -->
        <div id="tab-ca" style="display:none">
            <p class="section-title">Chiffre d'affaires total : <strong><?= number_format($ca_total, 2) ?> €</strong></p>
            <div class="card border-0 shadow-sm">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>#</th><th>Ligne</th><th>CA Total</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ca_lignes as $i => $l): ?>
                        <tr <?= $i >= 10 ? 'class="extra" style="display:none"' : '' ?>>
                            <td class="rank"><?= $i + 1 ?></td>
                            <td>Ligne <?= trim($l['LIG_NUM']) ?></td>
                            <td><?= number_format($l['CA_TOTAL'], 2) ?> €</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($ca_lignes) > 10): ?>
            <div class="text-center mt-2">
                <button class="btn btn-sm btn-outline-secondary btn-voir" onclick="toggleExtra(this, 'ca')">
                    Voir tout (<?= count($ca_lignes) ?> résultats)
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Clients fidèles -->
        <div id="tab-points" style="display:none">
            <p class="section-title">Top clients par points fidélité</p>
            <div class="card border-0 shadow-sm">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>#</th><th>Nom</th><th>Prénom</th><th>Points en cours</th><th>Points total</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients_points as $i => $c): ?>
                        <tr <?= $i >= 10 ? 'class="extra" style="display:none"' : '' ?>>
                            <td class="rank"><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($c['CLI_NOM']) ?></td>
                            <td><?= htmlspecialchars($c['CLI_PRENOM']) ?></td>
                            <td><?= $c['CLI_NB_POINTS_EC'] ?></td>
                            <td><?= $c['CLI_NB_POINTS_TOT'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($clients_points) > 10): ?>
            <div class="text-center mt-2">
                <button class="btn btn-sm btn-outline-secondary btn-voir" onclick="toggleExtra(this, 'points')">
                    Voir tout (<?= count($clients_points) ?> résultats)
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Heures de pointe -->
        <div id="tab-pointe" style="display:none">
            <p class="section-title">Top 5 des heures les plus fréquentées</p>
            <div class="card border-0 shadow-sm">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>#</th><th>Heure</th><th>Passages</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($heures_pointe as $i => $h): ?>
                        <tr <?= $i >= 10 ? 'class="extra" style="display:none"' : '' ?>>
                            <td class="rank"><?= $i + 1 ?></td>
                            <td><?= $h['HEURE'] ?>h00</td>
                            <td><?= $h['NB'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Trajets populaires -->
        <div id="tab-trajets" style="display:none">
            <p class="section-title">Top 10 des trajets les plus réservés</p>
            <div class="card border-0 shadow-sm">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>#</th><th>Départ</th><th>Arrivée</th><th>Réservations</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trajets_pop as $i => $t): ?>
                        <tr <?= $i >= 10 ? 'class="extra" style="display:none"' : '' ?>>
                            <td class="rank"><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($t['DEPART']) ?></td>
                            <td><?= htmlspecialchars($t['ARRIVEE']) ?></td>
                            <td><?= $t['NB'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($trajets_pop) > 10): ?>
            <div class="text-center mt-2">
                <button class="btn btn-sm btn-outline-secondary btn-voir" onclick="toggleExtra(this, 'trajets')">
                    Voir tout (<?= count($trajets_pop) ?> résultats)
                </button>
            </div>
            <?php endif; ?>
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
    }

    function toggleExtra(btn, tab) {
        const rows = document.querySelectorAll('#tab-' + tab + ' tr.extra');
        const visible = rows[0].style.display !== 'none';
        rows.forEach(r => r.style.display = visible ? 'none' : '');
        btn.textContent = visible
            ? 'Voir tout (' + rows.length + ' résultats)'
            : 'Réduire';
    }
    </script>
</body>
</html>