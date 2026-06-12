<?php
require_once __DIR__ . '/BddConnexionUtils.php';

function ListeCommunesLignes($conn) {
    $cur = $conn->query("SELECT n.LIG_NUM,
                                n.NOE_HEURE_PASSAGE,
                                n.COM_CODE_INSEE_ARRET   AS COM_CODE_INSEE_DEPART,
                                n.COM_CODE_INSEE_SUIVANT AS COM_CODE_INSEE_ARRIVEE,
                                dep.COM_NOM AS COM_NOM_DEPART,
                                arr.COM_NOM AS COM_NOM_ARRIVEE
                         FROM vik_noeud n
                         LEFT JOIN vik_commune dep ON dep.COM_CODE_INSEE = n.COM_CODE_INSEE_ARRET
                         LEFT JOIN vik_commune arr ON arr.COM_CODE_INSEE = n.COM_CODE_INSEE_SUIVANT
                         GROUP BY n.LIG_NUM,
                                  n.COM_CODE_INSEE_ARRET,
                                  n.COM_CODE_INSEE_SUIVANT,
                                  n.NOE_HEURE_PASSAGE,
                                  dep.COM_NOM,
                                  arr.COM_NOM
                         ORDER BY n.LIG_NUM,
                                  n.NOE_HEURE_PASSAGE,
                                  n.COM_CODE_INSEE_ARRET,
                                  n.COM_CODE_INSEE_SUIVANT");
    return $cur->fetchAll(PDO::FETCH_ASSOC);
}

function GetTarifSegment($conn, $numLigne, $comDepart, $comArrivee) {
    try {
        $numLigne   = trim((string)$numLigne);
        $comDepart  = trim((string)$comDepart);
        $comArrivee = trim((string)$comArrivee);

        if ($comDepart === $comArrivee) {
            return false;
        }

        // 1. On récupère la CARTE PHYSIQUE pure de la ligne (on ignore les horaires et les doublons)
        // On exclut les (null) et on remplace la virgule d'Oracle par un point
        $sqlToutesEtapes = "SELECT DISTINCT 
                                   TRIM(COM_CODE_INSEE_ARRET) AS DEPART, 
                                   TRIM(COM_CODE_INSEE_SUIVANT) AS ARRIVEE, 
                                   REPLACE(NOE_DISTANCE_PROCHAIN, ',', '.') AS DISTANCE 
                            FROM vik_noeud 
                            WHERE TRIM(LIG_NUM) = :ligne
                              AND NOE_DISTANCE_PROCHAIN IS NOT NULL";
        
        $stmt = preparerRequetePDO($conn, $sqlToutesEtapes);
        $stmt->execute(['ligne' => $numLigne]);
        $etapes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($etapes)) {
            return false;
        }

        // 2. Construction du "GPS" (Graphe spatial)
        $graphe = [];
        foreach ($etapes as $etape) {
            $u = $etape['DEPART'];
            $v = $etape['ARRIVEE'];
            $dist = (float)$etape['DISTANCE'];

            if ($dist <= 0) continue;

            // On cartographie dans les deux sens pour être sûr de trouver le chemin
            $graphe[$u][] = ['noeud' => $v, 'distance' => $dist];
            $graphe[$v][] = ['noeud' => $u, 'distance' => $dist];
        }

        // 3. Algorithme de calcul de la vraie distance entre Départ et Arrivée (Dijkstra)
        $distances = [$comDepart => 0];
        $aTraiter  = [$comDepart => 0];

        while (!empty($aTraiter)) {
            // Trouver la ville la plus proche dans notre file
            asort($aTraiter);
            $u = array_key_first($aTraiter);
            $d = $aTraiter[$u];
            unset($aTraiter[$u]);

            // Si on est arrivé, on s'arrête !
            if ($u === $comArrivee) break;

            if (isset($graphe[$u])) {
                foreach ($graphe[$u] as $voisin) {
                    $v = $voisin['noeud'];
                    $nouvelleDist = $d + $voisin['distance'];

                    if (!isset($distances[$v]) || $nouvelleDist < $distances[$v]) {
                        $distances[$v] = $nouvelleDist;
                        $aTraiter[$v]  = $nouvelleDist;
                    }
                }
            }
        }

        // 4. Vérification si une route a bien été trouvée
        if (!isset($distances[$comArrivee]) || $distances[$comArrivee] <= 0) {
            return false; 
        }
        $distanceFinale = $distances[$comArrivee];

        // 5. Recherche du prix correspondant dans vik_tarif
        $sqlTarif = "SELECT TAR_NUM_TRANCHE, TAR_PRIX AS PRIX
                     FROM vik_tarif
                     WHERE TAR_MIN_DIST <= :distance
                       AND TAR_MAX_DIST >= :distance
                     FETCH FIRST 1 ROWS ONLY";

        $stmtTarif = preparerRequetePDO($conn, $sqlTarif);
        
        // On arrondit (ex: 20.4 -> 21) pour être sûr de tomber dans une tranche entière de la BDD
        $distanceArrondie = ceil($distanceFinale); 
        $stmtTarif->execute(['distance' => $distanceArrondie]);
        
        $resultat = $stmtTarif->fetch(PDO::FETCH_ASSOC);

        if ($resultat) {
            $resultat['DISTANCE'] = $distanceFinale; 
            return $resultat;
        }

        return false;

    } catch (Exception $e) {
        return false;
    }
}


function trouverOuCreerClient($conn, $nom, $prenom, $email) {
    $sqlSelect = "SELECT cli_num FROM vik_client WHERE UPPER(cli_courriel) = UPPER(:email)";
    $stmtSelect = preparerRequetePDO($conn, $sqlSelect);
    $stmtSelect->execute(['email' => $email]);
    $row = $stmtSelect->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        return reset($row);
    }

    $sqlMax = "SELECT NVL(MAX(cli_num), 0) + 1 AS PROCHAIN FROM vik_client";
    $stmtMax = preparerRequetePDO($conn, $sqlMax);
    $stmtMax->execute();
    $cli_num = $stmtMax->fetch(PDO::FETCH_ASSOC)['PROCHAIN'];

    // CORRECTION DU MOT DE PASSE : On insère une valeur par défaut courte qui passe dans Oracle
    $sqlInsert = "INSERT INTO vik_client 
                    (cli_num, cli_nom, cli_prenom, cli_courriel,
                     cli_nb_points_ec, cli_nb_points_tot, cli_mdp) 
                  VALUES 
                    (:cli_num, :nom, :prenom, :email, 0, 0, :mdp)";
    $stmtInsert = preparerRequetePDO($conn, $sqlInsert);
    $ok = $stmtInsert->execute([
        'cli_num' => $cli_num,
        'nom'     => $nom,
        'prenom'  => $prenom,
        'email'   => $email,
        'mdp'     => 'Demo123!' 
    ]);

    return $ok ? $cli_num : false;
}

function getProchainResNum($conn) {
    $sql = "SELECT NVL(MAX(res_num), 0) + 1 AS PROCHAIN FROM vik_reservation";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['PROCHAIN'];
}

function reserverSansCompte($conn, $nom, $prenom, $email, $ligne, $dep, $arr, $tarNum, $prix, $distance = 0) {
    // RÈGLE DU SUJET : Client non inscrit = num_client 0
    $cli_num = 0; 
    $res_num = getProchainResNum($conn);

    $prixOracle = str_replace('.', ',', (string)round((float)$prix, 2));
    $distOracle = str_replace('.', ',', (string)round((float)$distance, 2));

    $sqlRes = "INSERT INTO vik_reservation 
                   (cli_num, res_num, tar_num_tranche, res_date, res_nb_points, res_prix_tot)
               VALUES 
                   (:cli_num, :res_num, :tar_num, SYSDATE, 0, :prix)";
    $stmt = preparerRequetePDO($conn, $sqlRes);
    $ok = $stmt->execute([
        'cli_num' => $cli_num,
        'res_num' => (int)$res_num,
        'tar_num' => $tarNum ? (int)$tarNum : null,
        'prix'    => $prixOracle
    ]);

    if ($ok) {
        $sqlEtape = "INSERT INTO vik_etape 
                        (cli_num, res_num, lig_num, com_code_insee_depart, com_code_insee_arrivee, eta_distance, eta_heure) 
                     VALUES 
                        (:cli_num, :res_num, :ligne, :dep, :arr, :dist, SYSDATE)";
        $stmtEtape = preparerRequetePDO($conn, $sqlEtape);
        $stmtEtape->execute([
            'cli_num' => $cli_num, 
            'res_num' => (int)$res_num, 
            'ligne'   => $ligne, 
            'dep'     => $dep, 
            'arr'     => $arr, 
            'dist'    => $distOracle
        ]);
    }

    return $ok;
}
function reserverAvecCompte($conn, $cli_num, $tarNum, $prix, $pointsGagnes = 0, $pointsUtilises = 0, $ligne = '', $dep = '', $arr = '', $distance = 0) {
    $res_num = getProchainResNum($conn);

    // --- SÉCURITÉ ORACLE : On force le type entier (int) et on adapte les décimales (virgules) ---
    $prixOracle = str_replace('.', ',', (string)round((float)$prix, 2));
    $distOracle = str_replace('.', ',', (string)round((float)$distance, 2));

    // 1. Insertion de la réservation dans l'historique
    $sqlResa = "INSERT INTO vik_reservation 
                (cli_num, res_num, tar_num_tranche, res_date, res_nb_points, res_prix_tot)
            VALUES 
                (:cli_num, :res_num, :tar_num, SYSDATE, :points_gagnes, :prix)";
                
    $stmtResa = preparerRequetePDO($conn, $sqlResa);
    $okResa = $stmtResa->execute([
        'cli_num'       => (int)$cli_num,
        'res_num'       => (int)$res_num,
        'tar_num'       => $tarNum ? (int)$tarNum : null,
        'points_gagnes' => (int)$pointsGagnes, 
        'prix'          => $prixOracle
    ]);

    // 2. Insertion du détail du trajet dans VIK_ETAPE
    if ($okResa && !empty($ligne)) {
        $sqlEtape = "INSERT INTO vik_etape 
                        (cli_num, res_num, lig_num, com_code_insee_depart, com_code_insee_arrivee, eta_distance, eta_heure) 
                     VALUES 
                        (:cli_num, :res_num, :ligne, :dep, :arr, :dist, SYSDATE)";
        $stmtEtape = preparerRequetePDO($conn, $sqlEtape);
        $stmtEtape->execute([
            'cli_num' => (int)$cli_num, 
            'res_num' => (int)$res_num, 
            'ligne'   => $ligne, 
            'dep'     => $dep, 
            'arr'     => $arr, 
            'dist'    => $distOracle
        ]);
    }

    // 3. Mise à jour du solde du client
    if ($okResa && ((int)$pointsGagnes > 0 || (int)$pointsUtilises > 0)) {
        $sqlUpdateClient = "UPDATE vik_client 
                            SET cli_nb_points_ec = NVL(cli_nb_points_ec, 0) + :points_gagnes - :points_utilises,
                                cli_nb_points_tot = NVL(cli_nb_points_tot, 0) + :points_gagnes
                            WHERE cli_num = :cli_num";
                            
        $stmtUpdate = preparerRequetePDO($conn, $sqlUpdateClient);
        $stmtUpdate->execute([
            'points_gagnes'   => (int)$pointsGagnes,
            'points_utilises' => (int)$pointsUtilises,
            'cli_num'         => (int)$cli_num
        ]);
    }

    return $okResa;
}
?>