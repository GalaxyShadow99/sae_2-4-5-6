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
        $numLigne = trim((string)$numLigne);
        $comDepart = trim((string)$comDepart);
        $comArrivee = trim((string)$comArrivee);

        $sqlToutesEtapes = "SELECT TRIM(COM_CODE_INSEE_DEPART) AS DEPART, 
                                   TRIM(COM_CODE_INSEE_ARRIVEE) AS ARRIVEE, 
                                   NVL(ETA_DISTANCE, 0) AS DISTANCE 
                            FROM vik_etape 
                            WHERE TRIM(LIG_NUM) = TRIM(:ligne)";
        
        $stmt = preparerRequetePDO($conn, $sqlToutesEtapes);
        $stmt->execute(['ligne' => $numLigne]);
        $etapes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $distanceFinale = 5; // Prix minimum de secours

        if (!empty($etapes)) {
            // 1. On construit le "Graphe" (la carte complète du réseau pour cette ligne)
            $graphe = [];
            foreach ($etapes as $etape) {
                $u = $etape['DEPART'];
                $v = $etape['ARRIVEE'];
                $dist = (float)$etape['DISTANCE'];
                
                // On crée les routes dans les DEUX sens pour éviter les pièges
                $graphe[$u][] = ['noeud' => $v, 'distance' => $dist];
                $graphe[$v][] = ['noeud' => $u, 'distance' => $dist];
            }

            // 2. Exploration intelligente (Algorithme BFS - Recherche en largeur)
            $file = [ ['noeud' => $comDepart, 'distCumulee' => 0] ]; // Point de départ
            $visites = [ $comDepart => true ]; // Mémoire des arrêts visités pour ne jamais faire demi-tour

            while (!empty($file)) {
                $courant = array_shift($file); // On avance au prochain arrêt
                $u = $courant['noeud'];
                $d = $courant['distCumulee'];

                // Si on a atteint la destination finale !
                if ($u === $comArrivee) {
                    if ($d > 0) {
                        $distanceFinale = $d; // On a notre VRAIE distance totale
                    }
                    break; 
                }

                // Sinon, on regarde les villes voisines
                if (isset($graphe[$u])) {
                    foreach ($graphe[$u] as $voisin) {
                        $v = $voisin['noeud'];
                        // Si on n'est pas encore passé par là, on y va
                        if (!isset($visites[$v])) {
                            $visites[$v] = true;
                            $file[] = [
                                'noeud' => $v, 
                                'distCumulee' => $d + $voisin['distance']
                            ];
                        }
                    }
                }
            }
        }

        // 3. On va chercher le prix correspondant à la vraie distance finale
        $sqlTarif = "SELECT TAR_NUM_TRANCHE, TAR_PRIX AS PRIX
                     FROM vik_tarif
                     WHERE TAR_MIN_DIST <= :distance
                       AND TAR_MAX_DIST >= :distance
                     FETCH FIRST 1 ROWS ONLY";
                     
        $stmtTarif = preparerRequetePDO($conn, $sqlTarif);
        
        // CORRECTION : On arrondit au nombre entier supérieur pour éviter les "trous" entre 10 et 11, 20 et 21, etc.
        $distanceArrondie = ceil($distanceFinale); 
        $stmtTarif->execute(['distance' => $distanceArrondie]);
        
        $resultat = $stmtTarif->fetch(PDO::FETCH_ASSOC);
        if ($resultat) {
            $resultat['DISTANCE'] = $distanceFinale; // On sauvegarde la vraie distance !
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

function reserverSansCompte($conn, $nom, $prenom, $email, $ligne, $dep, $arr, $tarNum, $prix) {
    $cli_num = trouverOuCreerClient($conn, $nom, $prenom, $email);
    if (!$cli_num) return false;

    $res_num = getProchainResNum($conn);

    $sqlRes = "INSERT INTO vik_reservation 
                   (cli_num, res_num, tar_num_tranche, res_date, res_nb_points, res_prix_tot)
               VALUES 
                   (:cli_num, :res_num, :tar_num, SYSDATE, 0, :prix)";
    $stmt = preparerRequetePDO($conn, $sqlRes);
    $ok = $stmt->execute([
        'cli_num' => $cli_num,
        'res_num' => $res_num,
        'tar_num' => $tarNum,
        'prix'    => $prix ?? 0,
    ]);

    return $ok;
}

function reserverAvecCompte($conn, $cli_num, $tarNum, $prix, $pointsGagnes = 0, $pointsUtilises = 0) {
    $res_num = getProchainResNum($conn);

    // 1. Insertion de la réservation dans l'historique
    $sqlResa = "INSERT INTO vik_reservation 
                (cli_num, res_num, tar_num_tranche, res_date, res_nb_points, res_prix_tot)
            VALUES 
                (:cli_num, :res_num, :tar_num, SYSDATE, :points_gagnes, :prix)";
                
    $stmtResa = preparerRequetePDO($conn, $sqlResa);
    $okResa = $stmtResa->execute([
        'cli_num'       => $cli_num,
        'res_num'       => $res_num,
        'tar_num'       => $tarNum,
        'points_gagnes' => $pointsGagnes, // On insère les points gagnés sur le ticket
        'prix'          => $prix
    ]);

    // 2. Mise à jour du solde du client dans la base de données
    if ($okResa && ($pointsGagnes > 0 || $pointsUtilises > 0)) {
        // La cagnotte "En Cours" (EC) diminue si on utilise des points
        // La cagnotte "Totale" (TOT) ne fait qu'augmenter pour l'historique
        $sqlUpdateClient = "UPDATE vik_client 
                            SET cli_nb_points_ec = NVL(cli_nb_points_ec, 0) + :points_gagnes - :points_utilises,
                                cli_nb_points_tot = NVL(cli_nb_points_tot, 0) + :points_gagnes
                            WHERE cli_num = :cli_num";
                            
        $stmtUpdate = preparerRequetePDO($conn, $sqlUpdateClient);
        $stmtUpdate->execute([
            'points_gagnes'   => $pointsGagnes,
            'points_utilises' => $pointsUtilises,
            'cli_num'         => $cli_num
        ]);
    }

    return $okResa;
}
?>