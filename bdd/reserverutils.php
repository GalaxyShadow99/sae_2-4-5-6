<?php

function OuvrirConnexionPDO($db, $db_username, $db_password) {
    try {
        $conn = new PDO($db, $db_username, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $erreur) {
        echo "Erreur connexion : " . $erreur->getMessage();
        $conn = null;
    }
    return $conn;
}

function ListeLignes($conn) {
    $cur = $conn->query("SELECT LIG_NUM, COM_CODE_INSEE_DEBU, COM_CODE_INSEE_TERM 
                         FROM vik_ligne ORDER BY LIG_NUM");
    return $cur->fetchAll(PDO::FETCH_ASSOC);
}

function ListeCommunesLignes($conn) {
    $cur = $conn->query("SELECT LIG_NUM, COM_CODE_INSEE_ARRET 
                         FROM vik_noeud 
                         ORDER BY LIG_NUM, COM_CODE_INSEE_ARRET");
    return $cur->fetchAll(PDO::FETCH_ASSOC);
}

// =====================================================
// TARIFS
// =====================================================

function GetTarifSegment($conn, $numLigne, $comDepart, $comArrivee) {
    try {
        // Cherche dans les étapes existantes
        $sqlDist = "SELECT ETA_DISTANCE FROM vik_etape
                    WHERE LIG_NUM = :ligne
                      AND COM_CODE_INSEE_DEPART  = :depart
                      AND COM_CODE_INSEE_ARRIVEE = :arrivee
                    FETCH FIRST 1 ROWS ONLY";
        $stmt = preparerRequetePDO($conn, $sqlDist);
        $stmt->execute(['ligne' => $numLigne, 'depart' => $comDepart, 'arrivee' => $comArrivee]);
        $etape = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si pas trouvé, on prend une distance par défaut (tranche 1 = 0-10km = 5€)
        $distance = $etape ? $etape['ETA_DISTANCE'] : 5;

        // Cherche la tranche tarifaire
        $sqlTarif = "SELECT TAR_NUM_TRANCHE, TAR_PRIX AS PRIX
                     FROM vik_tarif
                     WHERE TAR_MIN_DIST <= :distance
                       AND TAR_MAX_DIST >= :distance
                     FETCH FIRST 1 ROWS ONLY";
        $stmt = preparerRequetePDO($conn, $sqlTarif);
        $stmt->execute(['distance' => $distance]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;

    } catch (Exception $e) {
        return false;
    }
}

function preparerRequetePDO($conn, $sql) {
    return $conn->prepare($sql);
}

function trouverOuCreerClient($conn, $nom, $prenom, $email) {
    // 1. Cherche si l'email existe déjà
    $sqlSelect = "SELECT cli_num FROM vik_client 
                  WHERE UPPER(cli_courriel) = UPPER(:email)";
    $stmtSelect = preparerRequetePDO($conn, $sqlSelect);
    $stmtSelect->execute(['email' => $email]);
    $row = $stmtSelect->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Retourne la valeur de la première colonne (CLI_NUM en majuscules sous Oracle)
        return reset($row);
    }

    // 2. Prochain cli_num global
    $sqlMax = "SELECT NVL(MAX(cli_num), 0) + 1 AS PROCHAIN FROM vik_client";
    $stmtMax = preparerRequetePDO($conn, $sqlMax);
    $stmtMax->execute();
    $cli_num = $stmtMax->fetch(PDO::FETCH_ASSOC)['PROCHAIN'];

    // 3. Insert minimal (colonnes obligatoires seulement)
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
        'mdp' => md5(bin2hex(random_bytes(16))),
    ]);

    return $ok ? $cli_num : false;
}

function getProchainResNum($conn) {
    // Numéro de réservation global (unique sur toute la table, pas par client)
    $sql = "SELECT NVL(MAX(res_num), 0) + 1 AS PROCHAIN FROM vik_reservation";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['PROCHAIN'];
}

function reserverSansCompte($conn, $nom, $prenom, $email, $ligne, $dep, $arr, $tarNum, $prix) {
    // 1. Trouve ou crée le client, récupère son cli_num
    $cli_num = trouverOuCreerClient($conn, $nom, $prenom, $email);
    if ($cli_num === false) return false;

    // 2. Numéro de réservation unique global
    $res_num = getProchainResNum($conn);

    // 3. Insère la réservation
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