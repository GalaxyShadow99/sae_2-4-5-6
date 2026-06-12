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

$lignes = ListeLignes($conn);
$communes = ListeCommunesLignes($conn);

$infoClient = [];
if ($estConnecte) {
    $sqlClient = "SELECT cli_nom, cli_prenom, cli_courriel, cli_telephone FROM vik_client WHERE cli_num = :id";
    $stmtClient = $conn->prepare($sqlClient);
    $stmtClient->execute(['id' => $_SESSION['user_id']]);
    $infoClient = $stmtClient->fetch(PDO::FETCH_ASSOC) ?: [];
}

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
/* ================================================================
   MAP PAGE — VARIABLES & RESET
   ================================================================ */
:root {
    --viking-red:    #d20a28;
    --viking-dark:   #212529;
    --viking-yellow: #ffdc00;
    --viking-light:  #f8f9fa;
    --panel-width:   420px;
    --header-h:      70px;
    --radius:        8px;
    --shadow:        0 4px 24px rgba(0,0,0,0.13);
    --transition:    0.28s cubic-bezier(.4,0,.2,1);
}

/* ================================================================
   LAYOUT
   ================================================================ */
#map-container {
    position: relative;
    height: calc(100vh - var(--header-h));
    display: flex;
    flex-direction: row;
}

#map {
    flex: 1;
    height: 100%;
    z-index: 1;
}

/* ================================================================
   BARRE DE RECHERCHE COMMUNE
   ================================================================ */
#search-bar {
    position: absolute;
    top: 14px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 900;
    width: min(400px, calc(100% - 160px));
    display: flex;
    flex-direction: column;
    gap: 0;
}

#search-commune-wrapper {
    display: flex;
    align-items: center;
    background: #fff;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: visible;
    border: 2px solid transparent;
    transition: border-color var(--transition);
}
#search-commune-wrapper:focus-within {
    border-color: var(--viking-red);
}

#search-commune-input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 0.93rem;
    padding: 10px 14px;
    background: transparent;
    color: var(--viking-dark);
}

#search-commune-btn {
    background: var(--viking-red);
    border: none;
    color: #fff;
    padding: 10px 16px;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    border-radius: 0 calc(var(--radius) - 2px) calc(var(--radius) - 2px) 0;
    transition: background var(--transition);
    flex-shrink: 0;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
#search-commune-btn:hover { background: #a50820; }

#autocomplete-list {
    background: #fff;
    border-radius: 0 0 var(--radius) var(--radius);
    box-shadow: var(--shadow);
    max-height: 220px;
    overflow-y: auto;
    display: none;
    border: 1.5px solid #dee2e6;
    border-top: none;
    z-index: 1000;
    position: relative;
}
#autocomplete-list.open { display: block; }

.autocomplete-item {
    padding: 9px 14px;
    font-size: 0.88rem;
    cursor: pointer;
    transition: background 0.14s;
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--viking-dark);
}
.autocomplete-item:hover,
.autocomplete-item.active {
    background: #fff3f5;
    color: var(--viking-red);
}
.autocomplete-item .ac-badge {
    background: var(--viking-dark);
    color: var(--viking-yellow);
    font-size: 0.7rem;
    padding: 1px 6px;
    border-radius: 10px;
    white-space: nowrap;
    flex-shrink: 0;
}

/* ================================================================
   PANNEAU LATÉRAL
   ================================================================ */
#panel-reservation {
    width: var(--panel-width);
    min-width: 340px;
    max-width: 440px;
    height: 100%;
    background: #fff;
    box-shadow: -4px 0 24px rgba(0,0,0,0.13);
    z-index: 10;
    display: flex;
    flex-direction: column;
    transition: transform var(--transition), width var(--transition);
    overflow: hidden;
}
#panel-reservation.panel-hidden {
    transform: translateX(100%);
    width: 0;
    min-width: 0;
}

#panel-header {
    background: var(--viking-dark);
    color: #fff;
    padding: 16px 18px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    border-bottom: 3px solid var(--viking-red);
    flex-shrink: 0;
}
#panel-header h2 {
    font-size: 1.05rem;
    font-weight: 700;
    margin: 0;
    color: var(--viking-yellow);
}
.ligne-badge {
    background: var(--viking-red);
    color: #fff;
    font-weight: 700;
    font-size: 0.9rem;
    padding: 3px 10px;
    border-radius: 20px;
    white-space: nowrap;
}
#btn-fermer-panel {
    background: none;
    border: none;
    color: #fff;
    font-size: 1.4rem;
    cursor: pointer;
    padding: 0 4px;
    transition: color 0.15s;
    flex-shrink: 0;
}
#btn-fermer-panel:hover { color: var(--viking-red); }

/* Onglets */
#panel-tabs {
    display: flex;
    background: #f1f3f5;
    border-bottom: 1px solid #dee2e6;
    flex-shrink: 0;
}
.panel-tab {
    flex: 1;
    padding: 9px 4px;
    font-size: 0.8rem;
    font-weight: 600;
    text-align: center;
    cursor: pointer;
    border: none;
    background: transparent;
    color: #6c757d;
    transition: color 0.18s, border-bottom 0.18s;
    border-bottom: 3px solid transparent;
}
.panel-tab.active {
    color: var(--viking-red);
    border-bottom: 3px solid var(--viking-red);
    background: #fff;
}

#panel-body {
    overflow-y: auto;
    flex: 1;
    padding: 16px 16px 12px;
}

/* Tab content */
.tab-content { display: none; }
.tab-content.active { display: block; }

/* ================================================================
   SECTION MULTI-TRAJETS
   ================================================================ */
.section-title {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #6c757d;
    margin: 14px 0 7px;
    padding-bottom: 4px;
    border-bottom: 1px solid #e9ecef;
}

/* Panier trajets */
#trajets-panier {
    display: flex;
    flex-direction: column;
    gap: 7px;
    margin-bottom: 10px;
}

.trajet-card {
    background: var(--viking-light);
    border: 1.5px solid #dee2e6;
    border-radius: var(--radius);
    padding: 10px 12px;
    font-size: 0.87rem;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: border-color 0.18s;
    position: relative;
}
.trajet-card:hover { border-color: var(--viking-red); }

.trajet-card .tc-badge {
    background: var(--viking-red);
    color: #fff;
    font-weight: 700;
    font-size: 0.72rem;
    padding: 2px 7px;
    border-radius: 10px;
    flex-shrink: 0;
    min-width: 52px;
    text-align: center;
}
.trajet-card .tc-route {
    flex: 1;
    font-weight: 500;
    color: var(--viking-dark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.trajet-card .tc-arrets {
    font-size: 0.72rem;
    color: #6c757d;
    white-space: nowrap;
}
.trajet-card .tc-remove {
    background: none;
    border: none;
    color: #adb5bd;
    font-size: 1.1rem;
    cursor: pointer;
    padding: 0;
    line-height: 1;
    flex-shrink: 0;
    transition: color 0.15s;
}
.trajet-card .tc-remove:hover { color: var(--viking-red); }

/* ================================================================
   INFOS LIGNE
   ================================================================ */
#ligne-info-strip {
    background: var(--viking-light);
    border: 1px solid #dee2e6;
    border-radius: var(--radius);
    padding: 9px 12px;
    margin-bottom: 12px;
    font-size: 0.9rem;
}
.trajet-arrow { color: var(--viking-red); font-weight: 700; }
.badge-arrets {
    background: var(--viking-dark);
    color: var(--viking-yellow);
    font-size: 0.72rem;
    padding: 2px 8px;
    border-radius: 10px;
}

/* ================================================================
   FILTRES DE CARTE
   ================================================================ */
#filtres-lignes {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    padding: 10px 12px;
    background: var(--viking-light);
    border-bottom: 1px solid #dee2e6;
    flex-shrink: 0;
}
.filtre-badge {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 12px;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.15s;
    user-select: none;
    color: #fff;
}
.filtre-badge.inactive {
    opacity: 0.35;
    filter: grayscale(0.6);
}

/* ================================================================
   FORMULAIRE
   ================================================================ */
.form-label {
    font-weight: 600;
    font-size: 0.83rem;
    color: #495057;
    margin-bottom: 3px;
    display: block;
}
.form-control, .form-select {
    width: 100%;
    font-size: 0.9rem;
    border-radius: var(--radius);
    border: 1.5px solid #dee2e6;
    padding: 7px 10px;
    transition: border-color 0.18s;
    outline: none;
    background: #fff;
}
.form-control:focus, .form-select:focus {
    border-color: var(--viking-red);
    box-shadow: 0 0 0 0.15rem rgba(210,10,40,0.10);
}
.mb-2 { margin-bottom: 8px; }
.mb-3 { margin-bottom: 12px; }

#btn-reserver-carte {
    background: var(--viking-red);
    border: none;
    color: #fff;
    font-weight: 700;
    font-size: 0.97rem;
    border-radius: var(--radius);
    padding: 12px 0;
    width: 100%;
    transition: background 0.2s, transform 0.12s;
    cursor: pointer;
    margin-top: 4px;
}
#btn-reserver-carte:hover { background: #a50820; transform: scale(1.015); }

#btn-voir-horaires {
    background: var(--viking-dark);
    border: none;
    color: var(--viking-yellow);
    font-weight: 600;
    font-size: 0.86rem;
    border-radius: var(--radius);
    padding: 9px 0;
    width: 100%;
    transition: background 0.2s;
    margin-top: 6px;
    text-decoration: none;
    display: block;
    text-align: center;
}
#btn-voir-horaires:hover { background: #343a40; color: var(--viking-yellow); }

/* ================================================================
   BOUTON TOGGLE CARTE
   ================================================================ */
#btn-toggle-panel {
    position: absolute;
    top: 14px;
    right: 14px;
    z-index: 850;
    background: var(--viking-dark);
    color: var(--viking-yellow);
    border: none;
    border-radius: var(--radius);
    padding: 10px 14px;
    font-weight: 700;
    font-size: 0.88rem;
    box-shadow: var(--shadow);
    cursor: pointer;
    transition: background 0.2s;
    display: none;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
#btn-toggle-panel:hover { background: #343a40; }
#btn-toggle-panel.visible { display: block; }

/* ================================================================
   LÉGENDE
   ================================================================ */
#legende-carte {
    position: absolute;
    bottom: 28px;
    left: 10px;
    z-index: 500;
    background: rgba(255,255,255,0.96);
    border-radius: 10px;
    box-shadow: var(--shadow);
    padding: 10px 13px;
    font-size: 0.78rem;
    max-width: 155px;
}
#legende-carte h6 {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    margin: 0 0 6px;
    color: #333;
}
.legende-item {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 3px;
}
.legende-line {
    width: 22px;
    height: 4px;
    border-radius: 2px;
    flex-shrink: 0;
}

/* ================================================================
   TOOLTIPS & POPUPS LEAFLET
   ================================================================ */
.leaflet-tooltip-viking {
    background: var(--viking-dark);
    color: var(--viking-yellow);
    border: none;
    border-radius: 6px;
    font-weight: 700;
    font-size: 0.8rem;
    padding: 4px 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.25);
}
.leaflet-tooltip-viking::before { border-top-color: var(--viking-dark); }

.leaflet-tooltip-routes {
    background: #fff;
    color: var(--viking-dark);
    border: 2px solid var(--viking-dark);
    border-radius: 8px;
    font-size: 0.78rem;
    padding: 6px 10px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.18);
    min-width: 140px;
    pointer-events: none;
}
.leaflet-tooltip-routes::before { border-top-color: var(--viking-dark); }

.tooltip-commune-name {
    font-weight: 800;
    font-size: 0.85rem;
    color: var(--viking-dark);
    margin-bottom: 5px;
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 4px;
}
.tooltip-lignes-list {
    display: flex;
    flex-direction: column;
    gap: 3px;
    margin-top: 4px;
}
.tooltip-ligne-row {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
}
.tooltip-ligne-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}
.tooltip-ligne-label {
    font-weight: 700;
    color: var(--viking-dark);
}
.tooltip-ligne-route {
    color: #6c757d;
    font-weight: 400;
}

.popup-viking .leaflet-popup-content-wrapper {
    background: var(--viking-dark);
    color: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}
.popup-viking .leaflet-popup-tip { background: var(--viking-dark); }

/* ================================================================
   COMPTEUR TRAJETS SÉLECTIONNÉS (badge sur carte)
   ================================================================ */
#trajets-counter {
    position: absolute;
    bottom: 28px;
    right: 14px;
    z-index: 500;
    background: var(--viking-red);
    color: #fff;
    border-radius: 20px;
    padding: 6px 14px;
    font-size: 0.8rem;
    font-weight: 700;
    box-shadow: var(--shadow);
    display: none;
    gap: 6px;
    align-items: center;
    cursor: pointer;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
#trajets-counter.visible { display: flex; }

/* ================================================================
   ONGLET ITINÉRAIRE
   ================================================================ */
#itineraire-list {
    display: flex;
    flex-direction: column;
    gap: 0;
}
.itin-stop {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 6px 0;
    position: relative;
}
.itin-stop:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 9px;
    top: 22px;
    width: 2px;
    height: calc(100% - 4px);
    background: #dee2e6;
}
.itin-dot {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 3px solid var(--viking-red);
    background: #fff;
    flex-shrink: 0;
    margin-top: 1px;
    z-index: 1;
}
.itin-dot.terminus {
    background: var(--viking-red);
}
.itin-label {
    font-size: 0.87rem;
    color: var(--viking-dark);
    font-weight: 500;
    padding-top: 1px;
}
.itin-meta {
    font-size: 0.73rem;
    color: #6c757d;
}

/* ================================================================
   MINI ZOOM COMMUNE
   ================================================================ */
#commune-zoom-toast {
    position: absolute;
    bottom: 70px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 800;
    background: var(--viking-dark);
    color: var(--viking-yellow);
    padding: 7px 16px;
    border-radius: 20px;
    font-size: 0.82rem;
    font-weight: 700;
    box-shadow: var(--shadow);
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.3s;
}
#commune-zoom-toast.show { opacity: 1; }

/* ================================================================
   RESPONSIVE
   ================================================================ */
@media (max-width: 768px) {
    #map-container { flex-direction: column; height: auto; }
    #map { height: 55vh; min-height: 280px; }
    #panel-reservation {
        width: 100% !important;
        max-width: 100%;
        min-width: 0;
        height: auto;
        max-height: 55vh;
    }
    #panel-reservation.panel-hidden {
        transform: translateY(100%);
        height: 0;
        max-height: 0;
    }
    #btn-toggle-panel { top: 8px; right: 8px; }
    #legende-carte { display: none; }
    #search-bar { top: 8px; width: calc(100% - 120px); }
}
</style>

<body>
<?php include_once("./includes/topbar.php"); ?>

<div id="map-container">
    <!-- Carte Leaflet -->
    <div id="map"></div>

    <!-- ============================================================
         BARRE DE RECHERCHE COMMUNE
         ============================================================ -->
    <div id="search-bar">
        <div id="search-commune-wrapper">
            <input type="text"
                   id="search-commune-input"
                   autocomplete="off"
                   aria-label="Recherche de commune"
            >
            <button id="search-commune-btn" title="Centrer sur la commune">Rechercher</button>
        </div>
        <div id="autocomplete-list" role="listbox"></div>
    </div>

    <!-- Toast commune trouvée -->
    <div id="commune-zoom-toast"></div>

    <!-- Bouton toggle panneau -->
    <button id="btn-toggle-panel" onclick="ouvrirPanel()">
        Reserver via la carte
    </button>

    <!-- Compteur trajets sélectionnés -->
    <div id="trajets-counter" onclick="ouvrirPanel(); switchTab('tab-trajets')">
        <span id="trajets-count">0</span> trajet(s) selectionne(s)
    </div>

    <!-- Légende -->
    <div id="legende-carte">
        <h6>Legende</h6>
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
            <span>Autres lignes</span>
        </div>
        <div style="margin-top:5px; color:#888; font-size:0.7rem;">
            Cliquez sur une ligne pour l'ajouter
        </div>
    </div>

    <!-- ============================================================
         PANNEAU LATÉRAL
         ============================================================ -->
    <div id="panel-reservation" class="panel-hidden">
        <div id="panel-header">
            <div style="display:flex; align-items:center; gap:8px; min-width:0; flex:1;">
                <span id="panel-ligne-badge" class="ligne-badge">—</span>
                <h2 id="panel-titre">Reserver un voyage</h2>
            </div>
            <button id="btn-fermer-panel" onclick="fermerPanel()" title="Fermer">×</button>
        </div>

        <!-- Onglets -->
        <div id="panel-tabs">
            <button class="panel-tab active" id="tab-trajet-btn" onclick="switchTab('tab-trajet')">Trajet</button>
            <button class="panel-tab" id="tab-trajets-btn" onclick="switchTab('tab-trajets')">
                Panier <span id="tab-count-badge" style="background:var(--viking-red);color:#fff;border-radius:10px;padding:0 5px;font-size:0.68rem;display:none;"></span>
            </button>
            <button class="panel-tab" id="tab-itin-btn" onclick="switchTab('tab-itin')">Itineraire</button>
            <button class="panel-tab" id="tab-info-btn" onclick="switchTab('tab-info')">Infos</button>
        </div>

        <!-- Filtres lignes visibles -->
        <div id="filtres-lignes" title="Cliquez pour masquer/afficher une ligne">
            <span style="font-size:0.7rem;color:#6c757d;align-self:center;font-weight:600;">Afficher :</span>
            <!-- Generés par JS -->
        </div>

        <div id="panel-body">

            <!-- ======================================
                 ONGLET 1 — AJOUTER UN TRAJET
                 ====================================== -->
            <div class="tab-content active" id="tab-trajet">
                <!-- Info de la ligne cliquée -->
                <div id="ligne-info-strip">
                    <div style="font-weight:700; font-size:0.95rem; margin-bottom:4px;">
                        <span id="info-depart-nom">—</span>
                        <span class="trajet-arrow"> — </span>
                        <span id="info-arrivee-nom">—</span>
                    </div>
                    <span id="info-nb-arrets" class="badge-arrets">— arrets</span>
                    <span style="margin-left:8px; color:#6c757d; font-size:0.8rem;" id="info-distance"></span>
                </div>

                <div class="mb-2">
                    <label class="form-label">Ligne</label>
                    <select class="form-select" id="select-ligne-panel" onchange="onLigneChange(this.value)">
                        <option value="">Selectionnez une ligne</option>
                        <?php foreach ($lignes as $l):
                            $num = trim($l['LIG_NUM']);
                            $dep = $l['COM_NOM_DEBU'] ?: $l['COM_CODE_INSEE_DEBU'];
                            $arr = $l['COM_NOM_TERM'] ?: $l['COM_CODE_INSEE_TERM'];
                        ?>
                        <option value="<?= htmlspecialchars($num) ?>">
                            Ligne <?= htmlspecialchars($num) ?> (<?= htmlspecialchars($dep) ?> — <?= htmlspecialchars($arr) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-2">
                    <label class="form-label">Arret de depart *</label>
                    <select class="form-select" id="select-depart-panel">
                        <option value="" disabled selected>Choisir d'abord une ligne</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Arret d'arrivee *</label>
                    <select class="form-select" id="select-arrivee-panel">
                        <option value="" disabled selected>Choisir d'abord une ligne</option>
                    </select>
                </div>

                <a id="btn-voir-horaires" href="horaires.php" target="_blank">
                    Voir les horaires de cette ligne
                </a>
            </div>

            <!-- ======================================
                 ONGLET 2 — PANIER MULTI-TRAJETS
                 ====================================== -->
            <div class="tab-content" id="tab-trajets">
                <p class="section-title">Vos informations</p>

                <div class="mb-2">
                    <label class="form-label">Nom *</label>
                    <input type="text" class="form-control" id="input-nom"
                        value="<?= htmlspecialchars($infoClient['CLI_NOM'] ?? '') ?>"
                        required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Prenom *</label>
                    <input type="text" class="form-control" id="input-prenom"
                        value="<?= htmlspecialchars($infoClient['CLI_PRENOM'] ?? $_SESSION['user_prenom'] ?? '') ?>"
                        required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Telephone *</label>
                    <input type="text" class="form-control" id="input-tel"
                        value="0000000000"
                        maxlength="14" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-control" id="input-email"
                        value="<?= htmlspecialchars($infoClient['CLI_COURRIEL'] ?? '') ?>"
                        required>
                </div>

                <p class="section-title">Trajets selectionnes</p>
                <div id="trajets-panier">
                    <p style="color:#adb5bd; font-size:0.85rem; text-align:center; padding:12px 0;">
                        Aucun trajet — cliquez une ligne sur la carte
                    </p>
                </div>

                <?php if (!$estConnecte): ?>
                <div style="background:#fff3cd; border:1px solid #ffc107; border-radius:var(--radius); padding:8px 12px; font-size:0.82rem; margin-bottom:10px;">
                    <strong>Non connecte :</strong> reservation sans compte.
                    <a href="connexion.php" style="color:#856404;">Se connecter</a> ou
                    <a href="inscription.php" style="color:#856404;">s'inscrire</a> pour gagner des points.
                </div>
                <?php else: ?>
                <div style="background:#d4edda; border:1px solid #28a745; border-radius:var(--radius); padding:8px 12px; font-size:0.82rem; margin-bottom:10px;">
                    Connecte — vous gagnerez des points fidelite.
                </div>
                <?php endif; ?>

                <div id="panel-result"></div>

                <!-- Formulaire caché (soumission globale) -->
                <form id="form-carte-reservation" method="post" action="reserver.php">
                    <div id="hidden-trajets-container"></div>
                </form>

                <button type="button" id="btn-reserver-carte" onclick="soumettreTousLesTrajets()">
                    Reserver tous les trajets
                </button>
                <button type="button" id="btn-vider-panier"
                    style="width:100%;margin-top:6px;background:transparent;border:1.5px solid #dee2e6;color:#6c757d;border-radius:var(--radius);padding:8px;font-size:0.82rem;cursor:pointer;font-weight:600;transition:0.15s;"
                    onmouseover="this.style.borderColor='#d20a28';this.style.color='#d20a28'"
                    onmouseout="this.style.borderColor='#dee2e6';this.style.color='#6c757d'"
                    onclick="viderPanier()">
                    Vider le panier
                </button>
            </div>

            <!-- ======================================
                 ONGLET 3 — ITINÉRAIRE VISUEL
                 ====================================== -->
            <div class="tab-content" id="tab-itin">
                <p class="section-title">Itineraire des trajets selectionnes</p>
                <div id="itineraire-list">
                    <p style="color:#adb5bd; font-size:0.85rem; text-align:center; padding:12px 0;">
                        Ajoutez des trajets pour voir l'itineraire
                    </p>
                </div>
            </div>

            <!-- ======================================
                 ONGLET 4 — INFOS LIGNE
                 ====================================== -->
            <div class="tab-content" id="tab-info">
                <p class="section-title">Informations sur la ligne</p>
                <div id="tab-info-content">
                    <p style="color:#adb5bd; font-size:0.85rem; text-align:center; padding:12px 0;">
                        Cliquez sur une ligne pour afficher ses informations
                    </p>
                </div>
            </div>

        </div><!-- /panel-body -->
    </div><!-- /panel-reservation -->
</div><!-- /map-container -->

<?php include_once("./includes/footer.php"); ?>
<?php include_once("./includes/jsIncludes.php"); ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// ================================================================
// DONNÉES GPS COMMUNES
// ================================================================
const COMMUNES_GPS = {
    "14118": { nom: "Caen",                lat: 49.1829, lng: -0.3707 },
    "14366": { nom: "Lisieux",             lat: 49.1444, lng: 0.2270  },
    "14054": { nom: "Bayeux",              lat: 49.2744, lng: -0.7034 },
    "14128": { nom: "Caumont-l'Éventé",    lat: 49.0833, lng: -0.8000 },
    "14697": { nom: "Saint-Pierre-en-Auge",lat: 49.0167, lng: 0.0333  },
    "14500": { nom: "Courseulles-sur-Mer", lat: 49.3333, lng: -0.4667 },
    "14377": { nom: "Mézidon-Canon",       lat: 49.0833, lng: 0.0667  },
    "14203": { nom: "Falaise",             lat: 48.8943, lng: -0.1993 },
    "14481": { nom: "Orbec",               lat: 49.0167, lng: 0.4000  },
    "14141": { nom: "Condé-en-Normandie",  lat: 48.8499, lng: -0.5497 },
    "14454": { nom: "Lison",               lat: 49.1500, lng: -1.0667 },
    "14437": { nom: "Aunay-sur-Odon",      lat: 49.0167, lng: -0.6333 },
    "50007": { nom: "Avranches",           lat: 48.6833, lng: -1.3567 },
    "50011": { nom: "Barneville-Carteret", lat: 49.3833, lng: -1.7667 },
    "50150": { nom: "Carentan-les-Marais", lat: 49.3000, lng: -1.2333 },
    "50129": { nom: "Coutances",           lat: 49.0465, lng: -1.4428 },
    "50218": { nom: "Flamanville",         lat: 49.5333, lng: -1.8667 },
    "50228": { nom: "Granville",           lat: 48.8333, lng: -1.5950 },
    "50502": { nom: "Périers",             lat: 49.1833, lng: -1.3833 },
    "50064": { nom: "Saint-Lô",            lat: 49.1172, lng: -1.0911 },
    "50649": { nom: "Valognes",            lat: 49.5079, lng: -1.4714 },
    "50592": { nom: "Villedieu-les-Poêles-Rouffigny", lat: 48.8447, lng: -1.2222 },
    "50076": { nom: "Cherbourg-en-Cotentin", lat: 49.6333, lng: -1.6167 },
    "50256": { nom: "Gatteville-le-Phare", lat: 49.6833, lng: -1.2833 },
    "50116": { nom: "Grandcamp-Maisy",     lat: 49.3833, lng: -1.0333 },
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
    "61370": { nom: "Sées",                lat: 48.6000, lng: 0.1667  },
    "61469": { nom: "Tinchebray-Bocage",   lat: 48.7667, lng: -0.7333 },
    "61472": { nom: "Vimoutiers",          lat: 48.9333, lng: 0.2000  },
    "76540": { nom: "Rouen",               lat: 49.4431, lng: 1.0993  },
    "76095": { nom: "Bolbec",              lat: 49.5734, lng: 0.4694  },
    "76119": { nom: "Buchy",               lat: 49.5833, lng: 1.3500  },
    "76133": { nom: "Dieppe",              lat: 49.9218, lng: 1.0800  },
    "76222": { nom: "Elbeuf",              lat: 49.3000, lng: 1.0167  },
    "76255": { nom: "Fécamp",              lat: 49.7569, lng: 0.3756  },
    "76259": { nom: "Gamaches",            lat: 49.9833, lng: 1.5500  },
    "76340": { nom: "Gournay-en-Bray",     lat: 49.4833, lng: 1.7333  },
    "76366": { nom: "Le Havre",            lat: 49.4938, lng: 0.1077  },
    "76448": { nom: "Neufchâtel-en-Bray",  lat: 49.7333, lng: 1.4333  },
    "76498": { nom: "Petit-Caux",          lat: 49.9167, lng: 1.2833  },
    "76530": { nom: "Saint-Valery-en-Caux",lat: 49.8667, lng: 0.7167  },
    "76575": { nom: "Tôtes",               lat: 49.6833, lng: 1.0500  },
    "76631": { nom: "Le Tréport",          lat: 50.0583, lng: 1.3833  },
    "76559": { nom: "Yvetot",              lat: 49.6167, lng: 0.7500  },
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
    "27399": { nom: "Mesnils-sur-Iton",    lat: 48.9667, lng: 1.0333  },
    "27468": { nom: "Verneuil d'Avre et d'Iton", lat: 48.7333, lng: 0.9333 },
    "27672": { nom: "Vernon",              lat: 49.0917, lng: 1.4811  },
};

const LIGNES_BDD = <?= json_encode(array_map(fn($l) => [
    'num'        => trim($l['LIG_NUM']),
    'dep_insee'  => $l['COM_CODE_INSEE_DEBU'],
    'arr_insee'  => $l['COM_CODE_INSEE_TERM'],
    'dep_nom'    => $l['COM_NOM_DEBU'] ?: $l['COM_CODE_INSEE_DEBU'],
    'arr_nom'    => $l['COM_NOM_TERM'] ?: $l['COM_CODE_INSEE_TERM'],
], $lignes)) ?>;

const COMMUNES_BDD = <?= json_encode($communes) ?>;

// ================================================================
// INDEX COORDONNÉES
// ================================================================
const coordIndex = {};
Object.entries(COMMUNES_GPS).forEach(([code, data]) => {
    coordIndex[code] = data;
    coordIndex[normaliser(data.nom)] = data;
});

function normaliser(s) {
    return (s || '').toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim();
}

function getCoordsByNom(nom) {
    if (!nom) return null;
    const norm = normaliser(nom);
    if (coordIndex[norm]) return coordIndex[norm];
    const keys = Object.keys(coordIndex);
    for (const k of keys) {
        if (k.includes(norm) || norm.includes(k)) return coordIndex[k];
    }
    return null;
}

function getCoordsByInsee(insee) {
    return COMMUNES_GPS[insee] || null;
}

// ================================================================
// CONSTRUCTION DE L'INDEX : commune -> lignes qui y passent
// ================================================================
// Pour chaque commune (par code INSEE), on recense toutes les lignes
// dont elle est terminus de départ ou d'arrivée.
const communeLignesIndex = {}; // insee -> [{ num, dep_nom, arr_nom, color }]

function enregistrerCommuneLigne(insee, nom, ligneObj) {
    if (!insee) return;
    if (!communeLignesIndex[insee]) communeLignesIndex[insee] = [];
    // Éviter les doublons
    if (!communeLignesIndex[insee].find(l => l.num === ligneObj.num)) {
        communeLignesIndex[insee].push(ligneObj);
    }
}

// ================================================================
// PALETTE COULEURS LIGNES
// ================================================================
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
    return numLigne.endsWith('B') ? base + 'bb' : base;
}

// ================================================================
// INIT CARTE
// ================================================================
const map = L.map('map', { center: [49.1, -0.4], zoom: 8, zoomControl: true });
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 18,
}).addTo(map);
// ================================================================
// DESSIN DES LIGNES ET MARQUEURS D'ARRÊTS
// ================================================================
const lignePolylines = {};
const arretMarkers   = {};
const lignesSelectionnees = new Set();

// ------------------------------------------------------------------
// Reconstruction de la polyline ordonnée pour une ligne
// en chaînant les arcs COM_DEPART -> COM_ARRIVEE
// ------------------------------------------------------------------
function construirePolylineLigne(numLigne) {
    const arcs = COMMUNES_BDD.filter(c => (c['LIG_NUM'] || '').trim() === numLigne);
    if (!arcs.length) return [];

    // Construire un graphe de successeurs : insee_dep -> { insee_arr, nom_arr }
    const successeurs = {};
    const predecesseurs = new Set();
    arcs.forEach(a => {
        successeurs[a['COM_CODE_INSEE_DEPART']] = {
            insee: a['COM_CODE_INSEE_ARRIVEE'],
            nom:   a['COM_NOM_ARRIVEE']
        };
        predecesseurs.add(a['COM_CODE_INSEE_ARRIVEE']);
    });

    // Le point de départ est celui qui n'est jamais une arrivée
    let debutInsee = null;
    for (const insee of Object.keys(successeurs)) {
        if (!predecesseurs.has(insee)) { debutInsee = insee; break; }
    }
    // Fallback : utiliser le terminus de départ de la ligne depuis LIGNES_BDD
    if (!debutInsee) {
        const ligData = LIGNES_BDD.find(l => l.num === numLigne);
        if (ligData) debutInsee = ligData.dep_insee;
    }
    if (!debutInsee) debutInsee = Object.keys(successeurs)[0];

    // Chaîner dans l'ordre
    const noeuds = [];
    let current = debutInsee;
    const visited = new Set();
    while (current && !visited.has(current)) {
        visited.add(current);
        // Trouver le nom de ce noeud
        const arcDep = arcs.find(a => a['COM_CODE_INSEE_DEPART'] === current);
        const nomCurrent = arcDep
            ? arcDep['COM_NOM_DEPART']
            : (arcs.find(a => a['COM_CODE_INSEE_ARRIVEE'] === current) || {})['COM_NOM_ARRIVEE'] || current;
        const coord = getCoordsByInsee(current) || getCoordsByNom(nomCurrent);
        if (coord) noeuds.push({ insee: current, nom: nomCurrent, coord });
        const suiv = successeurs[current];
        if (!suiv) break;
        current = suiv.insee;
    }
    // Ajouter le dernier noeud (terminus arrivée)
    if (current && !visited.has(current)) {
        const arcArr = arcs.find(a => a['COM_CODE_INSEE_ARRIVEE'] === current);
        const nomLast = arcArr ? arcArr['COM_NOM_ARRIVEE'] : current;
        const coord = getCoordsByInsee(current) || getCoordsByNom(nomLast);
        if (coord) noeuds.push({ insee: current, nom: nomLast, coord });
    }

    return noeuds;
}

// ------------------------------------------------------------------
// HTML tooltip au survol d'une ligne (liste des communes)
// ------------------------------------------------------------------
function buildTooltipLigneHtml(numLigne, noeuds) {
    const color = getCouleurLigne(numLigne);
    const noms = noeuds.map(n => n.nom);
    const dep  = noms[0]  || '—';
    const arr  = noms[noms.length - 1] || '—';
    const intermediaires = noms.slice(1, -1);

    let html = `<div style="min-width:160px;">`;
    html += `<div style="font-weight:800;font-size:0.88rem;margin-bottom:5px;color:var(--viking-dark);">
        <span style="display:inline-block;background:${color};color:#fff;padding:1px 8px;border-radius:10px;font-size:0.75rem;margin-right:4px;">Ligne ${numLigne}</span>
    </div>`;
    html += `<div style="font-size:0.8rem;font-weight:700;color:var(--viking-dark);">${dep}</div>`;

    if (intermediaires.length) {
        html += `<div style="margin:3px 0;border-left:3px solid ${color};padding-left:6px;">`;
        intermediaires.forEach(n => {
            html += `<div style="font-size:0.74rem;color:#555;padding:1px 0;">↓ ${n}</div>`;
        });
        html += `</div>`;
    }

    html += `<div style="font-size:0.8rem;font-weight:700;color:var(--viking-dark);">${arr}</div>`;
    html += `<div style="margin-top:5px;font-size:0.7rem;color:#aaa;font-style:italic;">Cliquer pour réserver</div>`;
    html += `</div>`;
    return html;
}

// ------------------------------------------------------------------
// HTML tooltip commune (toutes les lignes qui y passent)
// ------------------------------------------------------------------
function buildTooltipCommuneHtml(nom, lignesPassant) {
    let rows = '';
    lignesPassant.forEach(l => {
        rows += `
            <div class="tooltip-ligne-row">
                <div class="tooltip-ligne-dot" style="background:${l.color};"></div>
                <span class="tooltip-ligne-label">Ligne ${l.num}</span>
                <span class="tooltip-ligne-route">${l.dep_nom} — ${l.arr_nom}</span>
            </div>`;
    });
    return `
        <div class="tooltip-commune-name">${nom}</div>
        <div class="tooltip-lignes-list">${rows}</div>
    `;
}

// Icône arrêt terminus
function createArretIcon(color, nbLignes) {
    const size = nbLignes > 1 ? 16 : 12;
    const border = nbLignes > 1 ? 4 : 3;
    return L.divIcon({
        html: `<div style="
            width:${size}px;height:${size}px;border-radius:50%;
            background:#fff;border:${border}px solid ${color};
            box-shadow:0 1px 4px rgba(0,0,0,0.3);
        "></div>`,
        className: '',
        iconSize: [size, size],
        iconAnchor: [size/2, size/2],
    });
}

// Icône arrêt intermédiaire
function createArretIconSmall(color) {
    return L.divIcon({
        html: `<div style="
            width:8px;height:8px;border-radius:50%;
            background:${color};border:2px solid #fff;
            box-shadow:0 1px 3px rgba(0,0,0,0.25);
        "></div>`,
        className: '',
        iconSize: [8, 8],
        iconAnchor: [4, 4],
    });
}

// ------------------------------------------------------------------
// Première passe : construire l'index commune -> lignes
// ------------------------------------------------------------------
LIGNES_BDD.forEach(ligne => {
    const color = getCouleurLigne(ligne.num);
    enregistrerCommuneLigne(ligne.dep_insee, ligne.dep_nom, { num: ligne.num, dep_nom: ligne.dep_nom, arr_nom: ligne.arr_nom, color });
    enregistrerCommuneLigne(ligne.arr_insee, ligne.arr_nom, { num: ligne.num, dep_nom: ligne.dep_nom, arr_nom: ligne.arr_nom, color });
});
COMMUNES_BDD.forEach(arret => {
    const num = (arret['LIG_NUM'] || '').trim();
    if (!num) return;
    const color = getCouleurLigne(num);
    const ligneBase = LIGNES_BDD.find(l => l.num === num);
    if (!ligneBase) return;
    const ligObj = { num, dep_nom: ligneBase.dep_nom, arr_nom: ligneBase.arr_nom, color };
    enregistrerCommuneLigne(arret['COM_CODE_INSEE_ARRIVEE'],  arret['COM_NOM_ARRIVEE'],  ligObj);
    enregistrerCommuneLigne(arret['COM_CODE_INSEE_DEPART'],   arret['COM_NOM_DEPART'],   ligObj);
});

// ------------------------------------------------------------------
// Dessin des lignes et marqueurs
// ------------------------------------------------------------------
LIGNES_BDD.forEach((ligne) => {
    const color  = getCouleurLigne(ligne.num);
    const isB    = ligne.num.endsWith('B');

    // === Reconstruire la polyline ordonnée via tous les nœuds ===
    const noeuds = construirePolylineLigne(ligne.num);

    // Fallback si pas de nœuds intermédiaires : ligne droite terminus → terminus
    let polyPoints;
    if (noeuds.length >= 2) {
        polyPoints = noeuds.map(n => [n.coord.lat, n.coord.lng]);
    } else {
        const coordDep = getCoordsByNom(ligne.dep_nom) || getCoordsByInsee(ligne.dep_insee);
        const coordArr = getCoordsByNom(ligne.arr_nom) || getCoordsByInsee(ligne.arr_insee);
        if (!coordDep || !coordArr) return;
        polyPoints = [[coordDep.lat, coordDep.lng], [coordArr.lat, coordArr.lng]];
    }

    const poly = L.polyline(polyPoints, {
        color,
        weight: isB ? 3 : 4,
        opacity: 0.85,
        dashArray: isB ? '7,5' : null,
        className: 'ligne-polyline'
    }).addTo(map);

    // === Tooltip enrichi avec toutes les communes de la ligne ===
    poly.bindTooltip(
        buildTooltipLigneHtml(ligne.num, noeuds.length >= 2 ? noeuds : [
            { nom: ligne.dep_nom }, { nom: ligne.arr_nom }
        ]),
        {
            sticky: true,
            className: 'leaflet-tooltip-routes',
            direction: 'top',
            offset: [0, -6]
        }
    );

    poly.on('click', () => {
        ouvrirPanelLigne(ligne.num, ligne.dep_nom, ligne.arr_nom, ligne.dep_insee, ligne.arr_insee);
    });
    poly.on('mouseover', function() {
        this.setStyle({ weight: 7, opacity: 1 });
        this.bringToFront();
    });
    poly.on('mouseout', function() {
        const sel = lignesSelectionnees.has(ligne.num);
        this.setStyle({ weight: sel ? 6 : (isB ? 3 : 4), opacity: sel ? 1 : 0.85 });
    });

    lignePolylines[ligne.num] = { poly, color, isB, ligne };

    // === Marqueurs sur CHAQUE nœud de la polyline ===
    const tousLesNoeuds = noeuds.length >= 2 ? noeuds : [
        { insee: ligne.dep_insee, nom: ligne.dep_nom, coord: getCoordsByInsee(ligne.dep_insee) || getCoordsByNom(ligne.dep_nom) },
        { insee: ligne.arr_insee, nom: ligne.arr_nom, coord: getCoordsByInsee(ligne.arr_insee) || getCoordsByNom(ligne.arr_nom) },
    ].filter(n => n.coord);

    tousLesNoeuds.forEach((noeud, idx) => {
        const { coord, nom, insee } = noeud;
        if (!coord) return;
        const key = `${coord.lat.toFixed(4)},${coord.lng.toFixed(4)}`;
        const lignesIci = communeLignesIndex[insee] || [];
        const isTerminus = (idx === 0 || idx === tousLesNoeuds.length - 1);

        if (!arretMarkers[key]) {
            const iconColor = lignesIci.length > 1 ? '#212529' : color;
            const marker = L.marker([coord.lat, coord.lng], {
                icon: isTerminus
                    ? createArretIcon(iconColor, lignesIci.length)
                    : createArretIconSmall(color),
                zIndexOffset: isTerminus ? 100 : 50,
            }).addTo(map);

            marker.bindTooltip(buildTooltipCommuneHtml(nom, lignesIci.length ? lignesIci : [{ num: ligne.num, dep_nom: ligne.dep_nom, arr_nom: ligne.arr_nom, color }]), {
                className: 'leaflet-tooltip-routes',
                direction: 'top',
                offset: [0, -8],
            });
            marker.on('click', () => afficherInfoArret(nom, lignesIci.length ? lignesIci : [{ num: ligne.num, dep_nom: ligne.dep_nom, arr_nom: ligne.arr_nom, color }]));
            arretMarkers[key] = { marker, nom, lignes: [ligne.num], insee };
        } else {
            if (!arretMarkers[key].lignes.includes(ligne.num)) {
                arretMarkers[key].lignes.push(ligne.num);
                const lignesAJour = communeLignesIndex[insee] || [];
                const iconColor = lignesAJour.length > 1 ? '#212529' : color;
                arretMarkers[key].marker.setIcon(
                    isTerminus ? createArretIcon(iconColor, lignesAJour.length) : createArretIconSmall(color)
                );
                arretMarkers[key].marker.unbindTooltip();
                arretMarkers[key].marker.bindTooltip(buildTooltipCommuneHtml(nom, lignesAJour), {
                    className: 'leaflet-tooltip-routes',
                    direction: 'top',
                    offset: [0, -8],
                });
            }
        }
    });
});
    // === Arrêts intermédiaires ===


// ================================================================
// FILTRES LIGNES (badges dans le panneau)
// ================================================================
const filtresContainer = document.getElementById('filtres-lignes');
const lignesFiltres = {};

LIGNES_BDD.forEach(ligne => {
    if (lignesFiltres[ligne.num] !== undefined) return;
    lignesFiltres[ligne.num] = true;
    const color = getCouleurLigne(ligne.num);
    const badge = document.createElement('span');
    badge.className = 'filtre-badge';
    badge.textContent = ligne.num;
    badge.style.background = color;
    badge.title = `Masquer/afficher ligne ${ligne.num}`;
    badge.dataset.ligne = ligne.num;
    badge.addEventListener('click', () => {
        const visible = lignesFiltres[ligne.num];
        lignesFiltres[ligne.num] = !visible;
        badge.classList.toggle('inactive', visible);
        const entry = lignePolylines[ligne.num];
        if (entry) {
            if (!visible) { entry.poly.addTo(map); }
            else           { entry.poly.remove(); }
        }
    });
    filtresContainer.appendChild(badge);
});

// ================================================================
// GESTION ONGLETS
// ================================================================
function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.panel-tab').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    document.getElementById(tabId + '-btn').classList.add('active');
}

// ================================================================
// PANNEAU LATERAL
// ================================================================
let ligneSelectionnee = null;

function ouvrirPanel() {
    document.getElementById('panel-reservation').classList.remove('panel-hidden');
    document.getElementById('btn-toggle-panel').classList.remove('visible');
}

function fermerPanel() {
    document.getElementById('panel-reservation').classList.add('panel-hidden');
    document.getElementById('btn-toggle-panel').classList.add('visible');
}

function ouvrirPanelLigne(numLigne, depNom, arrNom, depInsee, arrInsee) {
    ligneSelectionnee = numLigne;
    ouvrirPanel();
    switchTab('tab-trajet');

    document.getElementById('panel-ligne-badge').textContent = 'Ligne ' + numLigne;
    document.getElementById('panel-titre').textContent = 'Ajouter un trajet';
    document.getElementById('info-depart-nom').textContent = depNom;
    document.getElementById('info-arrivee-nom').textContent = arrNom;

    const arretsLigne = COMMUNES_BDD.filter(c => (c['LIG_NUM'] || '').trim() === numLigne);
    const nbArrets = new Set(arretsLigne.map(a => a['COM_CODE_INSEE_DEPART'])).size + 1;
    document.getElementById('info-nb-arrets').textContent = nbArrets + ' arrets';

    document.getElementById('btn-voir-horaires').href = 'horaires.php?lig_num=' + encodeURIComponent(numLigne);

    const sel = document.getElementById('select-ligne-panel');
    sel.value = numLigne;
    remplirArretsPanelDepuis(numLigne, depInsee, arrInsee);

    surbrillanceLigne(numLigne);
    afficherInfoLigne(numLigne, depNom, arrNom);
}

function surbrillanceLigne(numLigneActive) {
    Object.entries(lignePolylines).forEach(([num, entry]) => {
        const sel = lignesSelectionnees.has(num);
        const active = (num === numLigneActive);
        entry.poly.setStyle({
            weight: active ? 7 : (sel ? 6 : (entry.isB ? 3 : 4)),
            opacity: active ? 1 : (sel ? 0.9 : 0.45),
        });
        if (active || sel) entry.poly.bringToFront();
    });
}

// ================================================================
// REMPLISSAGE ARRÊTS
// ================================================================
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

    selDep.innerHTML = '<option value="" disabled selected>Choisir un arret</option>';
    selArr.innerHTML = '<option value="" disabled selected>Choisir un arret</option>';

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
}

function onLigneChange(numLigne) {
    if (!numLigne) return;
    ligneSelectionnee = numLigne;
    remplirArretsPanelDepuis(numLigne);
    document.getElementById('panel-ligne-badge').textContent = 'Ligne ' + numLigne;
    document.getElementById('btn-voir-horaires').href = 'horaires.php?lig_num=' + encodeURIComponent(numLigne);
    const ligData = LIGNES_BDD.find(l => l.num === numLigne);
    if (ligData) {
        document.getElementById('info-depart-nom').textContent = ligData.dep_nom;
        document.getElementById('info-arrivee-nom').textContent = ligData.arr_nom;
        afficherInfoLigne(numLigne, ligData.dep_nom, ligData.arr_nom);
    }
    surbrillanceLigne(numLigne);
}

// ================================================================
// PANIER MULTI-TRAJETS
// ================================================================
let panier = [];

function ajouterTrajetAuPanier() {
    const numLigne = document.getElementById('select-ligne-panel').value;
    const depCode  = document.getElementById('select-depart-panel').value;
    const arrCode  = document.getElementById('select-arrivee-panel').value;
    const depNom   = document.getElementById('select-depart-panel').selectedOptions[0]?.text || depCode;
    const arrNom   = document.getElementById('select-arrivee-panel').selectedOptions[0]?.text || arrCode;

    if (!numLigne) { showToast('Veuillez selectionner une ligne.'); return; }
    if (!depCode)  { showToast('Veuillez choisir un arret de depart.'); return; }
    if (!arrCode)  { showToast('Veuillez choisir un arret d\'arrivee.'); return; }
    if (depCode === arrCode) { showToast('Depart et arrivee identiques.'); return; }

    const doublon = panier.find(t => t.numLigne === numLigne && t.depCode === depCode && t.arrCode === arrCode);
    if (doublon) { showToast('Ce trajet est deja dans le panier.'); return; }

    const id = Date.now();
    panier.push({ id, numLigne, depCode, depNom, arrCode, arrNom });

    lignesSelectionnees.add(numLigne);
    surbrillanceLigne(numLigne);

    rafraichirPanier();
    switchTab('tab-trajets');
    showToast('Trajet ligne ' + numLigne + ' ajoute.', 'success');
}

function retirerTrajet(id) {
    const idx = panier.findIndex(t => t.id === id);
    if (idx !== -1) {
        const numLigne = panier[idx].numLigne;
        panier.splice(idx, 1);
        if (!panier.find(t => t.numLigne === numLigne)) {
            lignesSelectionnees.delete(numLigne);
            surbrillanceLigne(null);
        }
        rafraichirPanier();
    }
}

function viderPanier() {
    if (!panier.length) return;
    panier = [];
    lignesSelectionnees.clear();
    Object.entries(lignePolylines).forEach(([num, entry]) => {
        entry.poly.setStyle({ weight: entry.isB ? 3 : 4, opacity: 0.85 });
    });
    rafraichirPanier();
    showToast('Panier vide.');
}

function rafraichirPanier() {
    const container = document.getElementById('trajets-panier');
    const count = panier.length;

    const badge = document.getElementById('tab-count-badge');
    badge.textContent = count;
    badge.style.display = count > 0 ? 'inline' : 'none';

    const counter = document.getElementById('trajets-counter');
    document.getElementById('trajets-count').textContent = count;
    counter.classList.toggle('visible', count > 0);

    if (count === 0) {
        container.innerHTML = `<p style="color:#adb5bd;font-size:0.85rem;text-align:center;padding:12px 0;">
            Aucun trajet — cliquez une ligne sur la carte
        </p>`;
        document.getElementById('itineraire-list').innerHTML = `<p style="color:#adb5bd;font-size:0.85rem;text-align:center;padding:12px 0;">
            Ajoutez des trajets pour voir l'itineraire
        </p>`;
        return;
    }

    container.innerHTML = '';
    panier.forEach(t => {
        const card = document.createElement('div');
        card.className = 'trajet-card';
        card.innerHTML = `
            <span class="tc-badge" style="background:${getCouleurLigne(t.numLigne)}">Ligne ${t.numLigne}</span>
            <span class="tc-route">${t.depNom} — ${t.arrNom}</span>
            <button class="tc-remove" title="Retirer" onclick="retirerTrajet(${t.id})">×</button>
        `;
        container.appendChild(card);
    });

    mettreAJourItineraire();
}

function mettreAJourItineraire() {
    const list = document.getElementById('itineraire-list');
    list.innerHTML = '';
    panier.forEach((t, i) => {
        const color = getCouleurLigne(t.numLigne);
        const arrets = COMMUNES_BDD
            .filter(c => (c['LIG_NUM'] || '').trim() === t.numLigne)
            .map(c => c['COM_NOM_ARRIVEE'] || '').filter(Boolean);

        const stopDep = document.createElement('div');
        stopDep.className = 'itin-stop';
        stopDep.innerHTML = `
            <div class="itin-dot terminus" style="border-color:${color};background:${color};"></div>
            <div>
                <div class="itin-label">${t.depNom}</div>
                <div class="itin-meta">Depart — Ligne ${t.numLigne}</div>
            </div>
        `;
        list.appendChild(stopDep);

        const arretsUniq = [...new Set(arrets)].slice(0, 3);
        arretsUniq.forEach(nom => {
            const stop = document.createElement('div');
            stop.className = 'itin-stop';
            stop.innerHTML = `
                <div class="itin-dot" style="border-color:${color};width:14px;height:14px;"></div>
                <div>
                    <div class="itin-label" style="font-size:0.82rem;">${nom}</div>
                </div>
            `;
            list.appendChild(stop);
        });

        const stopArr = document.createElement('div');
        stopArr.className = 'itin-stop';
        stopArr.innerHTML = `
            <div class="itin-dot terminus" style="border-color:${color};background:${color};"></div>
            <div>
                <div class="itin-label">${t.arrNom}</div>
                <div class="itin-meta">Arrivee</div>
            </div>
        `;
        list.appendChild(stopArr);

        if (i < panier.length - 1) {
            const sep = document.createElement('div');
            sep.style.cssText = 'margin:6px 0 6px 26px;font-size:0.72rem;color:#adb5bd;font-style:italic;';
            sep.textContent = 'correspondance';
            list.appendChild(sep);
        }
    });
}

// ================================================================
// SOUMISSION FORMULAIRE MULTI-TRAJETS
// ================================================================
function soumettreTousLesTrajets() {
    if (!panier.length) { showToast('Ajoutez au moins un trajet.'); return; }

    const nom    = document.getElementById('input-nom').value.trim();
    const prenom = document.getElementById('input-prenom').value.trim();
    const tel    = document.getElementById('input-tel').value.trim();
    const email  = document.getElementById('input-email').value.trim();

    if (!nom || !prenom || !tel || !email) {
        showToast('Veuillez remplir tous les champs obligatoires.');
        switchTab('tab-trajets');
        return;
    }

    const form = document.getElementById('form-carte-reservation');
    const hidden = document.getElementById('hidden-trajets-container');
    hidden.innerHTML = '';

    hidden.innerHTML += `<input type="hidden" name="nom" value="${escAttr(nom)}">`;
    hidden.innerHTML += `<input type="hidden" name="prenom" value="${escAttr(prenom)}">`;
    hidden.innerHTML += `<input type="hidden" name="telephone" value="${escAttr(tel)}">`;
    hidden.innerHTML += `<input type="hidden" name="email" value="${escAttr(email)}">`;

    panier.forEach(t => {
        hidden.innerHTML += `<input type="hidden" name="Num_Ligne[]" value="${escAttr(t.numLigne)}">`;
        hidden.innerHTML += `<input type="hidden" name="Com_depart[]" value="${escAttr(t.depCode)}">`;
        hidden.innerHTML += `<input type="hidden" name="Com_arrivee[]" value="${escAttr(t.arrCode)}">`;
    });

    form.submit();
}

function escAttr(s) {
    return String(s).replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

// ================================================================
// INFOS ARRÊT (clic sur marqueur)
// ================================================================
function afficherInfoArret(nomArret, lignesPassant) {
    switchTab('tab-info');
    ouvrirPanel();
    const div = document.getElementById('tab-info-content');
    let html = `<p style="font-weight:700;font-size:0.97rem;margin-bottom:8px;">${nomArret}</p>`;
    html += `<p style="font-size:0.82rem;color:#6c757d;margin-bottom:8px;">${lignesPassant.length} ligne(s) passent par cet arret :</p>`;
    if (lignesPassant.length) {
        html += '<div style="display:flex;flex-wrap:wrap;gap:6px;">';
        lignesPassant.forEach(l => {
            const color = l.color || getCouleurLigne(l.num);
            html += `<span class="filtre-badge" style="background:${color};cursor:pointer;" onclick="ouvrirPanelLigne('${l.num}','${l.dep_nom}','${l.arr_nom}','','')">
                Ligne ${l.num}
            </span>`;
        });
        html += '</div>';
        html += '<div style="margin-top:10px;">';
        lignesPassant.forEach(l => {
            const color = l.color || getCouleurLigne(l.num);
            html += `<div style="font-size:0.8rem;margin-bottom:6px;display:flex;align-items:center;gap:8px;">
                <div style="width:10px;height:10px;border-radius:50%;background:${color};flex-shrink:0;"></div>
                <span style="font-weight:600;">Ligne ${l.num}</span>
                <span style="color:#6c757d;">${l.dep_nom} — ${l.arr_nom}</span>
            </div>`;
        });
        html += '</div>';
    } else {
        html += '<p style="color:#adb5bd;font-size:0.83rem;">Aucune ligne trouvee.</p>';
    }
    div.innerHTML = html;
}

function afficherInfoLigne(numLigne, depNom, arrNom) {
    const div = document.getElementById('tab-info-content');
    const color = getCouleurLigne(numLigne);
    const arretsLigne = COMMUNES_BDD.filter(c => (c['LIG_NUM'] || '').trim() === numLigne);
    const nbArrets = new Set([
        ...arretsLigne.map(a => a['COM_CODE_INSEE_DEPART']),
        ...arretsLigne.map(a => a['COM_CODE_INSEE_ARRIVEE'])
    ]).size;

    let html = `
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
            <span class="filtre-badge" style="background:${color};">Ligne ${numLigne}</span>
            <span style="font-size:0.87rem;font-weight:600;">${depNom} — ${arrNom}</span>
        </div>
        <div style="font-size:0.83rem;color:#6c757d;margin-bottom:8px;">
            ${nbArrets > 0 ? `${nbArrets} arrets recenses` : 'Trajet direct'}
        </div>
        <a href="horaires.php?lig_num=${encodeURIComponent(numLigne)}" target="_blank"
           style="font-size:0.83rem;color:var(--viking-red);text-decoration:none;font-weight:600;">
            Voir les horaires
        </a>
    `;
    div.innerHTML = html;
}

// ================================================================
// RECHERCHE + AUTOCOMPLÉTION COMMUNE
// ================================================================
const searchInput = document.getElementById('search-commune-input');
const searchList  = document.getElementById('autocomplete-list');
const allNoms = Object.values(COMMUNES_GPS).map(c => c.nom);
let selectedAcIndex = -1;

searchInput.addEventListener('input', () => {
    const q = searchInput.value.trim();
    if (q.length < 2) { searchList.classList.remove('open'); return; }

    const qNorm = normaliser(q);
    const resultats = allNoms
        .filter(n => normaliser(n).includes(qNorm))
        .slice(0, 8);

    searchList.innerHTML = '';
    selectedAcIndex = -1;

    if (!resultats.length) { searchList.classList.remove('open'); return; }

    resultats.forEach(nom => {
        const lignesIci = LIGNES_BDD.filter(l =>
            normaliser(l.dep_nom).includes(normaliser(nom)) ||
            normaliser(l.arr_nom).includes(normaliser(nom))
        ).length;

        const item = document.createElement('div');
        item.className = 'autocomplete-item';
        item.setAttribute('role', 'option');
        item.innerHTML = `
            <span>${nom}</span>
            ${lignesIci > 0 ? `<span class="ac-badge">${lignesIci} ligne${lignesIci > 1 ? 's' : ''}</span>` : ''}
        `;
        item.addEventListener('click', () => zoomerSurCommune(nom));
        searchList.appendChild(item);
    });

    searchList.classList.add('open');
});

searchInput.addEventListener('keydown', (e) => {
    const items = searchList.querySelectorAll('.autocomplete-item');
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedAcIndex = Math.min(selectedAcIndex + 1, items.length - 1);
        items.forEach((it, i) => it.classList.toggle('active', i === selectedAcIndex));
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedAcIndex = Math.max(selectedAcIndex - 1, 0);
        items.forEach((it, i) => it.classList.toggle('active', i === selectedAcIndex));
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (selectedAcIndex >= 0 && items[selectedAcIndex]) {
            items[selectedAcIndex].click();
        } else {
            zoomerSurCommune(searchInput.value.trim());
        }
    } else if (e.key === 'Escape') {
        searchList.classList.remove('open');
    }
});

document.getElementById('search-commune-btn').addEventListener('click', () => {
    zoomerSurCommune(searchInput.value.trim());
});

document.addEventListener('click', (e) => {
    if (!e.target.closest('#search-bar')) {
        searchList.classList.remove('open');
    }
});

function zoomerSurCommune(nom) {
    searchList.classList.remove('open');
    const coord = getCoordsByNom(nom);
    if (!coord) { showToast('Commune "' + nom + '" introuvable.'); return; }
    searchInput.value = coord.nom;

    map.flyTo([coord.lat, coord.lng], 12, { duration: 1.2 });

    const key = Object.keys(arretMarkers).find(k => {
        const m = arretMarkers[k];
        return normaliser(m.nom).includes(normaliser(nom)) || normaliser(nom).includes(normaliser(m.nom));
    });
    if (key) {
        const marker = arretMarkers[key].marker;
        marker.openTooltip();
        setTimeout(() => marker.closeTooltip(), 3000);
    }

    const lignesIci = LIGNES_BDD.filter(l =>
        normaliser(l.dep_nom).includes(normaliser(nom)) ||
        normaliser(l.arr_nom).includes(normaliser(nom))
    );
    const msg = lignesIci.length
        ? `${coord.nom} — ${lignesIci.length} ligne(s) disponible(s)`
        : coord.nom;
    showToast(msg, 'info');
}

// ================================================================
// TOAST NOTIFICATIONS
// ================================================================
function showToast(msg, type = 'neutral') {
    const toast = document.getElementById('commune-zoom-toast');
    const colors = { success: '#27ae60', info: '#2980b9', neutral: '#212529', error: '#d20a28' };
    toast.style.background = colors[type] || '#212529';
    toast.textContent = msg;
    toast.classList.add('show');
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => toast.classList.remove('show'), 2800);
}

// ================================================================
// RESPONSIVE & INIT
// ================================================================
window.addEventListener('resize', () => map.invalidateSize());

document.getElementById('btn-toggle-panel').classList.add('visible');

// Bouton "Ajouter au panier" injecté sous les selects
const btnAjout = document.createElement('button');
btnAjout.type = 'button';
btnAjout.id = 'btn-ajouter-au-panier';
btnAjout.textContent = 'Ajouter ce trajet au panier';
btnAjout.style.cssText = `
    width:100%;background:var(--viking-red);border:none;color:#fff;
    font-weight:700;font-size:0.95rem;border-radius:8px;padding:11px 0;
    cursor:pointer;margin-top:4px;transition:background 0.2s;
    text-transform:uppercase;letter-spacing:0.04em;
`;
btnAjout.onmouseover = () => btnAjout.style.background = '#a50820';
btnAjout.onmouseout  = () => btnAjout.style.background = 'var(--viking-red)';
btnAjout.onclick = ajouterTrajetAuPanier;

const selArrEl = document.getElementById('select-arrivee-panel');
selArrEl.closest('.mb-3').after(btnAjout);
</script>
</body>
</html>