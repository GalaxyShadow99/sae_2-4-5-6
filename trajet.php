<?php 
if (session_status() === PHP_SESSION_NONE) session_start();

include_once("./bdd/env.php");
include_once("./bdd/BddConnexionUtils.php");
include_once("./bdd/BddTrajetUtils.php");
include_once("./bdd/BddLigneUtils.php");

$conn = OuvrirConnexionPDO($dbOracle, $db_usernameOracle, $db_passwordOracle);

$communes = [];
$noms_villes = [];

if ($conn) {
    $sql = "SELECT DISTINCT c.COM_CODE_INSEE, c.COM_NOM 
            FROM vik_commune c
            JOIN vik_noeud n ON c.COM_CODE_INSEE = n.COM_CODE_INSEE_ARRET 
                            OR c.COM_CODE_INSEE = n.COM_CODE_INSEE_SUIVANT
            ORDER BY c.COM_NOM ASC";
    $stmt = $conn->query($sql);
    $communes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($communes as $c) {
        $nom_ville = $c['COM_NOM'];
        $noms_villes[$c['COM_CODE_INSEE']] = $nom_ville;
    }
}

function getGraphePondere($conn, $graphe) {
    $noeuds = ListeNoeuds($conn);

    foreach ($noeuds as $items){
        $arret = $items["COM_CODE_INSEE_ARRET"];
        $suivant = $items["COM_CODE_INSEE_SUIVANT"];
        $distance = (double) str_replace(',', '.', $items["NOE_DISTANCE_PROCHAIN"]);
        $duree = (double) $items["NOE_DUREE_PROCHAIN"];

        if (!isset($graphe[$arret][$suivant]) || $distance < $graphe[$arret][$suivant]["distance"]){
            $graphe[$arret][$suivant] = ["lig_num" => $items["LIG_NUM"], "distance" => $distance, "duree" => $duree];
        }
    }

    return $graphe;
}

function dijkstraDistance($graphe, $depart, $arrivee, $critere, $forbiden = []) {
    $dist = [];
    $prev = [];

    foreach ($graphe as $sommet => $v) {
        $dist[$sommet] = INF;
    }

    $dist[$depart] = 0;
    $file = new SplPriorityQueue();
    $file->setExtractFlags(SplPriorityQueue::EXTR_DATA);
    $file->insert($depart, 0);

    while (!$file->isEmpty()) {
        $courant = $file->extract();

        if ($courant === $arrivee) break;
        if (!isset($graphe[$courant])) continue;

        foreach ($graphe[$courant] as $voisin => $infos) {
            $key = $courant."-".$voisin;

            if (isset($forbiden[$key])) continue;

            $cout = (double) $infos[$critere];
            $new = $dist[$courant] + $cout;

            if ($new < ($dist[$voisin] ?? INF)) {
                $dist[$voisin] = $new;
                $prev[$voisin] = $courant;
                $file->insert($voisin, -$new);
            }
        }
    }

    if (!isset($dist[$arrivee]) || $dist[$arrivee] === INF) return null;

    $chemin = [];
    $courant = $arrivee;

    while (isset($prev[$courant])) {
        array_unshift($chemin, $courant);
        $courant = $prev[$courant];
    }

    array_unshift($chemin, $depart);

    return ["value" => $dist[$arrivee],"chemin" => $chemin];
}

function kShortest($graphe, $depart, $arrivee, $k = 10, $critere) {
    $resultats = [];
    $premier = dijkstraDistance($graphe, $depart, $arrivee, $critere);

    if (!$premier) return [];

    $resultats[] = $premier;
    $possibles = [];

    for ($i = 1; $i < $k; $i++) {
        if (!isset($resultats[$i - 1])) break;
        $last = $resultats[$i - 1]["chemin"];

        for ($j = 0; $j < count($last) - 1; $j++) {
            $u = $last[$j];
            $v = $last[$j + 1];

            $forbiden = ["$u-$v" => true];
            $other = dijkstraDistance($graphe, $depart, $arrivee, $critere, $forbiden);

            if ($other) {
                $dejaVu = false;
                
                foreach ($resultats as $res) {
                    if ($res["chemin"] === $other["chemin"]) {
                        $dejaVu = true;
                        break;
                    }
                }
                
                if (!$dejaVu) {
                    foreach ($possibles as $pos) {
                        if ($pos["chemin"] === $other["chemin"]) {
                            $dejaVu = true;
                            break;
                        }
                    }
                }
                
                if (!$dejaVu) $possibles[] = $other;
            }
        }

        if (empty($possibles)) break;

        usort($possibles, function ($a, $b) {
            if ($a["value"] < $b["value"]) return -1;
            else if ($a["value"] > $b["value"]) return 1;
            else return 0;
        });

        $resultats[] = array_shift($possibles);
    }
    return $resultats;
}

function grouperCheminParLigne($chemin, $graphe) {
    $segments = [];
    $currentLine = null;
    $currentSegment = [];
    
    for ($i = 0; $i < count($chemin) - 1; $i++) {
        $u = $chemin[$i];
        $v = $chemin[$i+1];
        
        if (isset($graphe[$u][$v]['lig_num'])) $line = trim($graphe[$u][$v]['lig_num']);
        else $line = 'Inconnue';

        if ($line !== $currentLine) {
            if ($currentLine !== null) {
                $currentSegment['arrets'][] = $u;
                $segments[] = $currentSegment;
            }
            
            $currentLine = $line;
            $currentSegment = ['ligne' => $line, 'arrets' => [$u]];
        } else $currentSegment['arrets'][] = $u;
    }
    
    if (!empty($currentSegment)) {
        $currentSegment['arrets'][] = end($chemin);
        $segments[] = $currentSegment;
    }
    
    return $segments;
}

function getMetriques($chemin, $graphe) {
    $distance = 0;
    $duree = 0;

    for ($i = 0; $i < count($chemin) - 1; $i++) {
        $u = $chemin[$i];
        $v = $chemin[$i+1];

        if (isset($graphe[$u][$v])) {
            $distance += $graphe[$u][$v]['distance'];
            $duree += $graphe[$u][$v]['duree'];
        }
    }

    return ['distance' => $distance, 'duree' => $duree];
}

function calculerHorairesReels($conn, $chemin, $graphe, $heure_depart_voulue) {
    $heure_courante = $heure_depart_voulue;
    $heure_depart_reelle = null;
    $current_line = null;
    
    for ($i = 0; $i < count($chemin) - 1; $i++) {
        $u = $chemin[$i];
        $v = $chemin[$i+1];

        if (isset($graphe[$u][$v]['lig_num'])) $ligne = trim($graphe[$u][$v]['lig_num']);
        else $ligne = null;
        
        if (!$ligne) return null;

        if ($ligne != $current_line) {
            $res = RecupHoraireSup($conn, $ligne, $u, $heure_courante);

            if (is_array($res)) {
                if (isset($res['HEURE_PASSAGE'])) $horaire_trouve = $res['HEURE_PASSAGE']; // Adapté à votre AS SQL
                elseif (isset($res['HEURE'])) $horaire_trouve = $res['HEURE']; 
                else $horaire_trouve = null;
            } else $horaire_trouve = $res;

            if ($horaire_trouve) {
                $heure_courante = $horaire_trouve;
                if ($i === 0) $heure_depart_reelle = $heure_courante;
            } else return null; 

            $current_line = $ligne;
        }
        
        $duree = (double) $graphe[$u][$v]['duree'];
        $obj = DateTime::createFromFormat('H:i', $heure_courante);

        if ($obj) {
            $obj->modify("+" . round($duree) . " minutes");
            $heure_courante = $obj->format('H:i');
        }
    }
    
    if ($heure_depart_reelle === null) return null;
    
    return ['depart' => $heure_depart_reelle, 'arrivee' => $heure_courante];
}

function formaterDuree($minutes) {
    $min = round($minutes);
    if ($min < 60) return $min . " min";
    $h = floor($min / 60);
    $m = $min % 60;
    if ($m === 0) return $h . " h";

    return $h . " h " . str_pad($m, 2, "0", STR_PAD_LEFT) . " min";
}

$depart  = $_GET['depart'] ?? null;
$arrivee = $_GET['arrivee'] ?? null;
$heure   = $_GET['heure'] ?? date('H:i');

$trajets_distance_affiches = [];
$trajets_duree_affiches = [];

if ($depart && $arrivee && $depart !== $arrivee && $conn) {
    $graphe = getGraphePondere($conn, []);
    $candidats_distance = kShortest($graphe, $depart, $arrivee, 5, "distance");
    $candidats_duree = kShortest($graphe, $depart, $arrivee, 5, "duree");

    foreach ($candidats_distance as $trajet) {
        $horaires = calculerHorairesReels($conn, $trajet['chemin'], $graphe, $heure);

        if ($horaires) {
            $trajet['horaires'] = $horaires;
            $d1 = DateTime::createFromFormat('H:i', $horaires['depart']);
            $d2 = DateTime::createFromFormat('H:i', $horaires['arrivee']);

            if ($d1 && $d2) {
                if ($d2 < $d1) $d2->modify('+1 day');
                $diff = $d1->diff($d2);
                $trajet['duree_reelle'] = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
            } else $trajet['duree_reelle'] = 0;

            $trajets_distance_affiches[] = $trajet;

            if (count($trajets_distance_affiches) >= 10) break;
        }
    }

    foreach ($candidats_duree as $trajet) {
        $horaires = calculerHorairesReels($conn, $trajet['chemin'], $graphe, $heure);

        if ($horaires) {
            $trajet['horaires'] = $horaires;
            $d1 = DateTime::createFromFormat('H:i', $horaires['depart']);
            $d2 = DateTime::createFromFormat('H:i', $horaires['arrivee']);

            if ($d1 && $d2) {
                if ($d2 < $d1) $d2->modify('+1 day');
                $diff = $d1->diff($d2);
                $trajet['duree_reelle'] = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
            } else $trajet['duree_reelle'] = INF;

            $trajets_duree_affiches[] = $trajet;
        }
    }
    
    usort($trajets_duree_affiches, function($a, $b) {
        return $a['duree_reelle'] <=> $b['duree_reelle'];
    });

    $trajets_duree_affiches = array_slice($trajets_duree_affiches, 0, 10);
}

?>


<!DOCTYPE html>
<html lang="fr" class="h-100">
<?php include_once("./includes/head.php"); ?>
<body class="d-flex flex-column h-100 bg-light text-dark">
    <?php include_once("./includes/topbar.php"); ?>
    <main class="container-fluid my-5 flex-shrink-0 px-4">
        <div class="row mb-4 text-center">
            <div class="col-lg-8 mx-auto">
                <div class="mb-3">
					<span class="badge px-3 py-2 rounded-pill fw-semibold text-uppercase tracking-wider" 
						  style="background-color: rgba(210, 10, 40, 0.1); color: rgb(210, 10, 40);">
						Recherche d'Itinéraires
					</span>
				</div>
                <h1 class="display-5 fw-bold text-dark mb-3">Trouvez votre trajet</h1>
                <p class="lead text-secondary">Trouvez facilement les meilleurs chemins pour vos déplacements urbains.</p>
            </div>
        </div>

        <div class="p-4 p-md-5 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10 mb-5" style="max-width:1200px; margin:0 auto;">
            <form method="GET" action="trajet.php" class="row gy-3 align-items-end">
                <div class="col-md-4">
                    <label for="depart" class="form-label fw-semibold text-dark">Départ</label>
                    <select name="depart" id="depart" class="form-select rounded-3" required>
                        <option value="" disabled <?= !$depart ? 'selected' : '' ?>>-- Choisir une ville --</option>
                        <?php foreach ($communes as $c): ?>
                            <option value="<?= htmlspecialchars($c['COM_CODE_INSEE'], ENT_QUOTES, 'UTF-8') ?>" <?= $depart === $c['COM_CODE_INSEE'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($noms_villes[$c['COM_CODE_INSEE']], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="arrivee" class="form-label fw-semibold text-dark">Arrivée</label>
                    <select name="arrivee" id="arrivee" class="form-select rounded-3" required>
                        <option value="" disabled <?= !$arrivee ? 'selected' : '' ?>>-- Choisir une ville --</option>
                        <?php foreach ($communes as $c): ?>
                            <option value="<?= htmlspecialchars($c['COM_CODE_INSEE'], ENT_QUOTES, 'UTF-8') ?>" <?= $arrivee === $c['COM_CODE_INSEE'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($noms_villes[$c['COM_CODE_INSEE']], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="heure" class="form-label fw-semibold text-dark">Heure de départ</label>
                    <input type="time" name="heure" id="heure" class="form-control rounded-3" value="<?= htmlspecialchars($heure, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn px-4 py-2 fw-semibold rounded-3 shadow-sm text-white w-100 hover-scale" 
                       style="background-color: rgb(210, 10, 40); border: 1px solid rgb(210, 10, 40);">
                       <i class="bi bi-search me-1"></i> Rechercher
                    </button>
                </div>
            </form>
        </div>

        <?php if ($depart && $arrivee): ?>
            <?php if ($depart === $arrivee): ?>
                <div class="alert alert-warning rounded-3 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Le départ et l'arrivée doivent être différents.
                </div>
            <?php elseif (empty($trajets_distance_affiches) && empty($trajets_duree_affiches)): ?>
                <div class="alert alert-info rounded-3 shadow-sm text-center" role="alert">
                    <i class="bi bi-info-circle me-2"></i> Aucun itinéraire trouvé après l'heure demandée.
                </div>
            <?php else: ?>
                <div class="row gy-4" style="max-width:1400px; margin:0 auto;">
                    <div class="col-lg-6">
                        <div class="p-4 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10 h-100">
                            <h2 class="fw-bold text-dark h5 mb-4 text-uppercase tracking-wider text-center">
                                <span style="color: rgb(255, 220, 0);">|</span> Les plus courts (Distance)
                            </h2>
                            <?php foreach ($trajets_distance_affiches as $index => $trajet): ?>
                                <?php $metriques = getMetriques($trajet['chemin'], $graphe); ?>
                                <div class="card mb-4 shadow-sm border border-secondary border-opacity-10 rounded-3">
                                    <div class="card-header bg-light rounded-top-3 border-0 pb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0 fw-bold">Option #<?= $index + 1 ?></h6>
                                            <span class="badge fs-7 rounded-pill px-3 py-2" style="background-color: rgb(255, 220, 0); color: #000;"><?= round($metriques['distance'], 2) ?> km</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center small mt-2 px-1 text-dark">
                                            <span><i class="bi bi-clock me-1"></i>Départ : <strong style="color: rgb(255, 180, 0);"><?= $trajet['horaires']['depart'] ?></strong></span>
                                            <i class="bi bi-arrow-right text-muted"></i>
                                            <span><i class="bi bi-flag me-1"></i>Arrivée : <strong><?= $trajet['horaires']['arrivee'] ?></strong></span>
                                            <span class="text-muted">(<?= formaterDuree($trajet['duree_reelle']) ?>)</span>
                                        </div>
                                    </div>
                                    <div class="card-body p-0 pt-3">
                                        <?php 
                                        $segments = grouperCheminParLigne($trajet["chemin"], $graphe);
                                        foreach ($segments as $sIndex => $segment): 
                                        ?>
                                            <div class="px-4 pb-2">
                                                <span class="badge bg-opacity-75 mb-2" style="background-color: rgb(255, 220, 0); color: #000;">
                                                    <i class="bi bi-bus-front"></i> Ligne <?= htmlspecialchars($segment['ligne'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                                <ul class="list-unstyled ms-2 mb-0 border-start border-2 border-secondary border-opacity-25 ps-3 pb-2 position-relative">
                                                    <?php foreach ($segment['arrets'] as $aIndex => $insee): ?>
                                                        <?php $isMainNode = ($aIndex === 0 || $aIndex === count($segment['arrets']) - 1); ?>
                                                        <li class="mb-1 position-relative">
                                                            <span class="position-absolute translate-middle-x" style="left: -17.5px; top: 4px;">
                                                                <i class="bi <?= $isMainNode ? 'bi-circle-fill text-dark' : 'bi-circle text-secondary' ?>" style="font-size: 0.55rem;"></i>
                                                            </span>
                                                            <span class="<?= $isMainNode ? 'fw-bold text-dark' : 'text-secondary small' ?>">
                                                                <?= htmlspecialchars($noms_villes[$insee] ?? $insee, ENT_QUOTES, 'UTF-8') ?>
                                                            </span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="p-4 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10 h-100">
                            <h2 class="fw-bold text-dark h5 mb-4 text-uppercase tracking-wider text-center">
                                <span style="color: rgb(210, 10, 40);">|</span> Les plus rapides (Durée)
                            </h2>
                            <?php foreach ($trajets_duree_affiches as $index => $trajet): ?>
                                <?php $metriques = getMetriques($trajet['chemin'], $graphe); ?>
                                <div class="card mb-4 shadow-sm border border-secondary border-opacity-10 rounded-3">
                                    <div class="card-header bg-light rounded-top-3 border-0 pb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0 fw-bold">Option #<?= $index + 1 ?></h6>
                                            <span class="badge fs-7 rounded-pill px-3 py-2" style="background-color: rgb(210, 10, 40); color: #fff;"><?= formaterDuree($trajet['duree_reelle']) ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center small mt-2 px-1 text-dark">
                                            <span><i class="bi bi-clock me-1"></i>Départ : <strong style="color: rgb(210, 10, 40);"><?= $trajet['horaires']['depart'] ?></strong></span>
                                            <i class="bi bi-arrow-right text-muted"></i>
                                            <span><i class="bi bi-flag me-1"></i>Arrivée : <strong><?= $trajet['horaires']['arrivee'] ?></strong></span>
                                            <span class="text-muted">(<?= round($metriques['distance'], 2) ?> km)</span>
                                        </div>
                                    </div>
                                    <div class="card-body p-0 pt-3">
                                        <?php 
                                        $segments = grouperCheminParLigne($trajet["chemin"], $graphe);
                                        foreach ($segments as $sIndex => $segment): 
                                        ?>
                                            <div class="px-4 pb-2">
                                                <span class="badge bg-opacity-75 mb-2" style="background-color: rgb(210, 10, 40); color: #fff;">
                                                    <i class="bi bi-bus-front"></i> Ligne <?= htmlspecialchars($segment['ligne'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                                <ul class="list-unstyled ms-2 mb-0 border-start border-2 border-secondary border-opacity-25 ps-3 pb-2 position-relative">
                                                    <?php foreach ($segment['arrets'] as $aIndex => $insee): ?>
                                                        <?php $isMainNode = ($aIndex === 0 || $aIndex === count($segment['arrets']) - 1); ?>
                                                        <li class="mb-1 position-relative">
                                                            <span class="position-absolute translate-middle-x" style="left: -17.5px; top: 4px;">
                                                                <i class="bi <?= $isMainNode ? 'bi-circle-fill text-dark' : 'bi-circle text-secondary' ?>" style="font-size: 0.55rem;"></i>
                                                            </span>
                                                            <span class="<?= $isMainNode ? 'fw-bold text-dark' : 'text-secondary small' ?>">
                                                                <?= htmlspecialchars($noms_villes[$insee] ?? $insee, ENT_QUOTES, 'UTF-8') ?>
                                                            </span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>
</body>
</html>