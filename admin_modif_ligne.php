<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once("./bdd/env.php");
include_once("./bdd/BddConnexionUtils.php");
include_once("./bdd/BddAdminClientUtils.php");
include_once("./bdd/BddLigneUtils.php");
include_once("./bdd/BddAdminLigneUtils.php");

$conn = OuvrirConnexionPDO($dbOracle, $db_usernameOracle, $db_passwordOracle);

if (!isset($_SESSION['user_id']) || !isUserAdmin($conn, $_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}

$lig_num = isset($_GET['lig_num']) ? trim($_GET['lig_num']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $ln = trim($_POST['lig_num'] ?? '');
    $ok = false;

    switch ($action) {
        case 'modifier_horaire':
            $ok = ModifierHoraire($conn, $ln, $_POST['com_arret'], $_POST['ancienne_heure'], $_POST['nouvelle_heure']);
            break;
        case 'supprimer_horaire':
            $ok = SupprimerHoraire($conn, $ln, $_POST['com_arret'], $_POST['heure']);
            break;
        case 'ajouter_horaire':
            $ok = AjouterHoraire($conn, $ln, $_POST['com_arret'], $_POST['com_suivant'] ?? null, $_POST['heure']);
            break;
        case 'supprimer_arret':
            $ok = SupprimerArret($conn, $ln, $_POST['com_arret']);
            break;
        case 'ajouter_arret':
            $ok = AjouterHoraire($conn, $ln, $_POST['com_arret'], $_POST['com_suivant'] ?: null, $_POST['heure']);
            break;
    }

    header("Location: admin_modif_ligne.php?lig_num=" . urlencode($ln) . "&msg=" . ($ok ? "ok" : "err"));
    exit();
}

$lignes   = ListeLignes($conn);
$noeuds   = $lig_num ? ListeNoeudsLigne($conn, $lig_num) : [];
$communes = $lig_num ? ListeCommunes($conn) : [];

// Grouper par arrêt (filtrer les horaires NULL = terminus)
$arrets = [];
foreach ($noeuds as $n) {
    $code = $n['COM_CODE_INSEE_ARRET'];
    if (!isset($arrets[$code])) {
        $arrets[$code] = [
            'code'     => $code,
            'nom'      => $n['COM_NOM'],
            'suivant'  => $n['COM_CODE_INSEE_SUIVANT'],
            'horaires' => []
        ];
    }
    if ($n['NOE_HEURE_PASSAGE'] !== null) {
        $arrets[$code]['horaires'][] = $n['NOE_HEURE_PASSAGE'];
    }
}
$arrets = array_values($arrets);

$ligne_info = null;
foreach ($lignes as $l) {
    if (trim($l['LIG_NUM']) === $lig_num) { $ligne_info = $l; break; }
}

$succes = isset($_GET['msg']) && $_GET['msg'] === 'ok' ? "Modification enregistrée." : null;
$erreur = isset($_GET['msg']) && $_GET['msg'] === 'err' ? "Une erreur s'est produite." : null;
?>
<!DOCTYPE html>
<html lang="fr">
<?php include_once("./includes/head.php"); ?>

<style>
.arret-card { border: 1px solid #e0e0e0; border-radius: 8px; margin-bottom: 6px; overflow: hidden; }
.arret-header { padding: 12px 16px; cursor: pointer; background: #fff; display: flex; align-items: center; justify-content: space-between; user-select: none; }
.arret-header:hover { background: rgba(210,10,40,0.04); }
.arret-body { padding: 14px 16px; background: #f8f9fa; border-top: 1px solid #e0e0e0; }
.heure-tag { font-family: monospace; background: #fff; border: 1px solid #dee2e6; border-radius: 4px; padding: 2px 8px; font-size: 13px; min-width: 52px; display: inline-block; text-align: center; }
.btn-viking { background-color: rgb(210,10,40); color: white; border: none; }
.btn-viking:hover { background-color: rgb(170,8,32); color: white; }
.table thead th { background: #222; color: #fff; font-size: 13px; }
</style>

<body class="bg-light">
<?php include_once("./includes/topbar.php"); ?>

<main class="container mt-4 mb-5">

    <h2 class="fw-bold mb-1">Modifier les lignes</h2>
    <p class="text-muted mb-4 small">Espace administrateur</p>

    <?php if ($succes): ?>
    <div class="alert alert-success alert-dismissible fade show py-2">
        <?= $succes ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($erreur): ?>
    <div class="alert alert-danger alert-dismissible fade show py-2">
        <?= $erreur ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>


    <?php if (!$lig_num): ?>
    <!-- ===== LISTE DES LIGNES ===== -->
    <div class="card border-0 shadow-sm">
        <table class="table table-hover mb-0" id="ligne-table">
            <thead>
                <tr><th>Ligne</th><th>Départ</th><th>Terminus</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($lignes as $i => $l): ?>
                <tr <?= $i >= 10 ? 'class="extra" style="display:none"' : '' ?>>
                    <td class="fw-semibold">Ligne <?= trim($l['LIG_NUM']) ?></td>
                    <td><?= htmlspecialchars($l['COM_NOM_DEBU'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($l['COM_NOM_TERM'] ?? '—') ?></td>
                    <td>
                        <a href="?lig_num=<?= urlencode(trim($l['LIG_NUM'])) ?>"
                           class="btn btn-sm btn-viking">Modifier</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (count($lignes) > 10): ?>
    <div class="text-center mt-2">
        <button class="btn btn-sm btn-outline-secondary"
                data-total="<?= count($lignes) ?>"
                onclick="toggleLignes(this)">
            Voir tout (<?= count($lignes) ?> lignes)
        </button>
    </div>
    <?php endif; ?>


    <?php else: ?>
    <!-- ===== DÉTAIL LIGNE ===== -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="admin_modif_ligne.php" class="btn btn-sm btn-outline-secondary">← Retour</a>
        <div>
            <h4 class="fw-bold mb-0">Ligne <?= htmlspecialchars($lig_num) ?></h4>
            <?php if ($ligne_info): ?>
            <small class="text-muted">
                <?= htmlspecialchars($ligne_info['COM_NOM_DEBU'] ?? '') ?> →
                <?= htmlspecialchars($ligne_info['COM_NOM_TERM'] ?? '') ?>
            </small>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($arrets)): ?>
    <p class="text-muted">Aucun arrêt pour cette ligne.</p>
    <?php else: ?>

    <p class="text-muted small mb-3">
        <?= count($arrets) ?> arrêt(s) — cliquez sur un arrêt pour voir et modifier ses horaires
    </p>

    <?php foreach ($arrets as $i => $arret): ?>
    <?php $cid = 'a' . md5($arret['code']); ?>

    <div class="arret-card">
        <div class="arret-header" data-bs-toggle="collapse" data-bs-target="#<?= $cid ?>">
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted fw-bold" style="min-width:22px"><?= $i+1 ?>.</span>
                <span class="fw-semibold"><?= htmlspecialchars($arret['nom']) ?></span>
                <span class="badge bg-light text-secondary border">
                    <?= count($arret['horaires']) ?> passage<?= count($arret['horaires']) > 1 ? 's' : '' ?>
                </span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <form method="POST" style="display:inline"
                      onsubmit="return confirm('Supprimer l\'arrêt <?= addslashes($arret['nom']) ?> et tous ses horaires ?')">
                    <input type="hidden" name="action" value="supprimer_arret">
                    <input type="hidden" name="lig_num" value="<?= $lig_num ?>">
                    <input type="hidden" name="com_arret" value="<?= $arret['code'] ?>">
                    <button type="submit"
                            class="btn btn-sm btn-outline-danger"
                            style="font-size:12px;padding:2px 8px"
                            onclick="event.stopPropagation()">
                        Supprimer l'arrêt
                    </button>
                </form>
                <i class="bi bi-chevron-down text-muted"></i>
            </div>
        </div>

        <div id="<?= $cid ?>" class="collapse arret-body">

            <?php if (empty($arret['horaires'])): ?>
            <p class="text-muted small mb-2">Aucun horaire pour cet arrêt.</p>
            <?php else: ?>
            <?php foreach ($arret['horaires'] as $h): ?>
            <?php $uid = $arret['code'] . '_' . str_replace(':', '', $h); ?>
            <div class="d-flex align-items-center gap-2 mb-2">

                <!-- Mode lecture -->
                <div id="view-<?= $uid ?>" class="d-flex align-items-center gap-2">
                    <span class="heure-tag"><?= $h ?></span>
                    <button type="button"
                            onclick="editHeure('<?= $uid ?>')"
                            class="btn btn-sm btn-outline-secondary"
                            style="font-size:12px;padding:2px 10px">
                        Modifier
                    </button>
                    <form method="POST" style="display:inline"
                          onsubmit="return confirm('Supprimer le passage de <?= $h ?> ?')">
                        <input type="hidden" name="action" value="supprimer_horaire">
                        <input type="hidden" name="lig_num" value="<?= $lig_num ?>">
                        <input type="hidden" name="com_arret" value="<?= $arret['code'] ?>">
                        <input type="hidden" name="heure" value="<?= $h ?>">
                        <button type="submit"
                                class="btn btn-sm btn-outline-danger"
                                style="font-size:12px;padding:2px 10px">
                            Supprimer
                        </button>
                    </form>
                </div>

                <!-- Mode édition -->
                <form id="edit-<?= $uid ?>"
                      method="POST"
                      class="d-none align-items-center gap-1">
                    <input type="hidden" name="action" value="modifier_horaire">
                    <input type="hidden" name="lig_num" value="<?= $lig_num ?>">
                    <input type="hidden" name="com_arret" value="<?= $arret['code'] ?>">
                    <input type="hidden" name="ancienne_heure" value="<?= $h ?>">
                    <input type="time" name="nouvelle_heure" value="<?= $h ?>"
                           class="form-control form-control-sm" style="width:105px">
                    <button type="submit" class="btn btn-sm btn-success" style="font-size:12px">✓</button>
                    <button type="button"
                            onclick="cancelEdit('<?= $uid ?>')"
                            class="btn btn-sm btn-outline-secondary" style="font-size:12px">✗</button>
                </form>

            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <!-- Ajouter une heure -->
            <button type="button"
                    class="btn btn-sm btn-outline-primary mt-1"
                    onclick="toggleForm('add-h-<?= $arret['code'] ?>')">
                + Ajouter une heure
            </button>
            <div id="add-h-<?= $arret['code'] ?>" class="d-none mt-2">
                <form method="POST" class="d-flex align-items-center gap-2 flex-wrap">
                    <input type="hidden" name="action" value="ajouter_horaire">
                    <input type="hidden" name="lig_num" value="<?= $lig_num ?>">
                    <input type="hidden" name="com_arret" value="<?= $arret['code'] ?>">
                    <input type="hidden" name="com_suivant" value="<?= $arret['suivant'] ?>">
                    <input type="time" name="heure"
                           class="form-control form-control-sm" style="width:110px" required>
                    <button type="submit" class="btn btn-sm btn-viking">Ajouter</button>
                    <button type="button"
                            onclick="toggleForm('add-h-<?= $arret['code'] ?>')"
                            class="btn btn-sm btn-outline-secondary">Annuler</button>
                </form>
            </div>

        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- Ajouter un arrêt -->
    <div class="mt-3">
        <button type="button" class="btn btn-viking"
                onclick="toggleForm('form-add-arret')">
            + Ajouter un arrêt
        </button>
    </div>

    <div id="form-add-arret" class="d-none card border-0 shadow-sm p-4 mt-3">
        <h6 class="fw-bold mb-3">Nouvel arrêt</h6>
        <form method="POST">
            <input type="hidden" name="action" value="ajouter_arret">
            <input type="hidden" name="lig_num" value="<?= $lig_num ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Arrêt <span class="text-danger">*</span></label>
                    <select name="com_arret" class="form-select form-select-sm" required>
                        <option value="">-- Choisir --</option>
                        <?php foreach ($communes as $c): ?>
                        <option value="<?= $c['COM_CODE_INSEE'] ?>">
                            <?= htmlspecialchars($c['COM_NOM']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Arrêt suivant</label>
                    <select name="com_suivant" class="form-select form-select-sm">
                        <option value="">-- Terminus --</option>
                        <?php foreach ($communes as $c): ?>
                        <option value="<?= $c['COM_CODE_INSEE'] ?>">
                            <?= htmlspecialchars($c['COM_NOM']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Heure <span class="text-danger">*</span></label>
                    <input type="time" name="heure" class="form-control form-control-sm" required>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-viking">Ajouter</button>
                    <button type="button"
                            onclick="toggleForm('form-add-arret')"
                            class="btn btn-sm btn-outline-secondary">Annuler</button>
                </div>
            </div>
        </form>
    </div>

    <?php endif; ?>

</main>

<?php include_once("./includes/footer.php"); ?>
<?php include_once("./includes/jsIncludes.php"); ?>

<script>
function toggleLignes(btn) {
    const rows = document.querySelectorAll('#ligne-table tr.extra');
    const visible = rows[0] && rows[0].style.display !== 'none';
    rows.forEach(r => r.style.display = visible ? 'none' : '');
    btn.textContent = visible
        ? 'Voir tout (' + btn.dataset.total + ' lignes)'
        : 'Réduire';
}

function editHeure(id) {
    document.getElementById('view-' + id).classList.add('d-none');
    const form = document.getElementById('edit-' + id);
    form.classList.remove('d-none');
    form.classList.add('d-flex');
}

function cancelEdit(id) {
    document.getElementById('view-' + id).classList.remove('d-none');
    const form = document.getElementById('edit-' + id);
    form.classList.add('d-none');
    form.classList.remove('d-flex');
}

function toggleForm(id) {
    document.getElementById(id).classList.toggle('d-none');
}
</script>
</body>
</html>