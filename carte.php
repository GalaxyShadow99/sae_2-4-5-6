<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$estConnecte = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

require_once './bdd/env.php';
require_once './bdd/BddLigneUtils.php';
require_once './bdd/reserverutils.php';

define('MOD_BDD', 'ORACLE');
$conn = OuvrirConnexionPDO($dbOracle, $db_usernameOracle, $db_passwordOracle);

// Récupération des lignes avec coordonnées GPS des communes
// On utilise les données statiques de coordonnées basées sur le plan du réseau du sujet
$lignes = ListeLignes($conn);
$communes = ListeCommunesLignes($conn);

// Info client pré-remplissage
$infoClient = [];
if ($estConnecte) {
    $sqlClient = "SELECT cli_nom, cli_prenom, cli_courriel, cli_telephone FROM vik_client WHERE cli_num = :id";
    $stmtClient = $conn->prepare($sqlClient);
    $stmtClient->execute(['id' => $_SESSION['user_id']]);
    $infoClient = $stmtClient->fetch(PDO::FETCH_ASSOC) ?: [];
}

// Récupérer toutes les communes avec leurs coordonnées GPS depuis la BDD si disponible
// Sinon on utilise les coordonnées hardcodées basées sur le réseau Normand
$communesGPS = [];
try {
    $sqlGPS = "SELECT com_code_insee, com_nom FROM vik_commune";
    $stmtGPS = $conn->query($sqlGPS);
    $communesGPS = $stmtGPS->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $communesGPS = [];
}

$conn = null;
?>
<!DOCTYPE html>
<html lang="fr">
<?php include_once("./includes/head.php"); ?>

<style>
    /* ===== MAP PAGE SPECIFIC STYLES ===== */
    #map-container {
        position: relative;
        height: calc(100vh - 70px);
        display: flex;
        flex-direction: row;
    }

    #map {
        flex: 1;
        height: 100%;
        z-index: 1;
    }

    /* Panneau latéral réservation */
    #panel-reservation {
        width: 400px;
        min-width: 340px;
        max-width: 420px;
        height: 100%;
        background: #fff;
        box-shadow: -4px 0 24px rgba(0,0,0,0.13);
        z-index: 10;
        display: flex;
        flex-direction: column;
        transition: transform 0.32s cubic-bezier(.4,0,.2,1);
        overflow: hidden;
    }

    #panel-reservation.panel-hidden {
        transform: translateX(100%);
        width: 0;
        min-width: 0;
        overflow: hidden;
    }

    #panel-header {
        background: #212529;
        color: #fff;
        padding: 18px 20px 14px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        border-bottom: 3px solid #d20a28;
        flex-shrink: 0;
    }

    #panel-header h2 {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0;
        color: #ffdc00;
        letter-spacing: 0.02em;
    }

    #panel-header .ligne-badge {
        background: #d20a28;
        color: #fff;
        font-weight: 700;
        font-size: 1rem;
        padding: 3px 13px;
        border-radius: 20px;
        white-space: nowrap;
    }

    #btn-fermer-panel {
        background: none;
        border: none;
        color: #fff;
        font-size: 1.5rem;
        line-height: 1;
        cursor: pointer;
        padding: 0 4px;
        transition: color 0.2s;
        flex-shrink: 0;
    }
    #btn-fermer-panel:hover { color: #d20a28; }

    #panel-body {
        overflow-y: auto;
        flex: 1;
        padding: 18px 18px 12px 18px;
    }

    /* Ligne info strip */
    #ligne-info-strip {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 10px 14px;
        margin-bottom: 16px;
        font-size: 0.93rem;
    }

    #ligne-info-strip .trajet-arrow {
        color: #d20a28;
        font-weight: 700;
    }

    #ligne-info-strip .badge-arrets {
        background: #212529;
        color: #ffdc00;
        font-size: 0.78rem;
        padding: 2px 8px;
        border-radius: 10px;
    }

    /* Formulaire dans le panneau */
    #panel-body .form-label {
        font-weight: 600;
        font-size: 0.87rem;
        color: #495057;
        margin-bottom: 4px;
    }

    #panel-body .form-control,
    #panel-body .form-select {
        font-size: 0.92rem;
        border-radius: 7px;
        border: 1.5px solid #dee2e6;
        transition: border-color 0.18s;
    }

    #panel-body .form-control:focus,
    #panel-body .form-select:focus {
        border-color: #d20a28;
        box-shadow: 0 0 0 0.15rem rgba(210,10,40,0.13);
    }

    #btn-reserver-carte {
        background: #d20a28;
        border: none;
        color: #fff;
        font-weight: 700;
        font-size: 1rem;
        border-radius: 8px;
        padding: 12px 0;
        width: 100%;
        transition: background 0.2s, transform 0.15s;
        margin-top: 4px;
    }
    #btn-reserver-carte:hover {
        background: #a50820;
        transform: scale(1.02);
    }

    #btn-voir-horaires {
        background: #212529;
        border: none;
        color: #ffdc00;
        font-weight: 600;
        font-size: 0.9rem;
        border-radius: 8px;
        padding: 9px 0;
        width: 100%;
        transition: background 0.2s;
        margin-top: 6px;
        text-decoration: none;
        display: block;
        text-align: center;
    }
    #btn-voir-horaires:hover { background: #343a40; color: #ffdc00; }

    .section-title {
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #6c757d;
        margin: 16px 0 8px 0;
        padding-bottom: 4px;
        border-bottom: 1px solid #e9ecef;
    }

    /* Bulle de résultat après réservation */
    #panel-result {
        margin-top: 10px;
    }

    /* Toggle bouton sur la carte */
    #btn-toggle-panel {
        position: absolute;
        top: 16px;
        right: 16px;
        z-index: 999;
        background: #212529;
        color: #ffdc00;
        border: none;
        border-radius: 8px;
        padding: 10px 16px;
        font-weight: 700;
        font-size: 0.92rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        cursor: pointer;
        transition: background 0.2s;
        display: none;
    }
    #btn-toggle-panel:hover { background: #343a40; }
    #btn-toggle-panel.visible { display: block; }

    /* Légende */
    #legende-carte {
        position: absolute;
        bottom: 30px;
        left: 12px;
        z-index: 500;
        background: rgba(255,255,255,0.95);
        border-radius: 10px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.15);
        padding: 10px 14px;
        font-size: 0.8rem;
        max-width: 160px;
    }

    #legende-carte h6 {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 6px;
        color: #333;
    }

    .legende-item {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 3px;
    }

    .legende-line {
        width: 24px;
        height: 4px;
        border-radius: 2px;
        flex-shrink: 0;
    }

    /* Tooltip carte */
    .leaflet-tooltip-viking {
        background: #212529;
        color: #ffdc00;
        border: none;
        border-radius: 6px;
        font-weight: 700;
        font-size: 0.82rem;
        padding: 4px 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.25);
    }
    .leaflet-tooltip-viking::before {
        border-top-color: #212529;
    }

    /* Popup */
    .popup-viking .leaflet-popup-content-wrapper {
        background: #212529;
        color: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    }
    .popup-viking .leaflet-popup-tip {
        background: #212529;
    }

    /* Responsive */
    @media (max-width: 768px) {
        #map-container { flex-direction: column; height: auto; }
        #map { height: 55vh; min-height: 300px; }
        #panel-reservation {
            width: 100% !important;
            max-width: 100%;
            min-width: 0;
            height: auto;
            max-height: 60vh;
        }
        #panel-reservation.panel-hidden {
            transform: translateY(100%);
            height: 0;
            max-height: 0;
        }
        #btn-toggle-panel { top: 8px; right: 8px; }
        #legende-carte { display: none; }
    }
</style>

<body class="bg-light">
    <?php include_once("./includes/topbar.php"); ?>

    <div id="map-container">
        <!-- Carte Leaflet -->
        <div id="map"></div>

        <!-- Bouton rouvrir panneau -->
        <button id="btn-toggle-panel" onclick="ouvrirPanelVide()">
            Réserver via la carte
        </button>

        <!-- Légende -->
        <div id="legende-carte">
            <h6>Légende</h6>
            <div class="legende-item">
                <div class="legende-line" style="background:#2980b9;"></div>
                <span>Ligne A (aller)</span>
            </div>
            <div class="legende-item">
                <div class="legende-line" style="background:#e74c3c;"></div>
                <span>Ligne B (retour)</span>
            </div>
            <div class="legende-item">
                <div class="legende-line" style="background:#27ae60;"></div>
                <span>Autres</span>
            </div>
            <div style="margin-top:6px; color:#555; font-size:0.73rem;">Cliquez sur une ligne pour réserver</div>
        </div>

        <!-- Panneau latéral réservation -->
        <div id="panel-reservation" class="panel-hidden">
            <div id="panel-header">
                <div style="display:flex; align-items:center; gap:10px; min-width:0;">
                    <span id="panel-ligne-badge" class="ligne-badge">-</span>
                    <h2 id="panel-titre">Réserver un voyage</h2>
                </div>
                <button id="btn-fermer-panel" onclick="fermerPanel()" title="Fermer">×</button>
            </div>

            <div id="panel-body">
                <!-- Info de la ligne -->
                <div id="ligne-info-strip">
                    <div style="font-weight:700; font-size:0.97rem; margin-bottom:4px;">
                        <span id="info-depart-nom">-</span>
                        <span class="trajet-arrow"> → </span>
                        <span id="info-arrivee-nom">-</span>
                    </div>
                    <span id="info-nb-arrets" class="badge-arrets">- arrêts</span>
                    <span style="margin-left:8px; color:#6c757d; font-size:0.82rem;" id="info-distance">-</span>
                </div>

                <div id="panel-result"></div>

                <!-- Formulaire -->
                <form id="form-carte-reservation" method="post" action="reserver.php">
                    <!-- Champ caché pour pré-sélectionner la ligne -->
                    <input type="hidden" name="Num_Ligne[]" id="hidden-ligne" value="">
                    <input type="hidden" name="Com_depart[]" id="hidden-depart" value="">
                    <input type="hidden" name="Com_arrivee[]" id="hidden-arrivee" value="">

                    <p class="section-title">Vos informations</p>

                    <div class="mb-2">
                        <label class="form-label">Nom *</label>
                        <input type="text" class="form-control" name="nom" id="input-nom"
                            value="<?= htmlspecialchars($infoClient['CLI_NOM'] ?? '') ?>"
                            placeholder="DUPONT" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Prénom *</label>
                        <input type="text" class="form-control" name="prenom" id="input-prenom"
                            value="<?= htmlspecialchars($infoClient['CLI_PRENOM'] ?? $_SESSION['user_prenom'] ?? '') ?>"
                            placeholder="Jean" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Téléphone *</label>
                        <input type="text" class="form-control" name="telephone" id="input-tel"
                            value="<?= htmlspecialchars($infoClient['cli_telephone'] ?? '') ?>"
                             maxlength="14" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" id="input-email"
                            value="<?= htmlspecialchars($infoClient['CLI_COURRIEL'] ?? '') ?>"
                            placeholder="jean.dupont@email.fr" required>
                    </div>

                    <p class="section-title">Votre trajet</p>

                    <div class="mb-2">
                        <label class="form-label">Ligne</label>
                        <select class="form-select" id="select-ligne-panel" onchange="onLigneChange(this.value)">
                            <option value="">- Sélectionnez une ligne -</option>
                            <?php foreach ($lignes as $l):
                                $num = trim($l['LIG_NUM']);
                                $dep = $l['COM_NOM_DEBU'] ?: $l['COM_CODE_INSEE_DEBU'];
                                $arr = $l['COM_NOM_TERM'] ?: $l['COM_CODE_INSEE_TERM'];
                            ?>
                            <option value="<?= htmlspecialchars($num) ?>">
                                Ligne <?= htmlspecialchars($num) ?> (<?= htmlspecialchars($dep) ?> → <?= htmlspecialchars($arr) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Départ *</label>
                        <select class="form-select" id="select-depart-panel" required>
                            <option value="" disabled selected>- Choisir d'abord une ligne -</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Arrivée *</label>
                        <select class="form-select" id="select-arrivee-panel" required>
                            <option value="" disabled selected>- Choisir d'abord une ligne -</option>
                        </select>
                    </div>

                    <?php if (!$estConnecte): ?>
                    <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:0.85rem;">
                        <strong>Non connecté :</strong> votre réservation sera enregistrée sans compte.
                        <a href="connexion.php" style="color:#856404;">Se connecter</a> ou
                        <a href="inscription.php" style="color:#856404;">s'inscrire</a> pour gagner des points.
                    </div>
                    <?php else: ?>
                    <div class="alert alert-success py-2 px-3 mb-3" style="font-size:0.85rem;">
                         Connecté - vous gagnerez des points fidélité sur cette réservation.
                    </div>
                    <?php endif; ?>

                    <button type="submit" id="btn-reserver-carte" onclick="return prepareFormSubmit()">
                        Réserver ce trajet →
                    </button>
                    <a id="btn-voir-horaires" href="horaires.php" target="_blank">
                        Voir les horaires de cette ligne
                    </a>

                    <p style="font-size:0.75rem; color:#adb5bd; margin-top:10px;">* Champs obligatoires</p>
                </form>
            </div>
        </div>
    </div>

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>

    <!-- Leaflet CSS + JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
    // ===================================================================
    // DONNÉES : coordonnées GPS des communes du réseau Viking Transport
    // Basées sur le plan du réseau (page 10 du sujet)
    // ===================================================================
    const COMMUNES_GPS = {
        // --- Calvados (14) ---
        "14118": { nom: "Caen",                lat: 49.1829, lng: -0.3707 },
        "14366": { nom: "Lisieux",             lat: 49.1444, lng: 0.2270  },
        "14054": { nom: "Bayeux",              lat: 49.2744, lng: -0.7034 },
        "14128": { nom: "Caumont-l'Éventé",    lat: 49.0833, lng: -0.8000 },
        "14697": { nom: "Saint-Pierre-en-Auge",lat: 49.0167, lng: 0.0333  },
        "14500": { nom: "Courseulles-sur-Mer", lat: 49.3333, lng: -0.4667 },
        "14109": { nom: "Bréval",              lat: 49.0000, lng: 1.5333  }, // hors Calvados mais dans le réseau
        "14377": { nom: "Mézidon-Canon",       lat: 49.0833, lng: 0.0667  },
        "14203": { nom: "Falaise",             lat: 48.8943, lng: -0.1993 },
        "14481": { nom: "Orbec",               lat: 49.0167, lng: 0.4000  },
        "14141": { nom: "Condé-en-Normandie",  lat: 48.8499, lng: -0.5497 },
        "14481": { nom: "Moult-Chicheboville", lat: 49.1167, lng: -0.2167 },
        "14454": { nom: "Lison",               lat: 49.1500, lng: -1.0667 },
        "14437": { nom: "Aunay-sur-Odon",      lat: 49.0167, lng: -0.6333 },
        // --- Manche (50) ---
        "50007": { nom: "Avranches",           lat: 48.6833, lng: -1.3567 },
        "50011": { nom: "Barneville-Carteret", lat: 49.3833, lng: -1.7667 },
        "50150": { nom: "Carentan-les-Marais", lat: 49.3000, lng: -1.2333 },
        "50129": { nom: "Coutances",           lat: 49.0465, lng: -1.4428 },
        "50218": { nom: "Flamanville",         lat: 49.5333, lng: -1.8667 },
        "50228": { nom: "Granville",           lat: 48.8333, lng: -1.5950 },
        "50502": { nom: "Périers",             lat: 49.1833, lng: -1.3833 },
        "50064": { nom: "Saint-Lô",            lat: 49.1172, lng: -1.0911 },
        "50649": { nom: "Valognes",            lat: 49.5079, lng: -1.4714 },
        "50649": { nom: "La Hague",            lat: 49.7000, lng: -1.9000 },
        "50129": { nom: "Le Mont-Saint-Michel",lat: 48.6361, lng: -1.5115 },
        "50550": { nom: "Taillepied",          lat: 49.3833, lng: -1.5333 },
        "50592": { nom: "Villedieu-les-Poêles-Rouffigny", lat: 48.8447, lng: -1.2222 },
        "50076": { nom: "Cherbourg-en-Cotentin", lat: 49.6333, lng: -1.6167 },
        "50256": { nom: "Gatteville-le-Phare", lat: 49.6833, lng: -1.2833 },
        "50116": { nom: "Grandcamp-Maisy",     lat: 49.3833, lng: -1.0333 },
        // --- Orne (61) ---
        "61006": { nom: "Argentan",            lat: 48.7443, lng: 0.0218  },
        "61001": { nom: "Alençon",             lat: 48.4306, lng: 0.0933  },
        "61031": { nom: "Bagnoles de l'Orne Normandie", lat: 48.5583, lng: -0.4167 },
        "61056": { nom: "Bellême",             lat: 48.3728, lng: 0.5599  },
        "61086": { nom: "Briouze",             lat: 48.7000, lng: -0.3667 },
        "61141": { nom: "Domfront-en-Poiraie", lat: 48.5975, lng: -0.6439 },
        "61160": { nom: "Flers",               lat: 48.7478, lng: -0.5706 },
        "61173": { nom: "Gacé",                lat: 48.7833, lng: 0.2833  },
        "61189": { nom: "La Ferté Macé",       lat: 48.5908, lng: -0.3567 },
        "61227": { nom: "L'Aigle",             lat: 48.7583, lng: 0.6333  },
        "61283": { nom: "Mamers",              lat: 48.3524, lng: 0.3726  },
        "61310": { nom: "Mortagne-au-Perche",  lat: 48.5167, lng: 0.5500  },
        "61340": { nom: "Nogent-le-Rotrou",    lat: 48.3222, lng: 0.8261  },
        "61370": { nom: "Sées",                lat: 48.6000, lng: 0.1667  },
        "61469": { nom: "Tinchebray-Bocage",   lat: 48.7667, lng: -0.7333 },
        "61472": { nom: "Vimoutiers",          lat: 48.9333, lng: 0.2000  },
        // --- Seine-Maritime (76) ---
        "76540": { nom: "Rouen",               lat: 49.4431, lng: 1.0993  },
        "76095": { nom: "Bolbec",              lat: 49.5734, lng: 0.4694  },
        "76119": { nom: "Buchy",               lat: 49.5833, lng: 1.3500  },
        "76133": { nom: "Dieppe",              lat: 49.9218, lng: 1.0800  },
        "76222": { nom: "Elbeuf",              lat: 49.3000, lng: 1.0167  },
        "76255": { nom: "Fécamp",              lat: 49.7569, lng: 0.3756  },
        "76259": { nom: "Gamaches",            lat: 49.9833, lng: 1.5500  },
        "76340": { nom: "Gournay-en-Bray",     lat: 49.4833, lng: 1.7333  },
        "76366": { nom: "Le Havre",            lat: 49.4938, lng: 0.1077  },
        "76448": { nom: "Londinières",         lat: 49.8333, lng: 1.4000  },
        "76448": { nom: "Neufchâtel-en-Bray",  lat: 49.7333, lng: 1.4333  },
        "76498": { nom: "Petit-Caux",          lat: 49.9167, lng: 1.2833  },
        "76530": { nom: "Saint-Valery-en-Caux",lat: 49.8667, lng: 0.7167  },
        "76575": { nom: "Tôtes",               lat: 49.6833, lng: 1.0500  },
        "76631": { nom: "Le tréport",          lat: 50.0583, lng: 1.3833  },
        "76559": { nom: "Yvetot",              lat: 49.6167, lng: 0.7500  },
        // --- Eure (27) ---
        "27075": { nom: "Beaumont-le-Roger",   lat: 49.0833, lng: 0.7833  },
        "27115": { nom: "Bernay",              lat: 49.0833, lng: 0.5981  },
        "27148": { nom: "Bréval",              lat: 48.9333, lng: 1.5333  },
        "27168": { nom: "Évreux",              lat: 49.0236, lng: 1.1536  },
        "27197": { nom: "Dreux",               lat: 48.7367, lng: 1.3650  },
        "27211": { nom: "Gaillon",             lat: 49.1667, lng: 1.3333  },
        "27230": { nom: "Gisors",              lat: 49.2817, lng: 1.7819  },
        "27370": { nom: "Louviers",            lat: 49.2167, lng: 1.1667  },
        "27516": { nom: "Pacy-sur-Eure",       lat: 49.0167, lng: 1.3833  },
        "27541": { nom: "Pont-Audemer",        lat: 49.3553, lng: 0.5186  },
        "27549": { nom: "Pont-l'Évêque",       lat: 49.2864, lng: 0.1831  },
        "27564": { nom: "Val-de-Reuil",        lat: 49.2500, lng: 1.2000  },
        "27295": { nom: "Honfleur",            lat: 49.4186, lng: 0.2331  },
        "27186": { nom: "Deauville",           lat: 49.3550, lng: 0.0731  },
        "27186": { nom: "Epaignes",            lat: 49.2667, lng: 0.4500  },
        "27399": { nom: "Mesnils-sur-Iton L'habit", lat: 48.9667, lng: 1.0333 },
        "27468": { nom: "Verneuil d'Avre et d'Iton", lat: 48.7333, lng: 0.9333 },
        "27672": { nom: "Vernon",              lat: 49.0917, lng: 1.4811  },
    };

    // ===================================================================
    // DONNÉES PHP → JS : lignes et communes depuis la BDD
    // ===================================================================
    const LIGNES_BDD = <?= json_encode(array_map(fn($l) => [
        'num'        => trim($l['LIG_NUM']),
        'dep_insee'  => $l['COM_CODE_INSEE_DEBU'],
        'arr_insee'  => $l['COM_CODE_INSEE_TERM'],
        'dep_nom'    => $l['COM_NOM_DEBU'] ?: $l['COM_CODE_INSEE_DEBU'],
        'arr_nom'    => $l['COM_NOM_TERM'] ?: $l['COM_CODE_INSEE_TERM'],
    ], $lignes)) ?>;

    const COMMUNES_BDD = <?= json_encode($communes) ?>;

    // ===================================================================
    // INITIALISATION DE LA CARTE LEAFLET
    // ===================================================================
    const map = L.map('map', {
        center: [49.1, -0.4],
        zoom: 8,
        zoomControl: true,
    });

    // Fond de carte OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 18,
    }).addTo(map);

    // ===================================================================
    // COULEURS DES LIGNES
    // ===================================================================
    const PALETTES = [
        '#e74c3c','#2980b9','#27ae60','#f39c12','#8e44ad',
        '#16a085','#d35400','#2c3e50','#c0392b','#1abc9c',
        '#e67e22','#3498db','#9b59b6','#1e8449','#e91e63',
        '#00bcd4','#ff5722','#607d8b','#795548'
    ];

    function getCouleurLigne(numLigne) {
        const num = numLigne.replace(/[AB]/g, '');
        const idx = (parseInt(num) - 1 + PALETTES.length) % PALETTES.length;
        const base = PALETTES[idx];
        // A = un peu plus saturé, B = variante plus claire
        if (numLigne.endsWith('B')) {
            return base + 'bb'; // transparence légère pour B
        }
        return base;
    }

    // ===================================================================
    // DESSIN DES LIGNES SUR LA CARTE
    // ===================================================================

    // Coordonnées des communes connues (depuis la BDD ou fallback GPS hardcodé)
    // On reconstruit un index : nom normalisé → coordonnées
    const coordIndex = {}; // insee_code → {lat, lng, nom}
    Object.entries(COMMUNES_GPS).forEach(([code, data]) => {
        coordIndex[code] = data;
        // Aussi indexer par nom normalisé
        coordIndex[data.nom.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "")] = data;
    });

    function getCoordsByNom(nom) {
        if (!nom) return null;
        const norm = nom.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        // Cherche d'abord exact, puis partiel
        if (coordIndex[norm]) return coordIndex[norm];
        const keys = Object.keys(coordIndex);
        for (const k of keys) {
            if (k.includes(norm) || norm.includes(k)) return coordIndex[k];
        }
        return null;
    }

    const lignePolylines = {}; // num_ligne → polyline Leaflet
    const communeMarkers = {}; // "lat,lng" → marker

    LIGNES_BDD.forEach((ligne, i) => {
        const coordDep = getCoordsByNom(ligne.dep_nom);
        const coordArr = getCoordsByNom(ligne.arr_nom);

        if (!coordDep || !coordArr) return; // pas de coords → on saute

        const color = getCouleurLigne(ligne.num);
        const isB = ligne.num.endsWith('B');

        // Tracé de la ligne (droite entre départ et arrivée)
        const polyline = L.polyline(
            [[coordDep.lat, coordDep.lng], [coordArr.lat, coordArr.lng]],
            {
                color: color,
                weight: isB ? 3 : 4,
                opacity: 0.82,
                dashArray: isB ? '7,5' : null,
                className: 'ligne-polyline',
            }
        ).addTo(map);

        // Tooltip au survol
        polyline.bindTooltip(
            `<strong>Ligne ${ligne.num}</strong><br>${ligne.dep_nom} → ${ligne.arr_nom}`,
            { sticky: true, className: 'leaflet-tooltip-viking', direction: 'top' }
        );

        // Clic → ouvre le panneau de réservation
        polyline.on('click', () => {
            ouvrirPanelLigne(ligne.num, ligne.dep_nom, ligne.arr_nom, ligne.dep_insee, ligne.arr_insee);
        });

        // Effet hover
        polyline.on('mouseover', function() {
            this.setStyle({ weight: 7, opacity: 1 });
            this.bringToFront();
        });
        polyline.on('mouseout', function() {
            this.setStyle({ weight: isB ? 3 : 4, opacity: 0.82 });
        });

        lignePolylines[ligne.num] = polyline;

        // Marqueurs des arrêts terminus
        [{ coord: coordDep, nom: ligne.dep_nom, insee: ligne.dep_insee },
         { coord: coordArr, nom: ligne.arr_nom, insee: ligne.arr_insee }].forEach(({ coord, nom, insee }) => {
            const key = `${coord.lat},${coord.lng}`;
            if (!communeMarkers[key]) {
                const marker = L.circleMarker([coord.lat, coord.lng], {
                    radius: 5,
                    fillColor: '#fff',
                    color: '#212529',
                    weight: 2,
                    fillOpacity: 1,
                }).addTo(map);
                marker.bindTooltip(nom, { className: 'leaflet-tooltip-viking', direction: 'top' });
                communeMarkers[key] = marker;
            }
        });
    });

    // ===================================================================
    // GESTION DU PANNEAU LATÉRAL
    // ===================================================================
    let ligneSelectionnee = null;

    function ouvrirPanelLigne(numLigne, depNom, arrNom, depInsee, arrInsee) {
        ligneSelectionnee = numLigne;

        // Mise à jour header
        document.getElementById('panel-ligne-badge').textContent = 'Ligne ' + numLigne;
        document.getElementById('panel-titre').textContent = 'Réserver un voyage';

        // Info strip
        document.getElementById('info-depart-nom').textContent = depNom;
        document.getElementById('info-arrivee-nom').textContent = arrNom;

        // Compter les arrêts de cette ligne dans les données BDD
        const arretsLigne = COMMUNES_BDD.filter(c => (c['LIG_NUM'] || '').trim() === numLigne);
        const nbArrets = new Set(arretsLigne.map(a => a['COM_CODE_INSEE_DEPART'])).size + 1;
        document.getElementById('info-nb-arrets').textContent = nbArrets + ' arrêts';
        document.getElementById('info-distance').textContent = '';

        // Lien horaires
        document.getElementById('btn-voir-horaires').href = 'horaires.php?lig_num=' + encodeURIComponent(numLigne);

        // Sélectionner la ligne dans le select
        const sel = document.getElementById('select-ligne-panel');
        sel.value = numLigne;
        remplirArretsPanelDepuis(numLigne, depInsee, arrInsee);

        // Mettre à jour les hidden inputs
        document.getElementById('hidden-ligne').value = numLigne;

        // Ouvrir le panneau
        document.getElementById('panel-reservation').classList.remove('panel-hidden');
        document.getElementById('btn-toggle-panel').classList.remove('visible');

        // Effacer résultat précédent
        document.getElementById('panel-result').innerHTML = '';
    }

    function ouvrirPanelVide() {
        document.getElementById('panel-ligne-badge').textContent = '-';
        document.getElementById('panel-titre').textContent = 'Réserver un voyage';
        document.getElementById('info-depart-nom').textContent = '-';
        document.getElementById('info-arrivee-nom').textContent = '-';
        document.getElementById('info-nb-arrets').textContent = '-';
        document.getElementById('panel-reservation').classList.remove('panel-hidden');
        document.getElementById('btn-toggle-panel').classList.remove('visible');
    }

    function fermerPanel() {
        document.getElementById('panel-reservation').classList.add('panel-hidden');
        document.getElementById('btn-toggle-panel').classList.add('visible');
    }

    // ===================================================================
    // REMPLISSAGE DES ARRÊTS SELON LA LIGNE
    // ===================================================================
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

    function remplirArretsPanelDepuis(numLigne, preselectDep = '', preselectArr = '') {
        const selDep = document.getElementById('select-depart-panel');
        const selArr = document.getElementById('select-arrivee-panel');

        selDep.innerHTML = '<option value="" disabled selected>- Choisir un arrêt -</option>';
        selArr.innerHTML = '<option value="" disabled selected>- Choisir un arrêt -</option>';

        const arretsLigne = COMMUNES_BDD.filter(c => (c['LIG_NUM'] || '').trim() === numLigne);
        const departs  = optionsUniques(arretsLigne, 'COM_CODE_INSEE_DEPART',  'COM_NOM_DEPART');
        const arrivees = optionsUniques(arretsLigne, 'COM_CODE_INSEE_ARRIVEE', 'COM_NOM_ARRIVEE');

        departs.forEach(({ code, label }) => {
            const opt = new Option(label, code);
            if (code === preselectDep) opt.selected = true;
            selDep.appendChild(opt);
        });

        arrivees.forEach(({ code, label }) => {
            const opt = new Option(label, code);
            if (code === preselectArr) opt.selected = true;
            selArr.appendChild(opt);
        });

        // Sync hidden inputs
        selDep.addEventListener('change', () => {
            document.getElementById('hidden-depart').value = selDep.value;
        });
        selArr.addEventListener('change', () => {
            document.getElementById('hidden-arrivee').value = selArr.value;
        });
    }

    function onLigneChange(numLigne) {
        if (!numLigne) return;
        remplirArretsPanelDepuis(numLigne);
        document.getElementById('panel-ligne-badge').textContent = 'Ligne ' + numLigne;
        document.getElementById('hidden-ligne').value = numLigne;
        document.getElementById('btn-voir-horaires').href = 'horaires.php?lig_num=' + encodeURIComponent(numLigne);

        // Mettre en évidence la ligne sur la carte
        Object.entries(lignePolylines).forEach(([num, poly]) => {
            const isB = num.endsWith('B');
            poly.setStyle({ weight: num === numLigne ? 7 : (isB ? 3 : 4), opacity: num === numLigne ? 1 : 0.55 });
        });

        // Mettre à jour l'info strip
        const ligData = LIGNES_BDD.find(l => l.num === numLigne);
        if (ligData) {
            document.getElementById('info-depart-nom').textContent = ligData.dep_nom;
            document.getElementById('info-arrivee-nom').textContent = ligData.arr_nom;
        }
    }

    // ===================================================================
    // VALIDATION AVANT SOUMISSION DU FORMULAIRE
    // ===================================================================
    function prepareFormSubmit() {
        const dep  = document.getElementById('select-depart-panel').value;
        const arr  = document.getElementById('select-arrivee-panel').value;
        const lig  = document.getElementById('select-ligne-panel').value;

        if (!lig) { alert('Veuillez sélectionner une ligne.'); return false; }
        if (!dep) { alert('Veuillez sélectionner un arrêt de départ.'); return false; }
        if (!arr) { alert('Veuillez sélectionner un arrêt d\'arrivée.'); return false; }
        if (dep === arr) { alert('L\'arrêt de départ et d\'arrivée ne peuvent pas être identiques.'); return false; }

        document.getElementById('hidden-ligne').value   = lig;
        document.getElementById('hidden-depart').value  = dep;
        document.getElementById('hidden-arrivee').value = arr;

        return true;
    }

    Object.values(communeMarkers).forEach(marker => {
    });

    window.addEventListener('resize', () => { map.invalidateSize(); });

    document.getElementById('btn-toggle-panel').classList.add('visible');
    </script>
</body>
</html>
