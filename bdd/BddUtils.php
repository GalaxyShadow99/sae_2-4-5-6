<?php
// BDDUtils.php

// =====================================================
// CONNEXION ET UTILITAIRES
// =====================================================

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

function majDonneesPDO($conn, $sql) {
    return $conn->exec($sql);
}

function preparerRequetePDO($conn, $sql) {
    return $conn->prepare($sql);
}

function LireDonneesPDO3($conn, $sql, &$tab) {
    $cur = $conn->query($sql);
    $tab = $cur->fetchAll(PDO::FETCH_ASSOC);
    return count($tab);
}

// =====================================================
// LIGNES & ARRÊTS
// =====================================================

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
        $sqlDist = "SELECT ETA_DISTANCE FROM vik_etape
                    WHERE LIG_NUM = :ligne
                      AND COM_CODE_INSEE_DEPART  = :depart
                      AND COM_CODE_INSEE_ARRIVEE = :arrivee
                    FETCH FIRST 1 ROWS ONLY";
        $stmt = preparerRequetePDO($conn, $sqlDist);
        $stmt->execute(['ligne' => $numLigne, 'depart' => $comDepart, 'arrivee' => $comArrivee]);
        $etape = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$etape) return false;

        $sqlTarif = "SELECT TAR_NUM_TRANCHE, TAR_PRIX AS PRIX
                     FROM vik_tarif
                     WHERE TAR_KM_MIN <= :distance
                       AND TAR_KM_MAX >= :distance
                     FETCH FIRST 1 ROWS ONLY";
        $stmt = preparerRequetePDO($conn, $sqlTarif);
        $stmt->execute(['distance' => $etape['ETA_DISTANCE']]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;

    } catch (Exception $e) {
        return false;
    }
}

function getTarifParDistance($conn, $distance) {
    $sql = "SELECT tar_num_tranche, tar_prix 
            FROM vik_tarif 
            WHERE :dist BETWEEN tar_km_min AND tar_km_max";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['dist' => $distance]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// =====================================================
// CLIENTS

// =====================================================

function trouverOuCreerClient($conn, $nom, $prenom, $email, $tel = null) {
    
    // 1. Cherche si l'email existe déjà
    $sqlSelect = "SELECT cli_num FROM vik_client 
                  WHERE UPPER(cli_courriel) = UPPER(:email)";
    $stmtSelect = preparerRequetePDO($conn, $sqlSelect);
    $stmtSelect->execute(['email' => $email]);
    $row = $stmtSelect->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        return $row['CLI_NUM'];
    }

    // 2. Prochain cli_num
    $sqlMax = "SELECT NVL(MAX(cli_num), 0) + 1 AS prochain FROM vik_client";
    $stmtMax = preparerRequetePDO($conn, $sqlMax);
    $stmtMax->execute();
    $cli_num = $stmtMax->fetch(PDO::FETCH_ASSOC)['PROCHAIN'];

    // 3. Insert
    $sqlInsert = "INSERT INTO vik_client 
                    (cli_num, cli_nom, cli_prenom, cli_courriel, cli_telephone,
                     cli_nb_points_ec, cli_nb_points_to) 
                  VALUES 
                    (:cli_num, :nom, :prenom, :email, :tel, 0, 0)";
    $stmtInsert = preparerRequetePDO($conn, $sqlInsert);
    $ok = $stmtInsert->execute([
        'cli_num' => $cli_num,
        'nom'     => $nom,
        'prenom'  => $prenom,
        'email'   => $email,
        'tel'     => $tel
    ]);

    return $ok ? $cli_num : false;
}

function ListeInfosClient($conn, $cli_num) {
    $sql = "SELECT * FROM vik_client WHERE cli_num = :num";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['num' => $cli_num]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getPointsClient($conn, $client_num) {
    $sql = "SELECT cli_nb_points_ec, cli_nb_points_to 
            FROM vik_client WHERE cli_num = :cli_num";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['cli_num' => $client_num]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}



// =====================================================
// RÉSERVATIONS
// =====================================================

function getProchainResNum($conn, $client_num) {
    $sql = "SELECT NVL(MAX(res_num), 0) + 1 AS prochain 
            FROM vik_reservation 
            WHERE cli_num = :cli_num";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['cli_num' => $client_num]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['PROCHAIN'];
}

function reserver_ligne($conn, $client_num, $tar_num_tranche, $res_date, $res_nb_points, $res_prix_tot) {
    $res_num = getProchainResNum($conn, $client_num);

    $sql = "INSERT INTO vik_reservation 
                (cli_num, res_num, tar_num_tranche, res_date, res_nb_points, res_prix_tot) 
            VALUES 
                (:cli_num, :res_num, :tar_num, TO_DATE(:res_date,'YYYY-MM-DD'), :res_nb_points, :res_prix_tot)";

    $stmt = preparerRequetePDO($conn, $sql);
    $ok = $stmt->execute([
        'cli_num'       => $client_num,
        'res_num'       => $res_num,
        'tar_num'       => $tar_num_tranche,
        'res_date'      => $res_date,
        'res_nb_points' => $res_nb_points,
        'res_prix_tot'  => $res_prix_tot
    ]);

    return $ok ? $res_num : false;
}

function HistoriqueReservationsClient($conn, $cli_num) {
    $sql = "SELECT * FROM vik_reservation 
            JOIN vik_client USING (cli_num) 
            WHERE cli_num = :num";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['num' => $cli_num]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// =====================================================
// TRAJETS & HORAIRES
// =====================================================



?>
