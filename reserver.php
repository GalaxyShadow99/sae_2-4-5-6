<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$estConnecte = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="fr">

<?php include_once("./includes/head.php"); ?>
<?php include_once("./bdd/BddLigneUtils.php"); ?>

<body class="bg-light">
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

        // --- RECUPERATION DES INFOS CLIENT ---
        $infoClient = [];
        if ($estConnecte) {
            $sqlClient = "SELECT cli_nom, cli_courriel, cli_telephone, cli_nb_points_ec FROM vik_client WHERE cli_num = :id";
            $stmtClient = $conn->prepare($sqlClient);
            $stmtClient->execute(['id' => $_SESSION['user_id']]);
            $infoClient = $stmtClient->fetch(PDO::FETCH_ASSOC) ?: [];
        }

        function formaterTelephone(string $tel): string {
            $chiffres = preg_replace('/\D/', '', $tel);
            if (strlen($chiffres) !== 10) return '';
            return implode('.', str_split($chiffres, 2));
        }

        function getNomCommune($codeInsee, $communes) {
            foreach ($communes as $c) {
                if (trim($c['COM_CODE_INSEE_DEPART']) === trim($codeInsee)) return $c['COM_NOM_DEPART'];
                if (trim($c['COM_CODE_INSEE_ARRIVEE']) === trim($codeInsee)) return $c['COM_NOM_ARRIVEE'];
            }
            return $codeInsee;
        }

        // --- TRAITEMENT DU FORMULAIRE EN 2 ETAPES ---
        $message = '';
        $messageType = '';
        $tarifsCalcules = [];
        $reservationReussie = false;
        $afficherRecap = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // ETAPE 2 : INSERTION DANS LA BASE (Bouton Valider cliqué)
            if (isset($_POST['bouton_valider'])) {
                $nom = $_SESSION['resa_temp']['nom'];
                $prenom = $_SESSION['resa_temp']['prenom'];
                $email = $_SESSION['resa_temp']['email'];
                $numLignes = $_SESSION['resa_temp']['Num_Ligne'];
                $comDeparts = $_SESSION['resa_temp']['Com_depart'];
                $comArrivees = $_SESSION['resa_temp']['Com_arrivee'];
                $tarifsCalcules = $_SESSION['tarifs_temp'];
                $prixTotal = $_SESSION['prix_total_temp'];

                $distanceTotale = $_SESSION['distance_totale_temp'] ?? 0;
                $pointsGagnesTotal = floor($distanceTotale / 10);

                // --- GESTION DE LA REDUCTION (SYSTÈME DE PALIERS) ---
                $pointsDispos = $estConnecte ? (int)($infoClient['CLI_NB_POINTS_EC'] ?? 0) : 0;
                $utiliserPoints = isset($_POST['utiliser_points']) && $pointsDispos >= 100;
                
                $pointsUtilisesTotaux = 0;
                $reductionTotale = 0;

                if ($utiliserPoints) {
                    // Calcul de la valeur maximale que le client peut tirer de ses points
                    $max_reduction = floor($pointsDispos / 1000) * 15 + floor(($pointsDispos % 1000) / 500) * 7 + floor(($pointsDispos % 500) / 100) * 1;
                    
                    if ($prixTotal >= $max_reduction) {
                        $reductionTotale = $max_reduction;
                        $pointsUtilisesTotaux = floor($pointsDispos / 100) * 100; 
                    } else {
                        // Optimisation : On cherche la dépense de points MINIMALE pour un prix à 0€
                        $meilleur_points = $pointsDispos;
                        $max_1000 = floor($pointsDispos / 1000);
                        
                        for ($i = 0; $i <= $max_1000; $i++) {
                            $max_500 = floor(($pointsDispos - $i * 1000) / 500);
                            for ($j = 0; $j <= $max_500; $j++) {
                                $max_100 = floor(($pointsDispos - $i * 1000 - $j * 500) / 100);
                                for ($k = 0; $k <= $max_100; $k++) {
                                    $reduc_potentielle = $i * 15 + $j * 7 + $k * 1;
                                    $cout_points = $i * 1000 + $j * 500 + $k * 100;
                                    
                                    if ($reduc_potentielle >= $prixTotal && $cout_points < $meilleur_points) {
                                        $meilleur_points = $cout_points;
                                    }
                                }
                            }
                        }
                        $reductionTotale = $prixTotal; 
                        $pointsUtilisesTotaux = $meilleur_points; 
                    }
                }

                try {
                    $conn->beginTransaction();
                    foreach ($numLignes as $i => $ligne) {
                        $dep = trim($comDeparts[$i]);
                        $arr = trim($comArrivees[$i]);
                        $tarNum = $tarifsCalcules[$i]['TAR_NUM_TRANCHE'] ?? null;
                        $prix = $tarifsCalcules[$i]['PRIX'] ?? 0;

                        // Application de la réduction sur le premier segment
                        if ($i === 0 && $reductionTotale > 0) {
                            $prix = max(0, $prix - $reductionTotale);
                        }

                        $pointsPourCeSegment = ($i === 0) ? $pointsGagnesTotal : 0;
                        $pointsDepensesCeSegment = ($i === 0) ? $pointsUtilisesTotaux : 0;
                        
                        if($estConnecte) {
                            $ok = reserverAvecCompte($conn, $_SESSION['user_id'], $tarNum, $prix, $pointsPourCeSegment, $pointsDepensesCeSegment);
                        } else {
                            $ok = reserverSansCompte($conn, $nom, $prenom, $email, trim($ligne), $dep, $arr, $tarNum, $prix);
                        }
                        if (!$ok) throw new Exception('Échec insertion segment ' . ($i + 1));
                    }
                    $conn->commit();
                    $message = 'Votre réservation a bien été enregistrée !';
                    $messageType = 'success';
                    $reservationReussie = true;
                    unset($_SESSION['resa_temp'], $_SESSION['tarifs_temp'], $_SESSION['prix_total_temp'], $_SESSION['distance_totale_temp']); 
                } catch (Exception $e) {
                    $conn->rollBack();
                    $message = 'Erreur : ' . $e->getMessage();
                    $messageType = 'danger';
                }
            } 

            // ETAPE 1 : VERIFICATION DES CHAMPS (Bouton Réserver cliqué)
            else {
                $nom = trim($_POST['nom'] ?? '');
                $prenom = trim($_POST['prenom'] ?? '');
                $telephone = formaterTelephone(trim($_POST['telephone'] ?? ''));
                $email = trim($_POST['email'] ?? '');

                $numLignes = $_POST['Num_Ligne'] ?? [];
                $comDeparts = $_POST['Com_depart'] ?? [];
                $comArrivees = $_POST['Com_arrivee'] ?? [];

                $erreurs = [];

                if (empty($nom)) $erreurs[] = 'Le nom est obligatoire.';
                if (empty($prenom)) $erreurs[] = 'Le prénom est obligatoire.';
                if (empty($telephone)) $erreurs[] = 'Le numéro de téléphone est invalide (10 chiffres requis).';
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = 'L\'adresse email est invalide.';
                if (empty($numLignes)) $erreurs[] = 'Veuillez ajouter au moins une ligne.';

                foreach ($numLignes as $i => $ligne) {
                    $dep = trim($comDeparts[$i] ?? '');
                    $arr = trim($comArrivees[$i] ?? '');
                    if (empty(trim($ligne)) || empty($dep) || empty($arr)) {
                        $erreurs[] = 'Segment ' . ($i + 1) . ' : ligne, départ et arrivée sont obligatoires.';
                    } elseif ($dep === $arr) {
                        $erreurs[] = 'Segment ' . ($i + 1) . ' : le départ et l\'arrivée ne peuvent pas être identiques.';
                    }
                }

                $prixTotal = 0;
                $distanceTotale = 0;
                if (empty($erreurs)) {
                    foreach ($numLignes as $i => $ligne) {
                        $dep = trim($comDeparts[$i]);
                        $arr = trim($comArrivees[$i]);
                        $tarif = GetTarifSegment($conn, trim($ligne), $dep, $arr);
                        if ($tarif !== false) {
                            $tarifsCalcules[$i] = $tarif;
                            $prixTotal += $tarif['PRIX'];
                            $distanceTotale += $tarif['DISTANCE'] ?? 0;
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
                    $afficherRecap = true;
                    $_SESSION['resa_temp'] = $_POST;
                    $_SESSION['tarifs_temp'] = $tarifsCalcules;
                    $_SESSION['prix_total_temp'] = $prixTotal;
                    $_SESSION['distance_totale_temp'] = $distanceTotale;
                }
            }
        }
        ?>

        <?php if ($message): ?>
            <div class="alert alert-<?= htmlspecialchars($messageType) ?> mt-3" role="alert">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($reservationReussie): ?>
            <div class="row justify-content-center mt-4">
                <div class="col-md-8">
                    <div class="card shadow-sm border-success">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-check-circle"></i> Billet confirmé</h5>
                        </div>
                        <div class="card-body text-center">
                            <h3 class="text-success mb-3">Merci pour votre réservation !</h3>
                            <a href="reserver.php" class="btn btn-outline-success me-2">Nouvelle réservation</a>
                            <a href="index.php" class="btn btn-secondary">Retour à l'accueil</a>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($afficherRecap): ?>
            <div class="row justify-content-center mt-4">
                <div class="col-md-8">
                    <div class="card shadow-sm border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Vérifiez votre réservation</h5>
                        </div>
                        <div class="card-body">
                            <p class="fs-5 mb-0"><?= htmlspecialchars($_SESSION['resa_temp']['prenom'] . ' ' . $_SESSION['resa_temp']['nom']) ?></p>
                            <div class="mt-2 small">
                                <?php if ($estConnecte): ?>
                                    <span class="text-muted">Vos points disponibles :</span>
                                    <strong><?= (int) ($infoClient['CLI_NB_POINTS_EC'] ?? 0) ?> pts</strong><br>
                                    <span class="text-success"><i class="bi bi-plus-circle-fill"></i> Points gagnés avec ce voyage :</span>
                                    <strong>+<?= floor(($_SESSION['distance_totale_temp'] ?? 0) / 10) ?> pts</strong>
                                <?php else: ?>
                                    <span class="text-muted"><i class="bi bi-info-circle"></i> Créez un compte ou connectez-vous pour cumuler des points de fidélité (1 pt / 10 km).</span>
                                <?php endif; ?>
                            </div>
                        
                            <hr>
                            <ul class="list-group mb-4">
                                <?php foreach ($_SESSION['resa_temp']['Num_Ligne'] as $i => $ligne): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-secondary mb-1">Ligne <?= htmlspecialchars(trim($ligne)) ?></span><br>
                                            <strong>Départ :</strong> <?= htmlspecialchars(getNomCommune(trim($_SESSION['resa_temp']['Com_depart'][$i]), $communes)) ?><br>
                                            <strong>Arrivée :</strong> <?= htmlspecialchars(getNomCommune(trim($_SESSION['resa_temp']['Com_arrivee'][$i]), $communes)) ?>
                                        </div>
                                        <span class="fs-5 fw-bold text-primary">
                                            <?= isset($_SESSION['tarifs_temp'][$i]['PRIX']) ? htmlspecialchars((string)$_SESSION['tarifs_temp'][$i]['PRIX']) . ' €' : 'N/A' ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded border">
                                <h5 class="mb-0 text-muted">TOTAL À PAYER</h5>
                                <h3 class="mb-0 text-warning fw-bold" id="affichagePrixTotal"><?= htmlspecialchars((string)$_SESSION['prix_total_temp']) ?> €</h3>
                            </div>
                            
                            <form method="post" class="mt-4">
                                <?php if ($estConnecte): ?>
                                    <div class="form-check form-switch mb-3 p-3 bg-light rounded border text-start d-flex align-items-center">
                                        <input class="form-check-input me-3 ms-0" type="checkbox" id="utiliserPoints" name="utiliser_points" value="1">
                                        <label class="form-check-label fw-bold text-dark mb-0" for="utiliserPoints">
                                            <i class="bi bi-gift-fill text-warning me-1"></i> Utiliser mes points fidélité (par paliers) pour réduire le prix
                                        </label>
                                    </div>
                                <?php endif; ?>
                                
                                <button type="submit" name="bouton_valider" class="btn btn-success btn-lg w-100 mb-2">Valider la réservation</button>
                                <a href="reserver.php" class="btn btn-outline-secondary w-100">Modifier les informations</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="row justify-content-center mt-4">
                <div class="col-md-7">
                    <form method="post" id="formReservation">

                        <div class="card mb-4">
                            <div class="card-header fw-bold bg-secondary text-white ">Vos informations</div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="nom" class="form-label">Nom *</label>
                                    <input type="text" class="form-control" id="nom" name="nom"
                                        value="<?= htmlspecialchars($_POST['nom'] ?? $infoClient['CLI_NOM'] ?? '') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="prenom" class="form-label">Prénom *</label>
                                    <input type="text" class="form-control" id="prenom" name="prenom"
                                        value="<?= htmlspecialchars($_POST['prenom'] ?? $_SESSION['user_prenom'] ?? '') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="telephone" class="form-label">Téléphone *</label>
                                    <input type="text" class="form-control" id="telephone" name="telephone"
                                        value="<?= htmlspecialchars($_POST['telephone'] ?? $infoClient['CLI_TELEPHONE'] ?? '') ?>"
                                        placeholder="0612345678" maxlength="14" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="<?= htmlspecialchars($_POST['email'] ?? $infoClient['CLI_COURRIEL'] ?? '') ?>" required>
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
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-supprimer">Supprimer</button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Ligne *</label>
                                            <select class="form-select select-ligne" name="Num_Ligne[]" required data-selected="<?= $sLigne ?>">
                                                <option value="" disabled <?= $sLigne === '' ? 'selected' : '' ?>>-- Choisir une ligne --</option>
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
                                            <label class="form-label">Départ *</label>
                                           <select class="form-select select-depart" name="Com_depart[]" required data-selected="<?= $sDep ?>">
                                                <option value="" disabled selected>-- Choisir d'abord une ligne --</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Arrivée *</label>
                                            <select class="form-select select-arrivee" name="Com_arrivee[]" required data-selected="<?= $sArr ?>">
                                                <option value="" disabled selected>-- Choisir d'abord une ligne --</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-secondary w-100" id="btn-ajouter-ligne">
                                + Ajouter une ligne
                            </button>
                        </div>

                        <p><small>* Champs obligatoires</small></p>
                        <button type="submit" name="bouton_reserver" class=" text-white btn w-100 mb-5" style="background-color: rgb(210, 10, 40);">Réserver</button>

                    </form>
                </div>
            </div>

            <script>
                const communes = <?= json_encode($communes) ?>;

                function optionsUniques(arrets, codeKey, nomKey) {
                    const dejaVus = new Set();
                    return arrets.reduce((liste, arret) => {
                        const code = (arret[codeKey] || '').trim();
                        if (!code || dejaVus.has(code)) return liste;
                        dejaVus.add(code);
                        liste.push({ code, label: (arret[nomKey] || code).trim() });
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
                                <label class="form-label">Départ *</label>
                                <select class="form-select select-depart" name="Com_depart[]" required>
                                    <option value="" disabled selected>-- Choisir d'abord une ligne --</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Arrivée *</label>
                                <select class="form-select select-arrivee" name="Com_arrivee[]" required>
                                    <option value="" disabled selected>-- Choisir d'abord une ligne --</option>
                                </select>
                            </div>
                        </div>`;

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
        <?php endif; ?>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const switchPoints = document.getElementById('utiliserPoints');
                
                if (switchPoints) {
                    const prixElement = document.getElementById('affichagePrixTotal');
                    const prixInitial = parseFloat(prixElement.textContent);
                    const pointsDispos = <?= isset($infoClient['CLI_NB_POINTS_EC']) ? (int)$infoClient['CLI_NB_POINTS_EC'] : 0 ?>;

                    switchPoints.addEventListener('change', function() {
                        if (this.checked) {
                            let max_reduction = Math.floor(pointsDispos / 1000) * 15 + 
                                                Math.floor((pointsDispos % 1000) / 500) * 7 + 
                                                Math.floor((pointsDispos % 500) / 100) * 1;
                            
                            let remiseReelle = Math.min(max_reduction, prixInitial);
                            let nouveauPrix = Math.max(0, prixInitial - remiseReelle);
                            
                            prixElement.innerHTML = nouveauPrix.toFixed(2) + ' € <br>' + 
                                '<span class="badge bg-success fs-6 mt-2 shadow-sm">- ' + remiseReelle.toFixed(2) + ' € (Fidélité)</span>';
                        } else {
                            prixElement.innerHTML = prixInitial.toFixed(2) + ' €';
                        }
                    });
                }
            });
        </script>

    </main>

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>

    <?php
    // Fermeture de la connexion BDD
    if (isset($conn)) {
        $conn = null;
    }
    ?>
</body>

</html>