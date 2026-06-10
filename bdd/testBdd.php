<?php

require_once 'env.php';
require_once 'BddUtils.php';

/* 
echo '<style>
    body { font-family: sans-serif; margin: 0; padding: 20px; background-color: #1a1a1a; color: #fff; }
    .status { padding: 20px; font-size: 24px; font-weight: bold; border-radius: 5px; margin-bottom: 20px; }
    .success { background-color: #2ecc71; color: #fff; }
    .error { background-color: #e74c3c; color: #fff; }
    pre { background: #2c3e50; padding: 15px; border-radius: 5px; font-size: 16px; overflow-x: auto; }
</style>';
*/

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
        echo '<div class="status success">OK : Connexion réussie à la base de données !</div>';
        //test si connexion bien établie
        $stmt = $conn->query("SELECT sysdate FROM dual");
        $resultat = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo '<h3>Résultat du SELECT sysdate FROM dual :</h3>';
        echo '<pre>'; print_r($resultat); echo '</pre>';

        echo '<h3>Test : ListeLignes($conn)</h3>';
        $lignes = ListeLignes($conn);
        echo '<pre>'; print_r($lignes); echo '</pre>';

        $id_client_test = '10';
        $insee_dep_test = '14118';
        $insee_arr_test = '50041';

        echo '<hr style="border: 1px solid #34495e; margin: 40px 0;">';
        echo '<h2>🧪 Rapport de test des fonctions utilitaires</h2>';

        // Test ListeInfosClient
        echo '<h3>Test : ListeInfosClient($conn, "' . $id_client_test . '")</h3>';
        $infosClient = ListeInfosClient($conn, $id_client_test);
        echo '<pre>'; print_r($infosClient ? $infosClient : "Aucun client trouvé."); echo '</pre>';

        // Test HistoriqueReservationsClient
        echo '<h3>Test : HistoriqueReservationsClient($conn, "' . $id_client_test . '")</h3>';
        $historique = HistoriqueReservationsClient($conn, $id_client_test);
        echo '<pre>'; print_r($historique); echo '</pre>';

        // Test TrajetsEtHorairesMemeLigne
        echo '<h3>Test : TrajetsEtHorairesMemeLigne($conn, "' . $insee_dep_test . '", "' . $insee_arr_test . '")</h3>';
        $trajetsHoraires = TrajetsEtHorairesMemeLigne($conn, $insee_dep_test, $insee_arr_test);
        echo '<pre>'; print_r($trajetsHoraires); echo '</pre>';

        // Test TrajetPlusCourtMemeLigne
        echo '<h3>Test : TrajetPlusCourtMemeLigne($conn, "' . $insee_dep_test . '", "' . $insee_arr_test . '")</h3>';
        $plusCourt = TrajetPlusCourtMemeLigne($conn, $insee_dep_test, $insee_arr_test);
        echo '<pre>'; print_r($plusCourt ? $plusCourt : "Aucun trajet trouvé."); echo '</pre>';

        // Test TrajetPlusRapideMemeLigne
        echo '<h3>Test : TrajetPlusRapideMemeLigne($conn, "' . $insee_dep_test . '", "' . $insee_arr_test . '")</h3>';
        $plusRapide = TrajetPlusRapideMemeLigne($conn, $insee_dep_test, $insee_arr_test);
        echo '<pre>'; print_r($plusRapide ? $plusRapide : "Aucun trajet trouvé."); echo '</pre>';
    }

} catch (Exception $e) {
    echo '<div class="status error">ERREUR : Liaison impossible avec la base de données</div>';
    echo '<h3>Message d\'erreur brut :</h3>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
}
?>