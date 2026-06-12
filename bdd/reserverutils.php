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

function CalculerDistanceSegment($conn, $numLigne, $comDepart, $comArrivee) {
    try {
        $numLigne   = trim((string)$numLigne);
        $comDepart  = trim((string)$comDepart);
        $comArrivee = trim((string)$comArrivee);

        if ($comDepart === $comArrivee) {
            return false;
        }

        // 1. CARTE PHYSIQUE
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

        if (empty($etapes)) return false;

        // 2. Graphe
        $graphe = [];
        foreach ($etapes as $etape) {
            $u = $etape['DEPART'];
            $v = $etape['ARRIVEE'];
            $dist = (float)$etape['DISTANCE'];

            if ($dist <= 0) continue;

            $graphe[$u][] = ['noeud' => $v, 'distance' => $dist];
            $graphe[$v][] = ['noeud' => $u, 'distance' => $dist];
        }

        // 3. Algorithme de Dijkstra pour trouver la route la plus courte
        $distances = [$comDepart => 0];
        $aTraiter  = [$comDepart => 0];

        while (!empty($aTraiter)) {
            asort($aTraiter);
            $u = array_key_first($aTraiter);
            $d = $aTraiter[$u];
            unset($aTraiter[$u]);

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

        if (!isset($distances[$comArrivee]) || $distances[$comArrivee] <= 0) {
            return false; 
        }

        // On ne retourne QUE la distance. Le calcul du prix se fera à la fin.
        return $distances[$comArrivee]; 

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

// LA NOUVELLE FONCTION GLOBALE
function reserverVoyageMultiSegments($conn, $estConnecte, $userId, $nom, $prenom, $email, $tarNum, $prixTotal, $pointsGagnes, $pointsUtilises, $segments) {
    if ($estConnecte && !empty($userId)) {
        $cli_num = $userId;
    } else {
        $cli_num = trouverOuCreerClient($conn, $nom, $prenom, $email);
        if (!$cli_num) return false;
    }

    $res_num = getProchainResNum($conn);
    $prixOracle = str_replace('.', ',', (string)round((float)$prixTotal, 2));

    // 1. Réservation (UNE SEULE FOIS POUR TOUT LE TRAJET)
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

    if (!$okResa) return false;

    // 2. Étapes (ON BOUCLE SUR TOUS LES SEGMENTS PHYSIQUES DU VOYAGE)
    $sqlEtape = "INSERT INTO vik_etape 
                    (cli_num, res_num, lig_num, com_code_insee_depart, com_code_insee_arrivee, eta_distance, eta_heure) 
                 VALUES 
                    (:cli_num, :res_num, :ligne, :dep, :arr, :dist, SYSDATE)";
    $stmtEtape = preparerRequetePDO($conn, $sqlEtape);

    foreach ($segments as $seg) {
        $distOracle = str_replace('.', ',', (string)round((float)$seg['distance'], 2));
        $stmtEtape->execute([
            'cli_num' => (int)$cli_num, 
            'res_num' => (int)$res_num, 
            'ligne'   => $seg['ligne'], 
            'dep'     => $seg['dep'], 
            'arr'     => $seg['arr'], 
            'dist'    => $distOracle
        ]);
    }

    // 3. Mise à jour des points de fidélité
    if ($estConnecte && ((int)$pointsGagnes > 0 || (int)$pointsUtilises > 0)) {
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

    return true;
}
?>