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
        // Sécurité 1 : On s'assure d'avoir des chaînes de caractères propres côté PHP
        $numLigne = trim((string)$numLigne);
        $comDepart = trim((string)$comDepart);
        $comArrivee = trim((string)$comArrivee);

        // Sécurité 2 : On utilise TRIM() directement dans SQL pour éviter le piège des espaces invisibles d'Oracle
        $sqlToutesEtapes = "SELECT TRIM(COM_CODE_INSEE_DEPART) AS DEPART, 
                                   TRIM(COM_CODE_INSEE_ARRIVEE) AS ARRIVEE, 
                                   NVL(ETA_DISTANCE, 0) AS DISTANCE 
                            FROM vik_etape 
                            WHERE TRIM(LIG_NUM) = TRIM(:ligne)";
        
        $stmt = preparerRequetePDO($conn, $sqlToutesEtapes);
        $stmt->execute(['ligne' => $numLigne]);
        $etapes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $distanceFinale = 5; // Prix minimum de secours

        // Si Oracle a bien trouvé des étapes pour cette ligne, on calcule :
        if (!empty($etapes)) {
            $distanceTotale = 0;
            $etapeCourante = $comDepart;
            $maxIter = count($etapes) * 2; // Évite les boucles infinies
            $iter = 0;

            while ($etapeCourante !== $comArrivee && $iter < $maxIter) {
                $prochaineEtape = null;
                
                // On cherche le tronçon dans le sens Normal (Départ -> Arrivée)
                foreach ($etapes as $etape) {
                    if ($etape['DEPART'] === $etapeCourante) {
                        $distanceTotale += (float)$etape['DISTANCE'];
                        $prochaineEtape = $etape['ARRIVEE'];
                        break;
                    }
                }
                
                // Sécurité 3 : Si introuvable, on tente dans le sens Inverse (Arrivée -> Départ)
                if (!$prochaineEtape) {
                    foreach ($etapes as $etape) {
                        if ($etape['ARRIVEE'] === $etapeCourante) {
                            $distanceTotale += (float)$etape['DISTANCE'];
                            $prochaineEtape = $etape['DEPART'];
                            break;
                        }
                    }
                }

                // Si le chemin est brisé (données manquantes en base), on arrête
                if (!$prochaineEtape) {
                    break; 
                }
                
                $etapeCourante = $prochaineEtape;
                $iter++;
            }

            // Si on a bien relié le point A au point B, on applique la vraie distance
            if ($etapeCourante === $comArrivee && $distanceTotale > 0) {
                $distanceFinale = $distanceTotale;
            }
        }

        // 3. On va chercher le prix correspondant à la vraie distance
        $sqlTarif = "SELECT TAR_NUM_TRANCHE, TAR_PRIX AS PRIX
                     FROM vik_tarif
                     WHERE TAR_MIN_DIST <= :distance
                       AND TAR_MAX_DIST >= :distance
                     FETCH FIRST 1 ROWS ONLY";
                     
        $stmtTarif = preparerRequetePDO($conn, $sqlTarif);
        $stmtTarif->execute(['distance' => $distanceFinale]);
        
        return $stmtTarif->fetch(PDO::FETCH_ASSOC) ?: false;

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

function reserverAvecCompte($conn, $cli_num, $tarNum, $prix) {
    $res_num = getProchainResNum($conn);

    $sql = "INSERT INTO vik_reservation 
                (cli_num, res_num, tar_num_tranche, res_date, res_nb_points, res_prix_tot)
            VALUES 
                (:cli_num, :res_num, :tar_num, SYSDATE, 0, :prix)";
    $stmt = preparerRequetePDO($conn, $sql);
    return $stmt->execute([
        'cli_num' => $cli_num,
        'res_num' => $res_num,
        'tar_num' => $tarNum,
        'prix'    => $prix
    ]);
}
?>