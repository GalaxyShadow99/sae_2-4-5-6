<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once './bdd/env.php';
require_once './bdd/BddConnexionUtils.php';
require_once './bdd/BddAdminClientUtils.php';
require_once './bdd/BddClientUtils.php';

$conn = OuvrirConnexionPDO($dbOracle, $db_usernameOracle, $db_passwordOracle);

if (!isset($_SESSION['user_id']) || !isUserAdmin($conn, $_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr" class="h-100"> 

<?php include_once("./includes/head.php"); ?>

<style>
    th[data-sort] {
        cursor: pointer;
        transition: background-color 0.2s;
    }
    th[data-sort]:hover {
        background-color: #e9ecef !important;
    }
    th[data-sort]::after {
        content: " ↕";
        font-size: 0.8em;
        color: #adb5bd;
    }
</style>

<body class="d-flex flex-column h-100 bg-light">
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container my-5 flex-shrink-0">
        
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <div class="mb-2">
                    <span class="badge px-3 py-2 rounded-pill fw-semibold text-uppercase" 
                          style="background-color: rgba(210, 10, 40, 0.1); color: rgb(210, 10, 40);">
                        Espace Administrateur
                    </span>
                </div>
                <a href="admin_dashboard.php" class="btn btn-outline-secondary mb-3">← Retour au dashboard</a>
                <h1 class="display-6 fw-bold text-dark mb-0">Gestion des Clients</h1>
            </div>
            
            <div class="col-md-6 mt-3 mt-md-0 text-md-end">
                <div class="btn-group p-1 bg-white rounded-3 shadow-sm border" role="group" aria-label="Filtrage statut">
                    <button type="button" class="btn btn-sm rounded-2 px-3 btn-secondary active" id="btn-filter-all">
                        Tous
                    </button>
                    <button type="button" class="btn btn-sm rounded-2 px-3 btn-light" id="btn-filter-active">
                        Actifs
                    </button>
                    <button type="button" class="btn btn-sm rounded-2 px-3 btn-light" id="btn-filter-inactive">
                        Inactifs (>1 an)
                    </button>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-dark text-white py-3 px-4">
                <h5 class="mb-0 fw-semibold"><i class="bi bi-people-fill me-2"></i>Comptes Utilisateurs</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="clientTable">
                        <thead class="table-light text-uppercase fs-7 fw-bold text-secondary border-bottom">
                            <tr>
                                <th scope="col" class="ps-4 py-3" data-sort="number">N° Client</th>
                                <th scope="col" class="py-3" data-sort="string">Nom / Prénom</th>
                                <th scope="col" class="py-3">Adresse Courriel</th>
                                <th scope="col" class="py-3" data-sort="string">Ville</th>
                                <th scope="col" class="py-3 text-center" data-sort="number">Grade Fidélité</th>
                                <th scope="col" class="py-3 text-center" data-sort="number">Points Actifs</th>
                                <th scope="col" class="pe-4 py-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($conn) {
                                $clients = GetAllClients($conn);

                                if (!empty($clients) && is_array($clients)) {
                                    foreach ($clients as $client) {
                                        $cliNum = htmlspecialchars($client['CLI_NUM']);
                                        $cliNom = htmlspecialchars($client['CLI_NOM'] ?? '');
                                        $cliPrenom = htmlspecialchars($client['CLI_PRENOM'] ?? '');
                                        $cliMail = htmlspecialchars($client['CLI_COURRIEL'] ?? '—');
                                        $cliVille = htmlspecialchars($client['CLI_VILLE'] ?? '—');
                                        $cliPoints = (int)($client['CLI_NB_POINTS_EC'] ?? 0);
                                        $typNum = isset($client['TYP_NUM']) ? (int)$client['TYP_NUM'] : null;
                                        $cliDateConnec = $client['CLI_DATE_CONNEC'] ?? '';

                                        $isAnonyme = ($cliNum == 0);
                                        $statusType = 'active'; 

                                        if ($isAnonyme) {
                                            $statusBadge = '<span class="badge bg-light text-secondary border">Anonyme</span>';
                                        } else {
                                            if ($typNum === 5) {
                                                $statusBadge = '<span class="badge text-white" style="background-color: #ffd700;">Or</span>';
                                            } elseif ($typNum === 4) {
                                                $statusBadge = '<span class="badge bg-secondary-subtle text-secondary border border-secondary">Argent</span>';
                                            } elseif ($typNum === 3) {
                                                $statusBadge = '<span class="badge bg-primary text-white">Junior</span>';
                                            } elseif ($typNum === 2) {
                                                $statusBadge = '<span class="badge bg-info text-dark">Poussin</span>';
                                            } elseif ($typNum === 1) {
                                                $statusBadge = '<span class="badge bg-success text-white">Nouveau (95%)</span>';
                                            } else {
                                                $statusBadge = '<span class="badge bg-dark text-white">Non défini</span>';
                                            }
                                        }

                                        $alerteInactif = '';
                                        if (!$isAnonyme && !empty($cliDateConnec)) {
                                            $dateConnecObj = DateTime::createFromFormat('d/m/y', $cliDateConnec);
                                            if ($dateConnecObj) {
                                                $maintenant = new DateTime(); 
                                                $intervalle = $maintenant->diff($dateConnecObj);
                                                
                                                if ($intervalle->y >= 1) {
                                                    $statusType = 'inactive'; 
                                                }

                                                if ($intervalle->y >= 2) {
                                                    $alerteInactif = ' <span class="badge bg-danger">Compte à supprimer</span>';
                                                } elseif ($intervalle->y >= 1) {
                                                    $alerteInactif = ' <span class="badge bg-warning text-dark">Compte inactif</span>';
                                                }
                                            }
                                        }
                                        ?>
                                        <tr class="<?= $isAnonyme ? 'table-light text-muted' : '' ?>" data-status="<?= $statusType ?>">
                                            <td class="ps-4 fw-bold">#<?= $cliNum ?></td>
                                            
                                            <td>
                                                <span class="fw-semibold text-dark"><?= $cliNom ?></span> 
                                                <?= $cliPrenom ?>
                                                <?= $alerteInactif ?>
                                            </td>
                                            
                                            <td class="font-monospace"><?= $cliMail ?></td>
                                            <td><?= $cliVille ?></td>
                                            
                                            <td class="text-center" data-sort-value="<?= $isAnonyme ? 0 : $typNum ?>"><?= $statusBadge ?></td>
                                            <td class="text-center fw-bold text-success"><?= $cliPoints ?> pts</td>
                                            
                                            <td class="pe-4 text-end">
                                                <?php if ($isAnonyme): ?>
                                                    <a href="admin_reservations.php?cli_num=0" class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-eye"></i> Voir ses réservations
                                                    </a>
                                                <?php else: ?>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="admin_modifier_client.php?cli_num=<?= $cliNum ?>" class="btn btn-outline-primary" title="Modifier les informations">
                                                            Modifier
                                                        </a>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="if(confirm('Confirmez-vous la suppression définitive du compte de <?= $cliPrenom ?> <?= $cliNom ?> ?')) { window.location.href='action_supprimer_client.php?cli_num=<?= $cliNum ?>'; }" 
                                                                title="Supprimer le compte">
                                                            Supprimer
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="7" class="text-center py-4 text-muted">Aucun client trouvé.</td></tr>';
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const table = document.getElementById('clientTable');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        const btnAll = document.getElementById('btn-filter-all');
        const btnActive = document.getElementById('btn-filter-active');
        const btnInactive = document.getElementById('btn-filter-inactive');

        function updateFilterStyles(activeBtn) {
            [btnAll, btnActive, btnInactive].forEach(btn => {
                btn.classList.remove('btn-secondary', 'active');
                btn.classList.add('btn-light');
            });
            activeBtn.classList.remove('btn-light');
            activeBtn.classList.add('btn-secondary', 'active');
        }

        btnAll.addEventListener('click', () => {
            rows.forEach(row => row.style.display = '');
            updateFilterStyles(btnAll);
        });

        btnActive.addEventListener('click', () => {
            rows.forEach(row => {
                row.style.display = (row.dataset.status === 'active') ? '' : 'none';
            });
            updateFilterStyles(btnActive);
        });

        btnInactive.addEventListener('click', () => {
            rows.forEach(row => {
                row.style.display = (row.dataset.status === 'inactive') ? '' : 'none';
            });
            updateFilterStyles(btnInactive);
        });

        table.querySelectorAll('th[data-sort]').forEach(th => {
            let asc = true;
            th.addEventListener('click', () => {
                const colIndex = th.cellIndex;
                const type = th.dataset.sort;

                const sortedRows = rows.sort((a, b) => {
                    let cellA = a.cells[colIndex];
                    let cellB = b.cells[colIndex];

                    let valA = cellA.hasAttribute('data-sort-value') ? cellA.getAttribute('data-sort-value') : cellA.textContent.trim().replace('#', '').replace(' pts', '');
                    let valB = cellB.hasAttribute('data-sort-value') ? cellB.getAttribute('data-sort-value') : cellB.textContent.trim().replace('#', '').replace(' pts', '');

                    if (type === 'number') {
                        return asc ? parseFloat(valA) - parseFloat(valB) : parseFloat(valB) - parseFloat(valA);
                    } else {
                        return asc ? valA.localeCompare(valB) : valB.localeCompare(valA);
                    }
                });

                asc = !asc;
                sortedRows.forEach(row => tbody.appendChild(row));
            });
        });
    });
    </script>
</body>
</html>