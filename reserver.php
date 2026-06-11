<!DOCTYPE html>
<html lang="fr">

<?php include_once("./includes/head.php"); ?>
<?php include_once("./bdd/BddLigneUtils.php"); ?>

<body>
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container mt-4">
        <h1 class="text-center">Réserver un voyage</h1>

        <?php
        require_once './bdd/env.php';
        require_once './bdd/reserverutils.php';
        define('MOD_BDD', 'ORACLE');
        $conn = OuvrirConnexionPDO($dbOracle, $db_usernameOracle, $db_passwordOracle);
        $lignes = ListeLignes($conn);
        $communes = ListeCommunesLignes($conn);

        function formaterTelephone(string $tel): string
        {
            $chiffres = preg_replace('/\D/', '', $tel);
            if (strlen($chiffres) !== 10)
                return '';
            return implode('.', str_split($chiffres, 2));
        }

        // --- TRAITEMENT DU FORMULAIRE (POST) ---
        $message = '';
        $messageType = '';
        $tarifsCalcules = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $telephone = formaterTelephone(trim($_POST['telephone'] ?? ''));
            $email = trim($_POST['email'] ?? '');

            // Récupération des segments (tableaux)
            $numLignes = $_POST['Num_Ligne'] ?? [];
            $comDeparts = $_POST['Com_depart'] ?? [];
            $comArrivees = $_POST['Com_arrivee'] ?? [];

            $erreurs = [];

            if (empty($nom))
                $erreurs[] = 'Le nom est obligatoire.';
            if (empty($prenom))
                $erreurs[] = 'Le prénom est obligatoire.';
            if (empty($telephone))
                $erreurs[] = 'Le numéro de téléphone est invalide (10 chiffres requis).';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL))
                $erreurs[] = 'L\'adresse email est invalide.';
            if (empty($numLignes))
                $erreurs[] = 'Veuillez ajouter au moins une ligne.';

            foreach ($numLignes as $i => $ligne) {
                $dep = trim($comDeparts[$i] ?? '');
                $arr = trim($comArrivees[$i] ?? '');
                if (empty(trim($ligne)) || empty($dep) || empty($arr)) {
                    $erreurs[] = 'Segment ' . ($i + 1) . ' : ligne, départ et arrivée sont obligatoires.';
                } elseif ($dep === $arr) {
                    $erreurs[] = 'Segment ' . ($i + 1) . ' : le départ et l\'arrivée ne peuvent pas être identiques.';
                }
            }

            if (!empty($erreurs)) {
                // Échapper chaque message individuellement avant de les joindre
                $message = implode('<br>', array_map('htmlspecialchars', $erreurs));
                $messageType = 'danger';
            } else {
                $prixTotal = 0;
                foreach ($numLignes as $i => $ligne) {
                    $dep = trim($comDeparts[$i]);
                    $arr = trim($comArrivees[$i]);
                    $tarif = GetTarifSegment($conn, trim($ligne), $dep, $arr);
                    if ($tarif !== false) {
                        $tarifsCalcules[$i] = $tarif;
                        $prixTotal += $tarif['PRIX'];
                    } else {
                        $tarifsCalcules[$i] = null;
                        $erreurs[] = 'Segment ' . ($i + 1) . ' : aucun tarif trouvé pour ce trajet.';
                    }
                }
            }

            if (!empty($erreurs)) {
                $message = implode('<br>', array_map('htmlspecialchars', $erreurs));
                $messageType = 'danger';
            } else {

                try {
                    $conn->beginTransaction();
                    foreach ($numLignes as $i => $ligne) {
                        $dep = trim($comDeparts[$i]);
                        $arr = trim($comArrivees[$i]);
                        $tarNum = $tarifsCalcules[$i]['TAR_NUM_TRANCHE'] ?? null;
                        $prix = $tarifsCalcules[$i]['PRIX'] ?? null;
                        $ok = reserverSansCompte($conn, $nom, $prenom, $email, trim($ligne), $dep, $arr, $tarNum, $prix);
                        if (!$ok)
                            throw new Exception('Échec insertion segment ' . ($i + 1));
                    }
                    $conn->commit();
                    $prixStr = $prixTotal > 0 ? ' Prix total : <strong>' . htmlspecialchars((string) $prixTotal) . ' €</strong>' : '';
                    $message = 'Votre réservation a bien été enregistrée !' . $prixStr;
                    $messageType = 'success';
                } catch (Exception $e) {
                    $conn->rollBack();
                    $message = 'Erreur : ' . $e->getMessage();
                    $messageType = 'danger';
                }
            }
        }

        $conn = null;
        ?>

        <?php if ($message): ?>
            <div class="alert alert-<?= htmlspecialchars($messageType) ?> mt-3" role="alert">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center mt-4">
            <div class="col-md-7">
                <form method="post" id="formReservation">

                    <div class="card mb-4">
                        <div class="card-header fw-bold">Vos informations</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="nom" class="form-label">Nom *</label>
                                <input type="text" class="form-control" id="nom" name="nom"
                                    value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="prenom" class="form-label">Prénom *</label>
                                <input type="text" class="form-control" id="prenom" name="prenom"
                                    value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="telephone" class="form-label">Téléphone *</label>
                                <input type="text" class="form-control" id="telephone" name="telephone"
                                    value="<?= htmlspecialchars(formaterTelephone($_POST['telephone'] ?? '')) ?>"
                                    placeholder="0612345678" maxlength="14" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            </div>
                        </div>
                    </div>

                    <div id="segments-container">
                        <?php
                        $postLignes = $_POST['Num_Ligne'] ?? [''];
                        $postDeparts = $_POST['Com_depart'] ?? [''];
                        $postArrivees = $_POST['Com_arrivee'] ?? [''];
                        foreach ($postLignes as $si => $sLigne):
                            $sLigne = htmlspecialchars(trim($sLigne));
                            $sDep = htmlspecialchars($postDeparts[$si] ?? '');
                            $sArr = htmlspecialchars($postArrivees[$si] ?? '');
                            $sNum = $si + 1;
                            ?>
                            <div class="card mb-3 segment-bloc" id="segment-<?= $si ?>">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Trajet <?= $sNum ?></span>
                                    <?php if ($si > 0): ?>
                                        <button type="button"
                                            class="btn btn-sm btn-outline-danger btn-supprimer">Supprimer</button>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Ligne *</label>
                                        <select class="form-select select-ligne" name="Num_Ligne[]" required
                                            data-selected="<?= $sLigne ?>">
                                            <option value="" disabled <?= $sLigne === '' ? 'selected' : '' ?>>-- Choisir une
                                                ligne --</option>
                                            <?php foreach ($lignes as $Ligne):
                                                $val = trim($Ligne['LIG_NUM']);
                                                $departNom = $Ligne['COM_NOM_DEBU'] ?? $Ligne['COM_CODE_INSEE_DEBU'];
                                                $termNom = $Ligne['COM_NOM_TERM'] ?? $Ligne['COM_CODE_INSEE_TERM'];
                                                $label = 'Ligne ' . $val . ' (' . $departNom . ' → ' . $termNom . ')';
                                                ?>
                                                <option value="<?= htmlspecialchars($val) ?>" <?= ($sLigne === $val) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Arrêt de départ *</label>
                                        <select class="form-select select-depart" name="Com_depart[]" required
                                            data-selected="<?= $sDep ?>">
                                            <option value="" disabled selected>-- Choisir d'abord une ligne --</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Arrêt d'arrivée *</label>
                                        <select class="form-select select-arrivee" name="Com_arrivee[]" required
                                            data-selected="<?= $sArr ?>">
                                            <option value="" disabled selected>-- Choisir d'abord une ligne --</option>
                                        </select>
                                    </div>
                                    <!-- US6 : affichage du tarif calculé pour ce segment -->
                                    <?php if (!empty($tarifsCalcules[$si])): ?>
                                        <div class="alert alert-info py-2">
                                            Tarif estimé pour ce trajet :
                                            <strong><?= htmlspecialchars((string) $tarifsCalcules[$si]['PRIX']) ?> €</strong>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Bouton US5 : ajouter une ligne -->
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-secondary w-100" id="btn-ajouter-ligne">
                            + Ajouter une ligne
                        </button>
                    </div>

                    <p><small>* Champs obligatoires</small></p>
                    <button type="submit" class="btn btn-primary w-100">Réserver</button>

                </form>
            </div>
        </div>

        <script>
            const communes = <?= json_encode($communes) ?>;

            function optionsUniques(arrets, codeKey, nomKey) {
                const dejaVus = new Set();

                return arrets.reduce((liste, arret) => {
                    const code = (arret[codeKey] || '').trim();
                    if (!code || dejaVus.has(code)) {
                        return liste;
                    }

                    dejaVus.add(code);
                    liste.push({
                        code,
                        label: (arret[nomKey] || code).trim(),
                    });
                    return liste;
                }, []);
            }

            function remplirArrets(selectLigne, selectDepart, selectArrivee, valDepart = '', valArrivee = '') {
                const ligNum = selectLigne.value.trim();

                selectDepart.innerHTML = '<option value="" disabled selected>-- Choisir un arrêt --</option>';
                selectArrivee.innerHTML = '<option value="" disabled selected>-- Choisir un arrêt --</option>';

                const arretsLigne = communes.filter(c => (c['LIG_NUM'] || '').trim() === ligNum);
                const departs = optionsUniques(arretsLigne, 'COM_CODE_INSEE_DEPART', 'COM_NOM_DEPART');
                const arrivees = optionsUniques(arretsLigne, 'COM_CODE_INSEE_ARRIVEE', 'COM_NOM_ARRIVEE');

                if (departs.length === 0 && arrivees.length === 0) {
                    selectDepart.innerHTML = '<option value="" disabled selected>Aucun arrêt trouvé</option>';
                    selectArrivee.innerHTML = '<option value="" disabled selected>Aucun arrêt trouvé</option>';
                    return;
                }

                departs.forEach(({ code, label }) => {
                    const option = document.createElement('option');
                    option.value = code;
                    option.textContent = label;
                    if (code === valDepart) option.selected = true;
                    selectDepart.appendChild(option);
                });

                arrivees.forEach(({ code, label }) => {
                    const option = document.createElement('option');
                    option.value = code;
                    option.textContent = label;
                    if (code === valArrivee) option.selected = true;
                    selectArrivee.appendChild(option);
                });
            }

            function initBloc(bloc) {
                const selLigne = bloc.querySelector('.select-ligne');
                const selDepart = bloc.querySelector('.select-depart');
                const selArrivee = bloc.querySelector('.select-arrivee');
                const btnSuppr = bloc.querySelector('.btn-supprimer');

                if (selLigne.value) {
                    const savedDep = selDepart.dataset.selected || '';
                    const savedArr = selArrivee.dataset.selected || '';
                    remplirArrets(selLigne, selDepart, selArrivee, savedDep, savedArr);
                }

                selLigne.addEventListener('change', function () {
                    remplirArrets(this, selDepart, selArrivee);
                });

                if (btnSuppr) {
                    btnSuppr.addEventListener('click', function () {
                        bloc.remove();
                        renuméroterSegments();
                    });
                }
            }

            function renuméroterSegments() {
                document.querySelectorAll('.segment-bloc').forEach((bloc, i) => {
                    const titre = bloc.querySelector('.card-header span');
                    if (titre) titre.textContent = 'Trajet ' + (i + 1);
                });
            }

            document.querySelectorAll('.segment-bloc').forEach(initBloc);

            let segmentCount = <?= count($postLignes) ?>;

            document.getElementById('btn-ajouter-ligne').addEventListener('click', function () {
                const container = document.getElementById('segments-container');
                const newIndex = segmentCount++;

                const div = document.createElement('div');
                div.className = 'card mb-3 segment-bloc';
                div.id = 'segment-' + newIndex;
                div.innerHTML = `
<div class="card-header d-flex justify-content-between align-items-center">
<span class="fw-bold">Trajet ${newIndex + 1}</span>
<button type="button" class="btn btn-sm btn-outline-danger btn-supprimer">Supprimer</button>
</div>
<div class="card-body">
<div class="mb-3">
<label class="form-label">Ligne *</label>
<select class="form-select select-ligne" name="Num_Ligne[]" required>
<option value="" disabled selected>-- Choisir une ligne --</option>
${buildLigneOptions()}
</select>
</div>
<div class="mb-3">
<label class="form-label">Arrêt de départ *</label>
<select class="form-select select-depart" name="Com_depart[]" required>
<option value="" disabled selected>-- Choisir d'abord une ligne --</option>
</select>
</div>
<div class="mb-3">
<label class="form-label">Arrêt d'arrivée *</label>
<select class="form-select select-arrivee" name="Com_arrivee[]" required>
<option value="" disabled selected>-- Choisir d'abord une ligne --</option>
</select>
</div>
</div>`

                container.appendChild(div);
                initBloc(div);
                renuméroterSegments();
            });

            const lignesData = <?= json_encode(array_map(fn($l) => [
                'num' => trim($l['LIG_NUM']),
                'debu' => $l['COM_NOM_DEBU'] ?: $l['COM_CODE_INSEE_DEBU'],
                'term' => $l['COM_NOM_TERM'] ?: $l['COM_CODE_INSEE_TERM'],
            ], $lignes)) ?>;

            function buildLigneOptions() {
                return lignesData.map(l =>
                    `<option value="${l.num}">Ligne ${l.num} (${l.debu} → ${l.term})</option>`
                ).join('');
            }
        </script>

    </main>

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>
</body>

</html>