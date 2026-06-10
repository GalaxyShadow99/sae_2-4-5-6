<?php

require_once 'env.php';
require_once 'BddUtils.php';

// ⚠️  FICHIER DE DEBUG — À SUPPRIMER OU BLOQUER AVANT LA MISE EN PRODUCTION
// Pour bloquer en prod : décommenter les 3 lignes ci-dessous et définir la constante dans env.php
// if (!defined('ENV_DEV') || ENV_DEV !== true) {
//     http_response_code(403); exit('Accès refusé.');
// }

echo '<style>
    body { font-family: sans-serif; margin: 0; padding: 20px; background-color: #1a1a1a; color: #fff; }
    .status { padding: 20px; font-size: 24px; font-weight: bold; border-radius: 5px; margin-bottom: 20px; }
    .success { background-color: #2ecc71; color: #fff; }
    .error { background-color: #e74c3c; color: #fff; }
    .warning { background-color: #e67e22; color: #fff; padding: 10px 20px; border-radius: 5px; margin-bottom: 20px; font-weight: bold; }
    pre { background: #2c3e50; padding: 15px; border-radius: 5px; font-size: 16px; overflow-x: auto; }
    h2 { color: #f39c12; }
    h3 { color: #3498db; }
</style>';

echo '<div class="warning">⚠️ Fichier de debug — ne pas laisser accessible en production !</div>';

if (!defined('MOD_BDD')) {
    define('MOD_BDD', 'ORACLE');
}

if (MOD_BDD == "MYSQL") {
    $db_username = $db_usernameMySQL;
    $db_password = $db_passwordMySQL;
    $db = $dbMySQL;
} else {
    $db_username = $db_usernameOracle;
    $db_password = $db_passwordOracle;
    $db = $dbOracle;
}

try {
    $conn = OuvrirConnexionPDO($db, $db_username, $db_password);

    if ($conn) {
        echo '<div class="status success">✅ Connexion réussie à la base de données !</div>';

        // ── Vérification connexion ──────────────────────────────────────────
        $stmt = $conn->query("SELECT sysdate FROM dual");
        echo '<h3>SELECT sysdate FROM dual</h3>';
        echo '<pre>'; print_r($stmt->fetchAll(PDO::FETCH_ASSOC)); echo '</pre>';

        // ── Structure des tables ────────────────────────────────────────────
        echo '<h2>📋 Structure des tables SAE</h2>';

        foreach (['VIK_LIGNE', 'VIK_NOEUD', 'VIK_ETAPE', 'VIK_TARIF', 'VIK_RESERVATION', 'VIK_CLIENT'] as $table) {
            echo "<h3>Colonnes de $table</h3>";
            $stmt = $conn->query("SELECT COLUMN_NAME, DATA_TYPE FROM all_tab_columns WHERE table_name = '$table' AND owner = 'SAE' ORDER BY COLUMN_ID");
            echo '<pre>'; print_r($stmt->fetchAll(PDO::FETCH_ASSOC)); echo '</pre>';
        }

        // ── Tests des fonctions ─────────────────────────────────────────────
        echo '<h2>🧪 Rapport de test des fonctions</h2>';

        echo '<h3>ListeLignes()</h3>';
        echo '<pre>'; print_r(ListeLignes($conn)); echo '</pre>';

        echo '<h3>ListeCommunesLignes() — 10 premiers</h3>';
        $communes = ListeCommunesLignes($conn);
        echo '<pre>'; print_r(array_slice($communes, 0, 10)); echo '</pre>';

        $id_client_test  = '10';
        $insee_dep_test  = '14118';
        $insee_arr_test  = '50041';
        $ligne_test      = '1A';

        echo '<h3>ListeInfosClient("' . htmlspecialchars($id_client_test) . '")</h3>';
        $infosClient = ListeInfosClient($conn, $id_client_test);
        // Ne pas afficher le mot de passe s'il est présent
        if ($infosClient && isset($infosClient['CLI_MOT_DE_PASSE'])) {
            $infosClient['CLI_MOT_DE_PASSE'] = '***masqué***';
        }
        echo '<pre>'; print_r($infosClient ?: "Aucun client trouvé."); echo '</pre>';

        echo '<h3>HistoriqueReservationsClient("' . htmlspecialchars($id_client_test) . '")</h3>';
        echo '<pre>'; print_r(HistoriqueReservationsClient($conn, $id_client_test)); echo '</pre>';

        echo '<h3>TrajetsEtHorairesMemeLigne("' . htmlspecialchars($insee_dep_test) . '", "' . htmlspecialchars($insee_arr_test) . '")</h3>';
        echo '<pre>'; print_r(TrajetsEtHorairesMemeLigne($conn, $insee_dep_test, $insee_arr_test)); echo '</pre>';

        echo '<h3>TrajetPlusCourtMemeLigne("' . htmlspecialchars($insee_dep_test) . '", "' . htmlspecialchars($insee_arr_test) . '")</h3>';
        $plusCourt = TrajetPlusCourtMemeLigne($conn, $insee_dep_test, $insee_arr_test);
        echo '<pre>'; print_r($plusCourt ?: "Aucun trajet trouvé."); echo '</pre>';

        echo '<h3>TrajetPlusRapideMemeLigne("' . htmlspecialchars($insee_dep_test) . '", "' . htmlspecialchars($insee_arr_test) . '")</h3>';
        $plusRapide = TrajetPlusRapideMemeLigne($conn, $insee_dep_test, $insee_arr_test);
        echo '<pre>'; print_r($plusRapide ?: "Aucun trajet trouvé."); echo '</pre>';

        // ── Tests US4/US5/US6 ───────────────────────────────────────────────
        echo '<h2>🧪 Tests US4 / US5 / US6</h2>';

        echo '<h3>GetTarifSegment("' . htmlspecialchars($ligne_test) . '", "' . htmlspecialchars($insee_dep_test) . '", "' . htmlspecialchars($insee_arr_test) . '")</h3>';
        $tarif = GetTarifSegment($conn, $ligne_test, $insee_dep_test, $insee_arr_test);
        echo '<pre>'; print_r($tarif ?: "Aucun tarif trouvé — vérifier les colonnes de vik_tarif."); echo '</pre>';

        echo '<h3>reserverSansCompte() — TEST (désactivé par défaut)</h3>';
        echo '<pre>Désactivé pour éviter les insertions accidentelles.
Pour tester, décommentez le bloc ci-dessous dans testBdd.php.</pre>';
        /*
        $ok = reserverSansCompte($conn, 'TEST', 'Utilisateur', 'test@example.com', '1A', '14118', '50041');
        echo '<pre>'; print_r($ok ? '✅ Réservation insérée' : '❌ Échec insertion'); echo '</pre>';
        */

        echo '<h3>ListeHorairesLigne("' . htmlspecialchars($ligne_test) . '")</h3>';
        echo '<pre>'; print_r(array_slice(ListeHorairesLigne($conn, $ligne_test), 0, 5)); echo '</pre>';
    }

} catch (Exception $e) {
    echo '<div class="status error">❌ Liaison impossible avec la base de données</div>';
    echo '<h3>Message d\'erreur :</h3>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
}
?>